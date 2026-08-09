<?php

declare(strict_types=1);

namespace App\Filament\Resources\CrosswordPuzzles\Schemas;

use App\Enums\Direction;
use App\Enums\ExerciseStatus;
use App\Filament\Resources\ComprehensionExercises\ComprehensionExerciseResource;
use App\Models\ComprehensionExercise;
use App\Models\CrosswordPuzzle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class CrosswordPuzzleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.crossword_puzzle.sections.status'))
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('status_message')
                            ->hiddenLabel()
                            ->state(fn (CrosswordPuzzle $record): string => $record->status === ExerciseStatus::Failed
                                ? __('admin.crossword_puzzle.status_messages.failed')
                                : __('admin.crossword_puzzle.status_messages.pending')),
                    ])
                    ->visible(fn (CrosswordPuzzle $record): bool => $record->status !== ExerciseStatus::Generated),
                Section::make(__('admin.crossword_puzzle.sections.grid'))
                    ->columnSpan(2)
                    ->afterHeader([
                        TextEntry::make('status')
                            ->hiddenLabel()
                            ->badge()
                            ->state(fn (CrosswordPuzzle $record): ExerciseStatus => $record->status),
                    ])
                    ->schema([
                        TextEntry::make('grid')
                            ->hiddenLabel()
                            ->state(fn (CrosswordPuzzle $record): HtmlString => self::grid($record, solved: false)),
                    ])
                    ->visible(fn (CrosswordPuzzle $record): bool => $record->status === ExerciseStatus::Generated),
                Section::make(__('admin.crossword_puzzle.sections.details'))
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('title')
                            ->label(__('admin.crossword_puzzle.fields.title')),
                        TextEntry::make('group')
                            ->label(__('admin.crossword_puzzle.fields.group')),
                        TextEntry::make('level')
                            ->label(__('admin.crossword_puzzle.fields.level'))
                            ->helperText(fn (CrosswordPuzzle $record): string => $record->clue_band->getDescription()),
                        TextEntry::make('solution_word')
                            ->label(__('admin.crossword_puzzle.fields.solution_word'))
                            ->state(fn (CrosswordPuzzle $record): ?string => $record->solutionWord()?->word)
                            ->helperText(fn (CrosswordPuzzle $record): ?string => $record->solutionWord()?->clue)
                            ->hidden(fn (CrosswordPuzzle $record): bool => $record->solution_word === null),
                        TextEntry::make('exercises')
                            ->label(__('admin.crossword_puzzle.fields.exercises'))
                            ->listWithLineBreaks()
                            ->bulleted()
                            ->state(fn (CrosswordPuzzle $record): array => $record->exercises->pluck('id')->all())
                            ->formatStateUsing(fn (string $state, CrosswordPuzzle $record): string => (string) $record
                                ->exercises
                                ->firstWhere('id', $state)
                                ?->title)
                            ->url(self::exerciseUrl(...)),
                    ]),
                Section::make(__('admin.crossword_puzzle.sections.clues'))
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('across')
                                ->label(__('admin.crossword_puzzle.sections.across'))
                                ->state(fn (CrosswordPuzzle $record): HtmlString => self::clueList($record, Direction::Across)),
                            TextEntry::make('down')
                                ->label(__('admin.crossword_puzzle.sections.down'))
                                ->state(fn (CrosswordPuzzle $record): HtmlString => self::clueList($record, Direction::Down)),
                        ]),
                    ])
                    ->visible(fn (CrosswordPuzzle $record): bool => $record->status === ExerciseStatus::Generated),
                Section::make(__('admin.crossword_puzzle.sections.answer_sheet'))
                    ->columnSpanFull()
                    ->collapsible()
                    ->schema([
                        TextEntry::make('solution')
                            ->hiddenLabel()
                            ->state(fn (CrosswordPuzzle $record): HtmlString => self::grid($record, solved: true)),
                    ])
                    ->visible(fn (CrosswordPuzzle $record): bool => $record->status === ExerciseStatus::Generated),
            ])
            ->columns(3);
    }

    private static function grid(CrosswordPuzzle $puzzle, bool $solved): HtmlString
    {
        return new HtmlString(
            view('crossword-puzzles.partials.grid', ['puzzle' => $puzzle, 'solved' => $solved])->render(),
        );
    }

    private static function exerciseUrl(string $state): string
    {
        return ComprehensionExerciseResource::getUrl('view', ['record' => $state]);
    }

    /**
     * One clue per block: the number and the clue on the first line, the source
     * text underneath, so a long clue does not run into its source.
     */
    private static function clueList(CrosswordPuzzle $puzzle, Direction $direction): HtmlString
    {
        $exercises = $puzzle->exercises->keyBy('id');

        $blocks = array_map(function (array $entry) use ($exercises): string {
            $source = $exercises->get($entry['exercise_id']);

            $block = sprintf(
                '<div style="margin-bottom: .75rem; line-height: 1.5;"><div><strong>%d.</strong> %s</div>',
                $entry['number'],
                e($entry['clue']),
            );

            if ($source instanceof ComprehensionExercise) {
                $block .= sprintf(
                    '<a href="%s" style="display: block; font-size: .75rem; color: #6b7280; text-decoration: underline;">%s</a>',
                    e(self::exerciseUrl($source->id)),
                    e($source->title),
                );
            }

            return $block.'</div>';
        }, $puzzle->entriesFor($direction));

        return new HtmlString(implode('', $blocks));
    }
}
