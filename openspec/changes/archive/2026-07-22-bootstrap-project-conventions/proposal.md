# Bootstrap project conventions

## Why

dk-playground is een verse Laravel 13 + Filament v5 skeleton zonder de standaard projectconventies die inmiddels in de globale CLAUDE.md zijn vastgelegd (geoogst uit dcf-collits-platform, g4h-platform en eve-platform). Door het playground nu te bootstrappen wordt elk experiment hier direct volgens de standaard gebouwd, en dient het project als referentie-implementatie van die conventies.

## What Changes

- **Dutch-first localization**: `APP_LOCALE=nl`, `APP_FALLBACK_LOCALE=en`, `APP_FAKER_LOCALE=nl_NL`, `APP_TIMEZONE=Europe/Amsterdam`; `laravel-lang/common` als dev-dependency; Nederlandse core-vertalingen (`auth`, `validation`, `passwords`, `pagination`) + scaffold van `lang/nl/admin.php` en `lang/nl/common.php` volgens de surface-conventie.
- **Code quality tooling**: `rector/rector` + `driftingly/rector-laravel` + `barryvdh/laravel-ide-helper` als dev-dependencies; `rector.php` met php84-sets, zes rector-laravel sets en prepared sets; composer scripts `rector`, `rector:dry-run`, `code-quality`, `code-quality:check`, aangepaste `test` (quality gate vóór de suite) en `ci:check`. **Geen PHPStan/Larastan** — bewuste keuze.
- **Testing setup**: `Arch` testsuite toegevoegd aan `phpunit.xml` + testing-env defaults (`BCRYPT_ROUNDS=4`, observability uit); `tests/Pest.php` bindt `RefreshDatabase` suite-breed aan Feature; `tests/Arch/PresetsTest.php` met de Pest-presets (php/laravel/security), strict-types-check, no-debug-check, no-ForceDelete-check en de resource-heeft-policy reflectietest.
- **Globale app-defaults** in `AppServiceProvider::boot()` via configure-methodes: `configureDefaults()` (CarbonImmutable, `Number::useLocale`, `prohibitDestructiveCommands`, production-only `Password::defaults()`) en `configureFilament()` (create-another label, gray Edit/View, success Restore, MarkdownEditor-toolbar, paginatie 50, `verticallyAlignStart`, placeholder `—`, DatePicker/DateTimePicker `native(false)` + `d-m-Y` formats).
- `declare(strict_types=1)` op alle bestaande app-bestanden (vereist door de arch-test).

## Capabilities

### New Capabilities

- `localization`: Dutch-first locale-configuratie en de vertaalbestand-structuur (surface-bestanden, key-conventie, laravel-lang basis).
- `code-quality`: Rector + Pint toolchain en de composer-scriptketen die formatting/refactoring afdwingt vóór de testsuite.
- `testing-conventions`: Pest-suiteopbouw (Unit/Feature/Arch), testing-env defaults en de arch-tests die de projectconventies afdwingen.
- `app-defaults`: projectbrede framework- en Filament-defaults, eenmalig geconfigureerd in `AppServiceProvider`.

### Modified Capabilities

_(geen — er bestaan nog geen specs in dit project)_

## Impact

- `composer.json` — nieuwe dev-dependencies (`rector/rector`, `driftingly/rector-laravel`, `laravel-lang/common`, `barryvdh/laravel-ide-helper`) en scriptwijzigingen.
- Nieuw: `rector.php`, `lang/nl/*`, `tests/Arch/PresetsTest.php`.
- Gewijzigd: `phpunit.xml`, `tests/Pest.php`, `app/Providers/AppServiceProvider.php`, `.env` + `.env.example`, `config/app.php` (locale-defaults).
- Geen runtime-/schemawijzigingen; puur tooling, configuratie en conventie-handhaving.
