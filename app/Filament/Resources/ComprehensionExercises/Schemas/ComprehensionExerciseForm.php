<?php

declare(strict_types=1);

namespace App\Filament\Resources\ComprehensionExercises\Schemas;

use App\Enums\LevelBand;
use App\Enums\ReadingLevel;
use App\Models\ComprehensionExercise;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Slider;
use Filament\Forms\Components\Slider\Enums\PipsMode;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class ComprehensionExerciseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('reading_level')
                    ->label(__('admin.comprehension_exercise.fields.reading_level'))
                    ->options(ReadingLevel::class)
                    ->default(fn (): ?ReadingLevel => ComprehensionExercise::query()
                        ->latest()
                        ->value('reading_level'))
                    ->required(),
                TextInput::make('topic')
                    ->label(__('admin.comprehension_exercise.fields.topic'))
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->label(__('admin.comprehension_exercise.fields.description'))
                    ->helperText(__('admin.comprehension_exercise.help.description'))
                    ->rows(3),
                Slider::make('level')
                    ->label(__('admin.comprehension_exercise.fields.level'))
                    ->range(1, 50)
                    ->step(1)
                    ->decimalPlaces(0)
                    ->tooltips()
                    ->pips(PipsMode::Values, density: 2)
                    ->pipsValues([1, 10, 20, 30, 40, 50])
                    ->default(fn (): int => ComprehensionExercise::query()
                        ->latest()
                        ->value('level') ?? 1)
                    ->live()
                    ->helperText(function (Get $get): ?HtmlString {
                        $level = filter_var($get('level'), FILTER_VALIDATE_INT);

                        if ($level === false || $level < 1 || $level > 50) {
                            return null;
                        }

                        $description = e(LevelBand::forLevel($level)->getDescription());

                        return new HtmlString("<span style=\"display: block; margin-top: 1rem;\">{$description}</span>");
                    })
                    ->required(),
            ])
            ->columns(1);
    }
}
