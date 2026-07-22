<?php

declare(strict_types=1);

namespace App\Filament\Resources\ComprehensionExercises\Pages;

use App\Filament\Resources\ComprehensionExercises\Actions\OpenAnswerSheetAction;
use App\Filament\Resources\ComprehensionExercises\Actions\OpenWorksheetAction;
use App\Filament\Resources\ComprehensionExercises\Actions\RegenerateExerciseAction;
use App\Filament\Resources\ComprehensionExercises\ComprehensionExerciseResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewComprehensionExercise extends ViewRecord
{
    protected static string $resource = ComprehensionExerciseResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            OpenWorksheetAction::make(),
            OpenAnswerSheetAction::make(),
            ActionGroup::make([
                RegenerateExerciseAction::make(),
                DeleteAction::make(),
            ])->icon(Heroicon::EllipsisVertical),
        ];
    }
}
