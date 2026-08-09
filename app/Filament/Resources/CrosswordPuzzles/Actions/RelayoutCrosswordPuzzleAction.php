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
            ->visible(fn (CrosswordPuzzle $record): bool => $record->status === ExerciseStatus::Generated)
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
