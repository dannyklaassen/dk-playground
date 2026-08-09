<?php

declare(strict_types=1);

namespace App\Filament\Resources\CrosswordPuzzles\Pages;

use App\Filament\Resources\CrosswordPuzzles\Actions\OpenAnswerSheetAction;
use App\Filament\Resources\CrosswordPuzzles\Actions\OpenWorksheetAction;
use App\Filament\Resources\CrosswordPuzzles\Actions\RegenerateCrosswordPuzzleAction;
use App\Filament\Resources\CrosswordPuzzles\Actions\RelayoutCrosswordPuzzleAction;
use App\Filament\Resources\CrosswordPuzzles\CrosswordPuzzleResource;
use App\Filament\Resources\CrosswordPuzzles\Schemas\CrosswordPuzzleForm;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class ViewCrosswordPuzzle extends ViewRecord
{
    protected static string $resource = CrosswordPuzzleResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                EditAction::make()
                    ->slideOver()
                    ->modalWidth(Width::Medium)
                    ->schema(CrosswordPuzzleForm::editSchema()),
                OpenWorksheetAction::make(),
                OpenAnswerSheetAction::make(),
                RelayoutCrosswordPuzzleAction::make(),
                RegenerateCrosswordPuzzleAction::make(),
                DeleteAction::make(),
            ])->icon(Heroicon::EllipsisVertical),
        ];
    }
}
