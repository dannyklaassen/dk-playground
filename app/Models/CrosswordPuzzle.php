<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ClueBand;
use App\Enums\Direction;
use App\Enums\ExerciseStatus;
use App\Enums\PuzzleGroup;
use App\Support\Crossword\LayoutBuilder;
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
     * @param  list<WordCandidate>  $candidates
     * @param  list<string>  $exerciseIds
     */
    public function relayout(array $candidates, array $exerciseIds, int $minimumPlacements): bool
    {
        if ($candidates === []) {
            return false;
        }

        $seed = random_int(1, 1_000_000);

        $grid = (new LayoutBuilder)->build($candidates, $this->group->wordCount(), $exerciseIds, $seed);

        if (count($grid->placements()) < $minimumPlacements) {
            return false;
        }

        $this->forceFill([
            'entries' => $grid->entries(),
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
     * The cropped grid as a rows x cols matrix. Every cell is either null (no
     * cell at all) or an array with the solution letter and, on a starting
     * square, the entry number.
     *
     * @return list<list<array{letter: string, number: int|null}|null>>
     */
    public function cells(): array
    {
        $grid = array_fill(0, $this->grid_rows ?? 0, array_fill(0, $this->grid_cols ?? 0, null));

        foreach ($this->entries ?? [] as $entry) {
            $letters = mb_str_split($entry['word']);

            foreach ($letters as $index => $letter) {
                $row = $entry['row'] + ($entry['direction'] === Direction::Down->value ? $index : 0);
                $column = $entry['col'] + ($entry['direction'] === Direction::Across->value ? $index : 0);

                $grid[$row][$column] = [
                    'letter' => $letter,
                    'number' => $index === 0 ? $entry['number'] : ($grid[$row][$column]['number'] ?? null),
                ];
            }
        }

        return $grid;
    }

    /**
     * From level 21 up the clue points back into the text, so the worksheet has
     * to tell the child which text to look in.
     */
    public function showsSource(): bool
    {
        return $this->level >= 21;
    }

    /** The printed cell size in millimetres, scaled down for wide grids. */
    public function cellSizeMm(): float
    {
        $columns = max(1, $this->grid_cols ?? 1);

        return round(min(self::MAX_CELL_SIZE_MM, self::PRINTABLE_WIDTH_MM / $columns), 1);
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
