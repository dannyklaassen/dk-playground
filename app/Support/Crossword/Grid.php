<?php

declare(strict_types=1);

namespace App\Support\Crossword;

use App\Enums\Direction;

/**
 * A criss-cross grid on unbounded integer coordinates. Absolute positions are
 * irrelevant: the grid is cropped to its bounding box when the entries are read
 * out, which is what centres the first word.
 */
class Grid
{
    /** @var array<string, string> "row,column" => letter */
    private array $cells = [];

    /** @var list<Placement> */
    private array $placements = [];

    /**
     * @return list<Placement>
     */
    public function placements(): array
    {
        return $this->placements;
    }

    public function isEmpty(): bool
    {
        return $this->placements === [];
    }

    /**
     * Place a word, enforcing the three placement rules. Every word after the
     * first MUST cross an already placed word, which is what keeps the grid
     * connected. Returns false when the placement is invalid.
     */
    public function place(WordCandidate $candidate, int $row, int $column, Direction $direction): bool
    {
        $crossings = $this->crossingsAt($candidate, $row, $column, $direction);

        if ($crossings === null || ($crossings === 0 && ! $this->isEmpty())) {
            return false;
        }

        foreach (mb_str_split($candidate->word) as $index => $letter) {
            [$cellRow, $cellColumn] = $this->cellAt($row, $column, $direction, $index);

            $this->cells["{$cellRow},{$cellColumn}"] = $letter;
        }

        $this->placements[] = new Placement($candidate, $row, $column, $direction, $crossings);

        return true;
    }

    /**
     * The number of crossings this placement would make, or null when it
     * violates one of the placement rules.
     */
    public function crossingsAt(WordCandidate $candidate, int $row, int $column, Direction $direction): ?int
    {
        $letters = mb_str_split($candidate->word);
        $length = count($letters);

        [$headRow, $headColumn] = $this->cellAt($row, $column, $direction, -1);
        [$tailRow, $tailColumn] = $this->cellAt($row, $column, $direction, $length);

        if ($this->isFilled($headRow, $headColumn) || $this->isFilled($tailRow, $tailColumn)) {
            return null;
        }

        $crossings = 0;

        foreach ($letters as $index => $letter) {
            [$cellRow, $cellColumn] = $this->cellAt($row, $column, $direction, $index);
            $existing = $this->cells["{$cellRow},{$cellColumn}"] ?? null;

            if ($existing !== null) {
                if ($existing !== $letter) {
                    return null;
                }

                $crossings++;

                continue;
            }

            $sideRow = $direction === Direction::Across ? 1 : 0;
            $sideColumn = $direction === Direction::Across ? 0 : 1;

            if ($this->isFilled($cellRow - $sideRow, $cellColumn - $sideColumn)
                || $this->isFilled($cellRow + $sideRow, $cellColumn + $sideColumn)) {
                return null;
            }
        }

        return $crossings;
    }

    /**
     * Every valid placement of this candidate that crosses an already placed
     * word, most crossings first and then most compact.
     *
     * @return list<Placement>
     */
    public function validPlacements(WordCandidate $candidate): array
    {
        $letters = mb_str_split($candidate->word);
        $found = [];

        foreach ($this->placements as $placed) {
            $direction = $placed->direction === Direction::Across ? Direction::Down : Direction::Across;

            foreach (mb_str_split($placed->word()) as $placedIndex => $placedLetter) {
                foreach ($letters as $index => $letter) {
                    if ($letter !== $placedLetter) {
                        continue;
                    }

                    [$crossRow, $crossColumn] = $this->cellAt(
                        $placed->row,
                        $placed->column,
                        $placed->direction,
                        $placedIndex,
                    );

                    $row = $direction === Direction::Down ? $crossRow - $index : $crossRow;
                    $column = $direction === Direction::Across ? $crossColumn - $index : $crossColumn;
                    $key = "{$row},{$column},{$direction->value}";

                    if (isset($found[$key])) {
                        continue;
                    }

                    $crossings = $this->crossingsAt($candidate, $row, $column, $direction);
                    if ($crossings === null) {
                        continue;
                    }
                    if ($crossings === 0) {
                        continue;
                    }

                    $found[$key] = new Placement($candidate, $row, $column, $direction, $crossings);
                }
            }
        }

        $placements = array_values($found);

        usort($placements, fn (Placement $a, Placement $b): int => [
            -$a->crossings, $this->spanWith($a), $a->row, $a->column, $a->direction->value,
        ] <=> [
            -$b->crossings, $this->spanWith($b), $b->row, $b->column, $b->direction->value,
        ]);

        return $placements;
    }

    public function width(): int
    {
        return $this->bounds()['width'];
    }

    public function height(): int
    {
        return $this->bounds()['height'];
    }

    /** The number of cells shared by two words. */
    public function crossings(): int
    {
        return count(array_filter($this->coverage(), fn (int $count): bool => $count > 1));
    }

    /** The number of words that cross two or more other words. */
    public function doubleCrossings(): int
    {
        $coverage = $this->coverage();
        $doubles = 0;

        foreach ($this->placements as $placement) {
            $crossings = 0;

            foreach (array_keys(mb_str_split($placement->word())) as $index) {
                [$row, $column] = $this->cellAt($placement->row, $placement->column, $placement->direction, $index);

                if (($coverage["{$row},{$column}"] ?? 0) > 1) {
                    $crossings++;
                }
            }

            if ($crossings >= 2) {
                $doubles++;
            }
        }

        return $doubles;
    }

    /**
     * The placed words, cropped to the bounding box and numbered from the top
     * left. An across and a down word starting on the same square share a
     * number.
     *
     * @return list<array{word: string, clue: string, exercise_id: string|null, row: int, col: int, direction: string, number: int}>
     */
    public function entries(): array
    {
        $bounds = $this->bounds();
        $starts = [];

        foreach ($this->placements as $placement) {
            $row = $placement->row - $bounds['minRow'];
            $column = $placement->column - $bounds['minColumn'];
            $starts["{$row},{$column}"] = [$row, $column];
        }

        uasort($starts, fn (array $a, array $b): int => [$a[0], $a[1]] <=> [$b[0], $b[1]]);

        $numbers = [];

        foreach (array_keys($starts) as $key) {
            $numbers[$key] = count($numbers) + 1;
        }

        $entries = [];

        foreach ($this->placements as $placement) {
            $row = $placement->row - $bounds['minRow'];
            $column = $placement->column - $bounds['minColumn'];

            $entries[] = [
                ...$placement->candidate->toArray(),
                'row' => $row,
                'col' => $column,
                'direction' => $placement->direction->value,
                'number' => $numbers["{$row},{$column}"],
            ];
        }

        usort($entries, fn (array $a, array $b): int => [$a['number'], $a['direction']] <=> [$b['number'], $b['direction']]);

        return $entries;
    }

    /**
     * @return array{minRow: int, minColumn: int, width: int, height: int}
     */
    private function bounds(): array
    {
        if ($this->cells === []) {
            return ['minRow' => 0, 'minColumn' => 0, 'width' => 0, 'height' => 0];
        }

        $rows = [];
        $columns = [];

        foreach (array_keys($this->cells) as $key) {
            [$row, $column] = explode(',', $key);
            $rows[] = (int) $row;
            $columns[] = (int) $column;
        }

        return [
            'minRow' => min($rows),
            'minColumn' => min($columns),
            'width' => max($columns) - min($columns) + 1,
            'height' => max($rows) - min($rows) + 1,
        ];
    }

    /**
     * How many words cover each cell.
     *
     * @return array<string, int>
     */
    private function coverage(): array
    {
        $coverage = [];

        foreach ($this->placements as $placement) {
            foreach (array_keys(mb_str_split($placement->word())) as $index) {
                [$row, $column] = $this->cellAt($placement->row, $placement->column, $placement->direction, $index);

                $coverage["{$row},{$column}"] = ($coverage["{$row},{$column}"] ?? 0) + 1;
            }
        }

        return $coverage;
    }

    /** The bounding-box perimeter the grid would have with this placement added. */
    private function spanWith(Placement $placement): int
    {
        $bounds = $this->bounds();
        $length = mb_strlen($placement->word());

        [$endRow, $endColumn] = $this->cellAt($placement->row, $placement->column, $placement->direction, $length - 1);

        $minRow = min($bounds['minRow'], $placement->row);
        $minColumn = min($bounds['minColumn'], $placement->column);
        $maxRow = max($bounds['minRow'] + $bounds['height'] - 1, $endRow);
        $maxColumn = max($bounds['minColumn'] + $bounds['width'] - 1, $endColumn);

        return ($maxRow - $minRow) + ($maxColumn - $minColumn);
    }

    /**
     * @return array{int, int}
     */
    private function cellAt(int $row, int $column, Direction $direction, int $index): array
    {
        return $direction === Direction::Across
            ? [$row, $column + $index]
            : [$row + $index, $column];
    }

    private function isFilled(int $row, int $column): bool
    {
        return isset($this->cells["{$row},{$column}"]);
    }
}
