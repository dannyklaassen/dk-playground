# app-defaults

## ADDED Requirements

### Requirement: AppServiceProvider dispatches focused configure-methods
`AppServiceProvider::boot()` SHALL only dispatch to focused methods (`configureDefaults()`, `configureFilament()`); every project-wide default is set once here via `::configureUsing()` and never restated per-resource. The file SHALL use `declare(strict_types=1);`.

#### Scenario: Boot structure
- **WHEN** `AppServiceProvider` is inspected
- **THEN** `boot()` contains only calls to the configure-methods

### Requirement: Framework defaults
`configureDefaults()` SHALL set: `Date::use(CarbonImmutable::class)`, `Number::useLocale(config('app.locale'))`, `DB::prohibitDestructiveCommands(app()->isProduction())`, and `Password::defaults()` returning strong rules (min 12, mixedCase, letters, numbers, symbols, uncompromised) only in production and `null` otherwise.

#### Scenario: Dates are immutable
- **WHEN** `now()` is called anywhere in the app
- **THEN** it returns a `CarbonImmutable` instance

#### Scenario: Destructive commands blocked on production
- **WHEN** the app environment is production and a destructive artisan command (e.g. `migrate:fresh`) is invoked
- **THEN** the command is prohibited

### Requirement: Project-wide Filament defaults
`configureFilament()` SHALL configure: `CreateAction` create-another label via `__('common.actions.create_another')`; `EditAction`/`ViewAction` color `gray`; `RestoreAction` color `success`; the standardized `MarkdownEditor` toolbar (bold/italic/strike, heading, bulletList/orderedList, undo/redo) with `minHeight('200px')`; `Table::defaultPaginationPageOption(50)`; `TextColumn::verticallyAlignStart()`; placeholder `—` on `TextColumn` and `TextEntry`; and `DatePicker`/`DateTimePicker` with `native(false)` and display formats `d-m-Y` / `d-m-Y H:i`.

#### Scenario: Edit action defaults to gray
- **WHEN** an `EditAction` is instantiated without explicit color
- **THEN** its color is `gray`

#### Scenario: Table pagination default
- **WHEN** a Filament table renders without explicit pagination option
- **THEN** the default page size is 50
