<?php

declare(strict_types=1);

namespace App\Support\Crossword;

use App\Enums\Direction;

/**
 * Picks the solution word for a laid grid: a candidate that did not make the
 * grid, with one grid square per letter. Marked squares are spread over
 * different entries, so the marking reads as a trail through the puzzle instead
 * of a streak inside one answer.
 *
 * Database-less, AI-less and deterministic: the same grid and candidates always
 * give the same word.
 *
 * @phpstan-type IndexedCell array{row: int, column: int, letter: string, entries: list<int>, penalty: int}
 */
class SolutionWordPicker
{
    /**
     * A ceiling on the backtracking search per word. The expensive case is a
     * word that does not fit at all, which is a supported outcome, so running
     * out of budget is simply "no match" instead of an error. Reached only by
     * grids far larger than an A4 can hold; a normal match needs a few hundred.
     */
    private const int MAX_ASSIGNMENTS = 50_000;

    private int $assignments = 0;

    /**
     * @param  list<array<string, mixed>>  $entries  the placed entries in cropped coordinates
     * @param  list<WordCandidate>  $candidates
     */
    public function pick(array $entries, array $candidates): ?SolutionWord
    {
        $cells = $this->index($entries);
        $remaining = $this->remaining($entries, $candidates);

        // First the whole list with every letter from a different entry; only
        // when nobody fits that way does the cap fall away.
        foreach ([1, null] as $maxPerEntry) {
            foreach ($remaining as $candidate) {
                $match = $this->match($candidate->word, $cells, $maxPerEntry);

                if ($match !== null) {
                    return new SolutionWord($candidate->word, $candidate->clue, $match);
                }
            }
        }

        return null;
    }

    /**
     * Every filled square with its letter, the entries it belongs to and its
     * penalty, in preference order: a plain square before a crossing or a
     * numbered starting square, and a square that is both comes last.
     *
     * @param  list<array<string, mixed>>  $entries
     * @return list<IndexedCell>
     */
    private function index(array $entries): array
    {
        $cells = [];

        foreach ($entries as $index => $entry) {
            foreach (mb_str_split((string) $entry['word']) as $offset => $letter) {
                $row = $entry['row'] + ($entry['direction'] === Direction::Down->value ? $offset : 0);
                $column = $entry['col'] + ($entry['direction'] === Direction::Across->value ? $offset : 0);
                $key = "{$row},{$column}";

                $cells[$key] ??= ['row' => $row, 'column' => $column, 'letter' => $letter, 'entries' => [], 'starts' => false];
                $cells[$key]['entries'][] = $index;
                $cells[$key]['starts'] = $cells[$key]['starts'] || $offset === 0;
            }
        }

        $cells = array_map(fn (array $cell): array => [
            'row' => $cell['row'],
            'column' => $cell['column'],
            'letter' => $cell['letter'],
            'entries' => $cell['entries'],
            'penalty' => (count($cell['entries']) > 1 ? 1 : 0) + ($cell['starts'] ? 1 : 0),
        ], $cells);

        usort($cells, fn (array $a, array $b): int => [$a['penalty'], $a['row'], $a['column']]
            <=> [$b['penalty'], $b['row'], $b['column']]);

        return $cells;
    }

    /**
     * The candidates that did not make the grid, longest first and
     * alphabetically as the tiebreak, so the choice needs no seed.
     *
     * @param  list<array<string, mixed>>  $entries
     * @param  list<WordCandidate>  $candidates
     * @return list<WordCandidate>
     */
    private function remaining(array $entries, array $candidates): array
    {
        $placed = array_column($entries, 'word');

        $remaining = array_values(array_filter(
            $candidates,
            fn (WordCandidate $candidate): bool => $candidate->length() <= SolutionWord::MAX_LENGTH
                && ! in_array($candidate->word, $placed, strict: true),
        ));

        usort($remaining, fn (WordCandidate $a, WordCandidate $b): int => [-$a->length(), $a->word]
            <=> [-$b->length(), $b->word]);

        return $remaining;
    }

    /**
     * One free square per letter, or null when the word does not fit. Rarest
     * letter first and backtracking, so a word is never rejected just because an
     * early letter took the last square a later letter needed.
     *
     * @param  list<IndexedCell>  $cells
     * @return list<SolutionCell>|null
     */
    private function match(string $word, array $cells, ?int $maxPerEntry): ?array
    {
        $options = [];

        foreach (mb_str_split($word) as $position => $letter) {
            $options[$position] = array_keys(array_filter(
                $cells,
                fn (array $cell): bool => $cell['letter'] === $letter,
            ));

            if ($options[$position] === []) {
                return null;
            }
        }

        $order = array_keys($options);
        usort($order, fn (int $a, int $b): int => [count($options[$a]), $a] <=> [count($options[$b]), $b]);

        $assigned = [];
        $used = [];
        $perEntry = [];
        $this->assignments = 0;

        if (! $this->assign($order, $options, $cells, $maxPerEntry, 0, $assigned, $used, $perEntry)) {
            return null;
        }

        ksort($assigned);

        return array_map(
            fn (int $cell): SolutionCell => new SolutionCell($cells[$cell]['row'], $cells[$cell]['column']),
            array_values($assigned),
        );
    }

    /**
     * @param  list<int>  $order
     * @param  array<int, list<int>>  $options
     * @param  list<IndexedCell>  $cells
     * @param  array<int, int>  $assigned
     * @param  array<int, true>  $used
     * @param  array<int, int>  $perEntry
     */
    private function assign(
        array $order,
        array $options,
        array $cells,
        ?int $maxPerEntry,
        int $depth,
        array &$assigned,
        array &$used,
        array &$perEntry,
    ): bool {
        if ($depth === count($order)) {
            return true;
        }

        if (++$this->assignments > self::MAX_ASSIGNMENTS) {
            return false;
        }

        $position = $order[$depth];

        foreach ($options[$position] as $cell) {
            if (isset($used[$cell])) {
                continue;
            }

            // A crossing square belongs to two entries and counts for both.
            $entries = $cells[$cell]['entries'];

            if ($maxPerEntry !== null && $this->exceeds($entries, $perEntry, $maxPerEntry)) {
                continue;
            }

            $used[$cell] = true;
            $assigned[$position] = $cell;

            foreach ($entries as $entry) {
                $perEntry[$entry] = ($perEntry[$entry] ?? 0) + 1;
            }

            if ($this->assign($order, $options, $cells, $maxPerEntry, $depth + 1, $assigned, $used, $perEntry)) {
                return true;
            }

            unset($used[$cell], $assigned[$position]);

            foreach ($entries as $entry) {
                $perEntry[$entry]--;
            }
        }

        return false;
    }

    /**
     * @param  list<int>  $entries
     * @param  array<int, int>  $perEntry
     */
    private function exceeds(array $entries, array $perEntry, int $maxPerEntry): bool
    {
        foreach ($entries as $entry) {
            if (($perEntry[$entry] ?? 0) >= $maxPerEntry) {
                return true;
            }
        }

        return false;
    }
}
