<?php

declare(strict_types=1);

namespace App\Filament\Resources\ComprehensionExercises\Tables;

use App\Enums\ExerciseStatus;
use App\Filament\Resources\ComprehensionExercises\Actions\RegenerateExerciseAction;
use App\Models\ComprehensionExercise;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ComprehensionExercisesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('topic')
                    ->label(__('admin.comprehension_exercise.fields.topic'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('reading_level')
                    ->label(__('admin.comprehension_exercise.fields.reading_level'))
                    ->sortable(),
                TextColumn::make('level')
                    ->label(__('admin.comprehension_exercise.fields.level'))
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('admin.comprehension_exercise.columns.status'))
                    ->badge()
                    ->state(fn (ComprehensionExercise $record): ExerciseStatus => $record->status)
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderByRaw(
                        "case when generated_at is not null then 1 when failed_at is not null then 2 else 0 end {$direction}",
                    )),
                TextColumn::make('created_at')
                    ->label(__('admin.comprehension_exercise.columns.created_at'))
                    ->dateTime('d-m-Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll(fn (): ?string => ComprehensionExercise::query()
                ->whereNull('generated_at')
                ->whereNull('failed_at')
                ->exists() ? '5s' : null)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    RegenerateExerciseAction::make(),
                    DeleteAction::make(),
                ])->icon(Heroicon::EllipsisVertical),
            ]);
    }
}
