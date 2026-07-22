<?php

declare(strict_types=1);

namespace App\Filament\Resources\ComprehensionExercises\Actions;

use App\Enums\ExerciseStatus;
use App\Models\ComprehensionExercise;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class OpenAnswerSheetAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'answerSheet';
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('admin.comprehension_exercise.actions.answer_sheet'))
            ->icon(Heroicon::ClipboardDocumentCheck)
            ->color('gray')
            ->visible(fn (ComprehensionExercise $record): bool => $record->status === ExerciseStatus::Generated)
            ->url(fn (ComprehensionExercise $record): string => route('comprehension-exercises.answer-sheet', $record))
            ->openUrlInNewTab();
    }
}
