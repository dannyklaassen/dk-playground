<?php

declare(strict_types=1);

namespace App\Filament\Resources\ComprehensionExercises\Pages;

use App\Filament\Resources\ComprehensionExercises\ComprehensionExerciseResource;
use App\Jobs\GenerateComprehensionExercise;
use App\Models\ComprehensionExercise;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListComprehensionExercises extends ListRecords
{
    protected static string $resource = ComprehensionExerciseResource::class;

    #[\Override]
    public function getTitle(): string
    {
        return __('admin.comprehension_exercise.navigation');
    }

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->slideOver()
                ->modalWidth(Width::Medium)
                ->after(function (ComprehensionExercise $record): void {
                    dispatch(new GenerateComprehensionExercise($record));
                }),
        ];
    }
}
