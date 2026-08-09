<?php

declare(strict_types=1);

namespace App\Filament\Resources\CrosswordPuzzles\Schemas;

use App\Enums\ClueBand;
use App\Enums\PuzzleGroup;
use App\Models\ComprehensionExercise;
use App\Models\CrosswordPuzzle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Slider;
use Filament\Forms\Components\Slider\Enums\PipsMode;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rule;

class CrosswordPuzzleForm
{
    private const int MIN_EXERCISES = 3;

    private const int MAX_EXERCISES = 6;

    /** How many texts the dropdown shows up front; the rest is reachable by searching. */
    private const int PRELOADED_EXERCISES = 20;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('common.sections.general'))
                    ->schema([
                        self::titleField(),
                        Select::make('exercises')
                            ->label(__('admin.crossword_puzzle.fields.exercises'))
                            ->helperText(__('admin.crossword_puzzle.help.exercises'))
                            // The newest text first. Filament only falls back to ordering by the
                            // title column when there is no option-label callback, and there is
                            // one here, so the order has to be set explicitly.
                            ->relationship(
                                'exercises',
                                'title',
                                fn (Builder $query): Builder => $query->whereNotNull('generated_at')->latest(),
                            )
                            ->getOptionLabelFromRecordUsing(self::exerciseLabel(...))
                            ->allowHtml()
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->optionsLimit(self::PRELOADED_EXERCISES)
                            ->minItems(self::MIN_EXERCISES)
                            ->maxItems(self::MAX_EXERCISES)
                            // The query above only scopes the options that are offered;
                            // what is submitted has to be checked separately.
                            ->nestedRecursiveRules([
                                Rule::exists('comprehension_exercises', 'id')->whereNotNull('generated_at'),
                            ])
                            ->required(),
                        Select::make('group')
                            ->label(__('admin.crossword_puzzle.fields.group'))
                            ->options(PuzzleGroup::class)
                            ->default(fn (): ?PuzzleGroup => CrosswordPuzzle::query()
                                ->latest()
                                ->value('group'))
                            ->required(),
                        Slider::make('level')
                            ->label(__('admin.crossword_puzzle.fields.level'))
                            ->range(1, 50)
                            ->step(1)
                            ->decimalPlaces(0)
                            ->tooltips()
                            ->pips(PipsMode::Values, density: 2)
                            ->pipsValues([1, 10, 20, 30, 40, 50])
                            ->default(fn (): int => CrosswordPuzzle::query()
                                ->latest()
                                ->value('level') ?? 1)
                            ->live()
                            ->helperText(self::clueBandDescription(...))
                            ->required(),
                    ]),
            ])
            ->columns(1);
    }

    /**
     * The edit form. The texts, the group and the level all feed the generation,
     * so changing them afterwards would leave the stored puzzle out of sync with
     * its own input. Only the name is safe to change.
     *
     * @return array<int, TextInput>
     */
    public static function editSchema(): array
    {
        return [self::titleField()];
    }

    private static function titleField(): TextInput
    {
        return TextInput::make('title')
            ->label(__('admin.crossword_puzzle.fields.title'))
            ->maxLength(255);
    }

    /**
     * The title with the creation date underneath it, the select equivalent of
     * a table column description. Both halves are escaped: the option is
     * rendered as HTML.
     */
    private static function exerciseLabel(ComprehensionExercise $record): string
    {
        return sprintf(
            '%s<br><span style="font-size: .75rem; opacity: .65;">%s</span>',
            e($record->title),
            e($record->created_at->format('d-m-Y')),
        );
    }

    private static function clueBandDescription(Get $get): ?HtmlString
    {
        $level = filter_var($get('level'), FILTER_VALIDATE_INT);

        if ($level === false || $level < 1 || $level > 50) {
            return null;
        }

        $description = e(ClueBand::forLevel($level)->getDescription());

        return new HtmlString("<span style=\"display: block; margin-top: 1rem;\">{$description}</span>");
    }
}
