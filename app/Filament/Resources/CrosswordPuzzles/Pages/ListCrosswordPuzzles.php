<?php

declare(strict_types=1);

namespace App\Filament\Resources\CrosswordPuzzles\Pages;

use App\Filament\Resources\CrosswordPuzzles\CrosswordPuzzleResource;
use App\Jobs\GenerateCrosswordPuzzle;
use App\Models\CrosswordPuzzle;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListCrosswordPuzzles extends ListRecords
{
    protected static string $resource = CrosswordPuzzleResource::class;

    #[\Override]
    public function getTitle(): string
    {
        return __('admin.crossword_puzzle.navigation');
    }

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->slideOver()
                ->modalWidth(Width::Medium)
                ->after(function (CrosswordPuzzle $record): void {
                    dispatch(new GenerateCrosswordPuzzle($record));
                }),
        ];
    }
}
