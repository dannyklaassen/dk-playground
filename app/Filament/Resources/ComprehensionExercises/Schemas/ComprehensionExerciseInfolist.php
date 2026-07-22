<?php

declare(strict_types=1);

namespace App\Filament\Resources\ComprehensionExercises\Schemas;

use App\Enums\ExerciseStatus;
use App\Enums\ReadingSkill;
use App\Models\ComprehensionExercise;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\HtmlString;

class ComprehensionExerciseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.comprehension_exercise.sections.status'))
                    ->columnSpan(2)
                    ->schema([
                        TextEntry::make('status_message')
                            ->hiddenLabel()
                            ->state(fn (ComprehensionExercise $record): string => $record->status === ExerciseStatus::Failed
                                ? __('admin.comprehension_exercise.status_messages.failed')
                                : __('admin.comprehension_exercise.status_messages.pending')),
                    ])
                    ->visible(fn (ComprehensionExercise $record): bool => $record->status !== ExerciseStatus::Generated),
                Section::make(__('admin.comprehension_exercise.sections.text'))
                    ->columnSpan(2)
                    ->afterHeader([
                        TextEntry::make('status')
                            ->hiddenLabel()
                            ->badge()
                            ->state(fn (ComprehensionExercise $record): ExerciseStatus => $record->status),
                    ])
                    ->schema([
                        TextEntry::make('title')
                            ->hiddenLabel()
                            ->weight(FontWeight::Bold),
                        TextEntry::make('paragraphs')
                            ->hiddenLabel()
                            ->state(fn (ComprehensionExercise $record): HtmlString => new HtmlString(
                                collect($record->paragraphs ?? [])
                                    ->map(fn (string $paragraph, int $index): string => sprintf(
                                        '<p style="position: relative; padding-left: 2rem; margin: 0 0 1rem; line-height: 1.7; max-width: 65ch;">'
                                        .'<span style="position: absolute; left: 0; top: .2rem; font-size: .75rem; color: #9ca3af;">%d</span>%s</p>',
                                        $index + 1,
                                        e($paragraph),
                                    ))
                                    ->implode(''),
                            )),
                    ])
                    ->visible(fn (ComprehensionExercise $record): bool => $record->status === ExerciseStatus::Generated),
                Section::make(__('admin.comprehension_exercise.sections.details'))
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('topic')
                            ->label(__('admin.comprehension_exercise.fields.topic')),
                        TextEntry::make('description')
                            ->label(__('admin.comprehension_exercise.fields.description')),
                        TextEntry::make('reading_level')
                            ->label(__('admin.comprehension_exercise.fields.reading_level')),
                        TextEntry::make('level')
                            ->label(__('admin.comprehension_exercise.fields.level'))
                            ->helperText(fn (ComprehensionExercise $record): string => $record->level_band->getDescription()),
                    ]),
                Section::make(__('admin.comprehension_exercise.sections.questions'))
                    ->columnSpanFull()
                    ->collapsible()
                    ->schema([
                        RepeatableEntry::make('questions')
                            ->hiddenLabel()
                            ->grid(2)
                            ->schema([
                                TextEntry::make('question')
                                    ->hiddenLabel()
                                    ->weight(FontWeight::Bold)
                                    ->aboveContent(self::questionLabel(...)),
                                TextEntry::make('options.A')->hiddenLabel()
                                    ->formatStateUsing(fn (string $state): string => 'A. '.$state),
                                TextEntry::make('options.B')->hiddenLabel()
                                    ->formatStateUsing(fn (string $state): string => 'B. '.$state),
                                TextEntry::make('options.C')->hiddenLabel()
                                    ->formatStateUsing(fn (string $state): string => 'C. '.$state),
                                TextEntry::make('options.D')->hiddenLabel()
                                    ->formatStateUsing(fn (string $state): string => 'D. '.$state),
                            ]),
                    ])
                    ->visible(fn (ComprehensionExercise $record): bool => $record->status === ExerciseStatus::Generated),
                Section::make(__('admin.comprehension_exercise.sections.answer_sheet'))
                    ->columnSpanFull()
                    ->collapsible()
                    ->schema([
                        RepeatableEntry::make('questions')
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make(__('admin.comprehension_exercise.fields.question')),
                                TableColumn::make(__('admin.comprehension_exercise.fields.answer')),
                                TableColumn::make(__('admin.comprehension_exercise.fields.paragraph')),
                                TableColumn::make(__('admin.comprehension_exercise.fields.evidence')),
                                TableColumn::make(__('admin.comprehension_exercise.fields.skill')),
                            ])
                            ->schema([
                                TextEntry::make('question')
                                    ->hiddenLabel()
                                    ->state(self::questionNumber(...)),
                                TextEntry::make('answer.choice')->hiddenLabel(),
                                TextEntry::make('answer.paragraph')->hiddenLabel(),
                                TextEntry::make('answer.evidence')
                                    ->hiddenLabel()
                                    ->placeholder(__('common.not_applicable')),
                                TextEntry::make('skill')
                                    ->hiddenLabel()
                                    ->formatStateUsing(fn (string $state): string => ReadingSkill::from($state)->getLabel()),
                            ]),
                    ])
                    ->visible(fn (ComprehensionExercise $record): bool => $record->status === ExerciseStatus::Generated),
            ])
            ->columns(3);
    }

    private static function questionNumber(TextEntry $component): ?int
    {
        preg_match('/questions\.(\d+)\./', $component->getStatePath(), $matches);

        return isset($matches[1]) ? $matches[1] + 1 : null;
    }

    private static function questionLabel(TextEntry $component): ?string
    {
        $number = self::questionNumber($component);

        return $number === null
            ? null
            : __('admin.comprehension_exercise.fields.question').' '.$number;
    }
}
