# Tasks — bootstrap-project-conventions

## 1. Dependencies

- [x] 1.1 `composer require --dev rector/rector driftingly/rector-laravel laravel-lang/common barryvdh/laravel-ide-helper`

## 2. Localization

- [x] 2.1 Zet `APP_LOCALE=nl`, `APP_FALLBACK_LOCALE=en`, `APP_FAKER_LOCALE=nl_NL`, `APP_TIMEZONE=Europe/Amsterdam` in `.env` en `.env.example`, en pas de bijbehorende defaults in `config/app.php` aan
- [x] 2.2 Publiceer Nederlandse core-vertalingen via `php artisan lang:add nl` (auth, validation, passwords, pagination in `lang/nl/`)
- [x] 2.3 Scaffold `lang/nl/common.php` (met `actions.create_another` => "Aanmaken & volgende" en lege `navigation.groups`) en `lang/nl/admin.php` (leeg resource-skelet), beide met `declare(strict_types=1);`

## 3. Code quality tooling

- [x] 3.1 Maak `rector.php` conform de spec (php84-sets, zes rector-laravel sets, prepared sets deadCode/codeQuality/typeDeclarations/privatization/earlyReturn, paths app/config/public/resources/routes)
- [x] 3.2 Voeg composer scripts toe: `rector`, `rector:dry-run`, `code-quality`, `code-quality:check`; pas `test` aan (config:clear → code-quality:check → `php artisan test -p`) en voeg `ci:check` toe
- [x] 3.3 Draai `composer rector` + `vendor/bin/pint` op de bestaande codebase zodat de dry-run daarna schoon is (o.a. strict_types op bestaande app-bestanden)

## 4. Testing setup

- [x] 4.1 Voeg de `Arch` testsuite toe aan `phpunit.xml` en de testing-env defaults (`BCRYPT_ROUNDS=4`, array cache/session/mail, sync queue, sqlite `:memory:`, `memory_limit=512M`, PULSE/TELESCOPE/NIGHTWATCH uit)
- [x] 4.2 Herschrijf `tests/Pest.php`: `pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature')`, strict types
- [x] 4.3 Maak `tests/Arch/PresetsTest.php` met de presets (php/laravel/security), strict-types-check, no-debug-check, no-ForceDelete-check en de resource-heeft-policy reflectietest (lege resource-set toegestaan)

## 5. App defaults

- [x] 5.1 Herschrijf `app/Providers/AppServiceProvider.php`: `boot()` dispatcht naar `configureDefaults()` en `configureFilament()`, strict types
- [x] 5.2 Implementeer `configureDefaults()`: CarbonImmutable, `Number::useLocale`, `prohibitDestructiveCommands`, production-only `Password::defaults()`
- [x] 5.3 Implementeer `configureFilament()`: create-another label, gray Edit/View, success Restore, MarkdownEditor-toolbar + minHeight 200px, Table paginatie 50, TextColumn `verticallyAlignStart` + placeholder `—`, TextEntry placeholder `—`, DatePicker/DateTimePicker `native(false)` + `d-m-Y`/`d-m-Y H:i`

## 6. Verificatie

- [x] 6.1 `composer test` draait volledig groen (rector dry-run schoon, pint --test schoon, alle suites incl. Arch slagen)
