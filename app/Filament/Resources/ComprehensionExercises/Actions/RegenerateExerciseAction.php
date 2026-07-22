<?php

declare(strict_types=1);

namespace App\Filament\Resources\ComprehensionExercises\Actions;

use App\Enums\ExerciseStatus;
use App\Jobs\GenerateComprehensionExercise;
use App\Models\ComprehensionExercise;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class RegenerateExerciseAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'regenerate';
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('admin.comprehension_exercise.actions.regenerate'))
            ->icon(Heroicon::ArrowPath)
            ->visible(fn (ComprehensionExercise $record): bool => $record->status === ExerciseStatus::Failed)
            ->action(function (ComprehensionExercise $record): void {
                $record->update(['failed_at' => null]);

                dispatch(new GenerateComprehensionExercise($record));

                Notification::make()
                    ->title(__('admin.comprehension_exercise.notifications.regeneration_started'))
                    ->success()
                    ->send();
            });
    }
}
