<?php

declare(strict_types=1);

namespace App\Filament\Resources\ComprehensionExercises\Actions;

use App\Enums\ExerciseStatus;
use App\Models\ComprehensionExercise;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class OpenWorksheetAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'worksheet';
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('admin.comprehension_exercise.actions.worksheet'))
            ->icon(Heroicon::Printer)
            ->visible(fn (ComprehensionExercise $record): bool => $record->status === ExerciseStatus::Generated)
            ->url(fn (ComprehensionExercise $record): string => route('comprehension-exercises.worksheet', $record))
            ->openUrlInNewTab();
    }
}
