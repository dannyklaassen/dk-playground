<?php

declare(strict_types=1);

namespace App\Filament\Resources\CrosswordPuzzles;

use App\Filament\Resources\CrosswordPuzzles\Pages\ListCrosswordPuzzles;
use App\Filament\Resources\CrosswordPuzzles\Pages\ViewCrosswordPuzzle;
use App\Filament\Resources\CrosswordPuzzles\Schemas\CrosswordPuzzleForm;
use App\Filament\Resources\CrosswordPuzzles\Schemas\CrosswordPuzzleInfolist;
use App\Filament\Resources\CrosswordPuzzles\Tables\CrosswordPuzzlesTable;
use App\Models\CrosswordPuzzle;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CrosswordPuzzleResource extends Resource
{
    protected static ?string $model = CrosswordPuzzle::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    #[\Override]
    public static function getNavigationLabel(): string
    {
        return __('admin.crossword_puzzle.navigation');
    }

    #[\Override]
    public static function getBreadcrumb(): string
    {
        return __('admin.crossword_puzzle.navigation');
    }

    #[\Override]
    public static function getModelLabel(): string
    {
        return __('admin.crossword_puzzle.singular');
    }

    #[\Override]
    public static function getPluralModelLabel(): string
    {
        return __('admin.crossword_puzzle.plural');
    }

    #[\Override]
    public static function form(Schema $schema): Schema
    {
        return CrosswordPuzzleForm::configure($schema);
    }

    #[\Override]
    public static function infolist(Schema $schema): Schema
    {
        return CrosswordPuzzleInfolist::configure($schema);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return CrosswordPuzzlesTable::configure($table);
    }

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => ListCrosswordPuzzles::route('/'),
            'view' => ViewCrosswordPuzzle::route('/{record}'),
        ];
    }
}
