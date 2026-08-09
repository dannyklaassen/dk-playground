<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ClueBand;
use App\Enums\Direction;
use App\Enums\ExerciseStatus;
use App\Enums\PuzzleGroup;
use App\Support\Crossword\LayoutBuilder;
use App\Support\Crossword\SolutionWord;
use App\Support\Crossword\SolutionWordPicker;
use App\Support\Crossword\WordCandidate;
use Database\Factories\CrosswordPuzzleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** Only the three columns the create form fills; everything the generation produces is written with `forceFill()`. */
#[Fillable([
    'group',
    'level',
    'title',
])]
class CrosswordPuzzle extends Model
{
    /** The printable width inside the 2cm A4 margins, in millimetres. */
    private const int PRINTABLE_WIDTH_MM = 170;

    private const int MAX_CELL_SIZE_MM = 12;

    private ?SolutionWord $memoizedSolutionWord = null;

    /** The stored value the memo was built from; false until the first call, which no column value can be. */
    private mixed $memoizedFrom = false;

    /** @use HasFactory<CrosswordPuzzleFactory> */
    use HasFactory;

    use HasUlids;

    /**
     * @return BelongsToMany<ComprehensionExercise, $this>
     */
    public function exercises(): BelongsToMany
    {
        return $this->belongsToMany(ComprehensionExercise::class);
    }

    /**
     * Lays the given candidates out with a fresh seed and stores the result.
     * Returns false without touching the record when fewer than
     * $minimumPlacements words end up on the grid: a bad reroll may not replace
     * a grid that works.
     *
     * $seed is a test seam to pin the reroll to a known grid; no production
     * caller passes it, every reroll gets a fresh one.
     *
     * @param  list<WordCandidate>  $candidates
     * @param  list<string>  $exerciseIds
     */
    public function relayout(array $candidates, array $exerciseIds, int $minimumPlacements, ?int $seed = null): bool
    {
        if ($candidates === []) {
            return false;
        }

        $seed ??= random_int(1, 1_000_000);

        $grid = (new LayoutBuilder)->build($candidates, $this->group->wordCount(), $exerciseIds, $seed);

        if (count($grid->placements()) < $minimumPlacements) {
            return false;
        }

        $entries = $grid->entries();

        $this->forceFill([
            'entries' => $entries,
            // No match is an empty result, not a failure: the puzzle stays valid.
            'solution_word' => (new SolutionWordPicker)->pick($entries, $candidates)?->toArray(),
            'seed' => $seed,
            'grid_rows' => $grid->height(),
            'grid_cols' => $grid->width(),
        ])->save();

        return true;
    }

    /**
     * The entries of one direction, ordered by their number.
     *
     * @return list<array<string, mixed>>
     */
    public function entriesFor(Direction $direction): array
    {
        return collect($this->entries ?? [])
            ->where('direction', $direction->value)
            ->sortBy('number')
            ->values()
            ->all();
    }

    /**
     * The solution word of this puzzle, or null when it has none. Memoized on
     * the stored value: one render asks for it up to six times, a reroll
     * rewrites the column and gets a fresh object.
     */
    public function solutionWord(): ?SolutionWord
    {
        if ($this->memoizedFrom !== $this->solution_word) {
            $this->memoizedFrom = $this->solution_word;
            $this->memoizedSolutionWord = $this->solution_word === null
                ? null
                : SolutionWord::fromArray($this->solution_word);
        }

        return $this->memoizedSolutionWord;
    }

    /**
     * The cropped grid as a rows x cols matrix. Every cell is either null (no
     * cell at all) or an array with the solution letter, the entry number on a
     * starting square, and the label of the solution word on a marked square.
     *
     * @return list<list<array{letter: string, number: int|null, solution: string|null}|null>>
     */
    public function cells(): array
    {
        $grid = array_fill(0, $this->grid_rows ?? 0, array_fill(0, $this->grid_cols ?? 0, null));
        $marked = $this->markedCells();

        foreach ($this->entries ?? [] as $entry) {
            $letters = mb_str_split($entry['word']);

            foreach ($letters as $index => $letter) {
                $row = $entry['row'] + ($entry['direction'] === Direction::Down->value ? $index : 0);
                $column = $entry['col'] + ($entry['direction'] === Direction::Across->value ? $index : 0);

                $grid[$row][$column] = [
                    'letter' => $letter,
                    'number' => $index === 0 ? $entry['number'] : ($grid[$row][$column]['number'] ?? null),
                    'solution' => $marked["{$row},{$column}"] ?? null,
                ];
            }
        }

        return $grid;
    }

    /** The printed cell size in millimetres, scaled down for wide grids. */
    public function cellSizeMm(): float
    {
        $columns = max(1, $this->grid_cols ?? 1);

        return round(min(self::MAX_CELL_SIZE_MM, self::PRINTABLE_WIDTH_MM / $columns), 1);
    }

    /**
     * The label of every marked square, keyed by "row,column".
     *
     * @return array<string, string>
     */
    private function markedCells(): array
    {
        $marked = [];

        foreach ($this->solutionWord()?->cells ?? [] as $index => $cell) {
            $marked["{$cell->row},{$cell->column}"] = SolutionWord::label($index);
        }

        return $marked;
    }

    #[\Override]
    protected function casts(): array
    {
        return [
            'group' => PuzzleGroup::class,
            'level' => 'integer',
            'seed' => 'integer',
            'grid_rows' => 'integer',
            'grid_cols' => 'integer',
            'candidates' => 'array',
            'entries' => 'array',
            'solution_word' => 'array',
            'generated_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    /**
     * @return Attribute<ExerciseStatus, never>
     */
    protected function status(): Attribute
    {
        return Attribute::get(fn (): ExerciseStatus => match (true) {
            $this->generated_at !== null => ExerciseStatus::Generated,
            $this->failed_at !== null => ExerciseStatus::Failed,
            default => ExerciseStatus::Pending,
        });
    }

    /**
     * @return Attribute<ClueBand, never>
     */
    protected function clueBand(): Attribute
    {
        return Attribute::get(fn (): ClueBand => ClueBand::forLevel($this->level));
    }
}
