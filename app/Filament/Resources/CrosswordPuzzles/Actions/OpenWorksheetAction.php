<?php

declare(strict_types=1);

namespace App\Filament\Resources\CrosswordPuzzles\Actions;

use App\Enums\ExerciseStatus;
use App\Models\CrosswordPuzzle;
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

        $this->label(__('admin.crossword_puzzle.actions.worksheet'))
            ->icon(Heroicon::Printer)
            ->visible(fn (CrosswordPuzzle $record): bool => $record->status === ExerciseStatus::Generated)
            ->url(fn (CrosswordPuzzle $record): string => route('crossword-puzzles.worksheet', $record))
            ->openUrlInNewTab();
    }
}
