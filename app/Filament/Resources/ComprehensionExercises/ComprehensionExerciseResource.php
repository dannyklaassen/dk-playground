<?php

declare(strict_types=1);

namespace App\Filament\Resources\ComprehensionExercises;

use App\Filament\Resources\ComprehensionExercises\Pages\ListComprehensionExercises;
use App\Filament\Resources\ComprehensionExercises\Pages\ViewComprehensionExercise;
use App\Filament\Resources\ComprehensionExercises\Schemas\ComprehensionExerciseForm;
use App\Filament\Resources\ComprehensionExercises\Schemas\ComprehensionExerciseInfolist;
use App\Filament\Resources\ComprehensionExercises\Tables\ComprehensionExercisesTable;
use App\Models\ComprehensionExercise;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ComprehensionExerciseResource extends Resource
{
    protected static ?string $model = ComprehensionExercise::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    #[\Override]
    public static function getNavigationLabel(): string
    {
        return __('admin.comprehension_exercise.navigation');
    }

    #[\Override]
    public static function getBreadcrumb(): string
    {
        return __('admin.comprehension_exercise.navigation');
    }

    #[\Override]
    public static function getModelLabel(): string
    {
        return __('admin.comprehension_exercise.singular');
    }

    #[\Override]
    public static function getPluralModelLabel(): string
    {
        return __('admin.comprehension_exercise.plural');
    }

    #[\Override]
    public static function form(Schema $schema): Schema
    {
        return ComprehensionExerciseForm::configure($schema);
    }

    #[\Override]
    public static function infolist(Schema $schema): Schema
    {
        return ComprehensionExerciseInfolist::configure($schema);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return ComprehensionExercisesTable::configure($table);
    }

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => ListComprehensionExercises::route('/'),
            'view' => ViewComprehensionExercise::route('/{record}'),
        ];
    }
}
