<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Ai\Agents\CrosswordWordWriter;
use App\Models\ComprehensionExercise;
use App\Models\CrosswordPuzzle;
use App\Support\Crossword\CandidateDeduplicator;
use App\Support\Crossword\CandidateValidator;
use App\Support\Crossword\WordCandidate;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateCrosswordPuzzle implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /** Room for six sequential agent calls. */
    public int $timeout = 900;

    /** One generation per puzzle at a time: every run costs six paid agent calls. */
    public int $uniqueFor = 900;

    public function __construct(public CrosswordPuzzle $puzzle) {}

    public function uniqueId(): string
    {
        return $this->puzzle->id;
    }

    public function handle(): void
    {
        $group = $this->puzzle->group;
        $exercises = $this->puzzle->exercises;
        $validator = new CandidateValidator;
        $candidates = [];

        foreach ($exercises as $exercise) {
            $response = (new CrosswordWordWriter)->prompt(
                CrosswordWordWriter::promptFor($exercise, $group, $this->puzzle->clue_band),
                model: config('ai.providers.anthropic.model'),
            );

            $candidates = [
                ...$candidates,
                ...$validator->validate(
                    $response->toArray()['words'] ?? [],
                    $this->sourceTextOf($exercise),
                    $group,
                    $exercise->id,
                ),
            ];
        }

        $candidates = (new CandidateDeduplicator)->deduplicate($candidates);
        $minimum = intdiv($group->wordCount(), 2);

        if (count($candidates) < $minimum) {
            $this->reject('too few usable candidates', ['candidates' => count($candidates), 'minimum' => $minimum]);

            return;
        }

        if (! $this->puzzle->relayout($candidates, $exercises->pluck('id')->all(), $minimum)) {
            $this->reject('too few words could be placed', ['minimum' => $minimum]);

            return;
        }

        $this->puzzle->forceFill([
            'candidates' => array_map(fn (WordCandidate $candidate): array => $candidate->toArray(), $candidates),
            'generated_at' => now(),
            'failed_at' => null,
        ])->save();
    }

    public function failed(?Throwable $exception): void
    {
        $this->puzzle->forceFill(['failed_at' => now()])->save();
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function reject(string $reason, array $context): void
    {
        Log::warning('Crossword puzzle generation rejected', [
            'puzzle_id' => $this->puzzle->id,
            'reason' => $reason,
            ...$context,
        ]);

        $this->puzzle->forceFill(['failed_at' => now()])->save();
    }

    private function sourceTextOf(ComprehensionExercise $exercise): string
    {
        return $exercise->title.' '.implode(' ', $exercise->paragraphs ?? []);
    }
}
