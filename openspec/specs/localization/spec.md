# localization

## Purpose

Dutch-first localization with English fallback, laravel-lang core translations, and per-surface translation files.

## Requirements

### Requirement: Dutch-first locale configuration
The application SHALL run Dutch-first with English fallback: `APP_LOCALE=nl`, `APP_FALLBACK_LOCALE=en`, `APP_FAKER_LOCALE=nl_NL` and `APP_TIMEZONE=Europe/Amsterdam`, set in both `.env` and `.env.example` and as defaults in `config/app.php`.

#### Scenario: Application locale is Dutch
- **WHEN** the application boots without env-overrides
- **THEN** `app()->getLocale()` returns `nl`, the fallback locale is `en` and the timezone is `Europe/Amsterdam`

### Requirement: Dutch core translations via laravel-lang
The project SHALL include `laravel-lang/common` as dev-dependency and publish the Dutch core translation files (`auth.php`, `validation.php`, `passwords.php`, `pagination.php`) into `lang/nl/`.

#### Scenario: Validation message renders in Dutch
- **WHEN** a validation rule such as `required` fails
- **THEN** the error message is the Dutch translation from `lang/nl/validation.php`

### Requirement: Surface-based translation files
User-facing strings SHALL live in per-surface files following the `<surface>.<resource>.<sub-key>.<property>` key convention. The project scaffolds `lang/nl/admin.php` and `lang/nl/common.php`, where `common.php` at minimum contains `actions.create_another` = "Aanmaken & volgende". All project lang files SHALL open with `declare(strict_types=1);`.

#### Scenario: Create-another label resolves
- **WHEN** `__('common.actions.create_another')` is called
- **THEN** it returns "Aanmaken & volgende"
