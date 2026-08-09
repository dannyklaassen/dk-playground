<?php

declare(strict_types=1);

namespace App\Filament\Resources\CrosswordPuzzles\Actions;

use App\Enums\ExerciseStatus;
use App\Models\CrosswordPuzzle;
use App\Support\Crossword\WordCandidate;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/**
 * Rerolls the grid from the stored candidates. Free and instant: no AI call,
 * and the words and clues stay exactly as they were.
 */
class RelayoutCrosswordPuzzleAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'relayout';
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('admin.crossword_puzzle.actions.relayout'))
            ->icon(Heroicon::ArrowsPointingOut)
            // Only a laid grid can be reshuffled; a pending or failed puzzle has
            // no candidates to work with.
            ->visible(fn (CrosswordPuzzle $record): bool => $record->status === ExerciseStatus::Generated)
            // Laying the grid and matching the solution word both run inside the
            // request, so a held-down button may not tie up a row of workers.
            ->rateLimit(10)
            ->action(function (CrosswordPuzzle $record): void {
                $candidates = array_map(
                    WordCandidate::fromArray(...),
                    $record->candidates ?? [],
                );

                $relaid = $record->relayout(
                    $candidates,
                    $record->exercises()->pluck('comprehension_exercises.id')->all(),
                    // A reroll may not put fewer words on the grid than are on it now.
                    count($record->entries ?? []),
                );

                if (! $relaid) {
                    Notification::make()
                        ->title(__('admin.crossword_puzzle.notifications.relayout_failed'))
                        ->warning()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title(__('admin.crossword_puzzle.notifications.relaid'))
                    ->success()
                    ->send();
            });
    }
}
