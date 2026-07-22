<?php

declare(strict_types=1);

namespace App\Providers;

use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureFilament();
    }

    private function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);
        Number::useLocale(config('app.locale'));
        DB::prohibitDestructiveCommands(app()->isProduction());
        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)->mixedCase()->letters()->numbers()->symbols()->uncompromised()
            : null);
    }

    private function configureFilament(): void
    {
        FilamentAsset::register([
            Css::make('admin', resource_path('css/filament/admin.css')),
        ]);

        CreateAction::configureUsing(function (CreateAction $action): void {
            $action->createAnotherAction(
                fn (Action $action): Action => $action->label(__('common.actions.create_another')),
            );
        });
        EditAction::configureUsing(fn (EditAction $action): EditAction => $action->color('gray'));
        ViewAction::configureUsing(fn (ViewAction $action): ViewAction => $action->color('gray'));
        RestoreAction::configureUsing(fn (RestoreAction $action): RestoreAction => $action->color('success'));

        MarkdownEditor::configureUsing(fn (MarkdownEditor $editor): MarkdownEditor => $editor
            ->toolbarButtons([
                ['bold', 'italic', 'strike'],
                ['heading'],
                ['bulletList', 'orderedList'],
                ['undo', 'redo'],
            ])
            ->minHeight('200px'));

        Table::configureUsing(fn (Table $table): Table => $table->defaultPaginationPageOption(50));
        TextColumn::configureUsing(fn (TextColumn $column): TextColumn => $column
            ->verticallyAlignStart()
            ->placeholder('—'));
        TextEntry::configureUsing(fn (TextEntry $entry): TextEntry => $entry->placeholder('—'));

        DatePicker::configureUsing(fn (DatePicker $picker): DatePicker => $picker
            ->native(false)
            ->displayFormat('d-m-Y'));
        DateTimePicker::configureUsing(fn (DateTimePicker $picker): DateTimePicker => $picker
            ->native(false)
            ->displayFormat('d-m-Y H:i'));
    }
}
