<?php

declare(strict_types=1);

namespace App\Filament\Resources\CrosswordPuzzles\Actions;

use App\Enums\ExerciseStatus;
use App\Jobs\GenerateCrosswordPuzzle;
use App\Models\CrosswordPuzzle;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class RegenerateCrosswordPuzzleAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'regenerate';
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('admin.crossword_puzzle.actions.regenerate'))
            ->icon(Heroicon::ArrowPath)
            // A rescue, not a reroll: a puzzle that came out fine has nothing to
            // gain from six more paid agent calls. A puzzle stuck on "pending"
            // (job died without `failed()` running) does need this button, or
            // there is no way back at all.
            ->visible(fn (CrosswordPuzzle $record): bool => $record->status !== ExerciseStatus::Generated)
            ->requiresConfirmation()
            // Every run costs six paid agent calls, so a repeated click may not
            // keep queueing work.
            ->rateLimit(3)
            ->action(function (CrosswordPuzzle $record): void {
                $record->forceFill([
                    'generated_at' => null,
                    'failed_at' => null,
                ])->save();

                dispatch(new GenerateCrosswordPuzzle($record));

                Notification::make()
                    ->title(__('admin.crossword_puzzle.notifications.regeneration_started'))
                    ->success()
                    ->send();
            });
    }
}
