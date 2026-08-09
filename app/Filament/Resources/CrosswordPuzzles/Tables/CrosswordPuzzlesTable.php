<?php

declare(strict_types=1);

namespace App\Filament\Resources\CrosswordPuzzles\Tables;

use App\Enums\ExerciseStatus;
use App\Filament\Resources\CrosswordPuzzles\Actions\OpenAnswerSheetAction;
use App\Filament\Resources\CrosswordPuzzles\Actions\OpenWorksheetAction;
use App\Filament\Resources\CrosswordPuzzles\Actions\RegenerateCrosswordPuzzleAction;
use App\Filament\Resources\CrosswordPuzzles\Actions\RelayoutCrosswordPuzzleAction;
use App\Filament\Resources\CrosswordPuzzles\Schemas\CrosswordPuzzleForm;
use App\Models\CrosswordPuzzle;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CrosswordPuzzlesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('admin.crossword_puzzle.fields.title'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('group')
                    ->label(__('admin.crossword_puzzle.fields.group'))
                    ->sortable(),
                TextColumn::make('level')
                    ->label(__('admin.crossword_puzzle.fields.level'))
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('admin.crossword_puzzle.columns.status'))
                    ->badge()
                    ->state(fn (CrosswordPuzzle $record): ExerciseStatus => $record->status)
                    // The direction goes into raw SQL, so it is narrowed to the two
                    // values it may ever be instead of trusting the caller.
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderByRaw(
                        'case when generated_at is not null then 1 when failed_at is not null then 2 else 0 end '
                        .($direction === 'desc' ? 'desc' : 'asc'),
                    )),
                TextColumn::make('created_at')
                    ->label(__('admin.crossword_puzzle.columns.created_at'))
                    ->dateTime('d-m-Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll(fn (): ?string => CrosswordPuzzle::query()
                ->whereNull('generated_at')
                ->whereNull('failed_at')
                ->exists() ? '5s' : null)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
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
            ]);
    }
}
