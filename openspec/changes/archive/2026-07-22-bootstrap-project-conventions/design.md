# Design — bootstrap-project-conventions

## Context

dk-playground is een verse Laravel 13 skeleton (Pest, Boost, Filament v5, OpenSpec net geïnstalleerd). `.editorconfig` is al aanwezig en conform. Er is nog geen `lang/`-map, geen `rector.php`, geen Arch-suite, en `AppServiceProvider` is leeg. De conventies zijn 1-op-1 overgenomen uit dcf-collits-platform (primaire referentie) aangevuld met g4h-platform (date-formats, placeholder) — beide zonder PHPStan/Larastan.

## Goals / Non-Goals

**Goals:**
- Referentie-implementatie van de globale conventies in dit playground, in één change.
- Alles verifieerbaar: quality gate + arch-tests slagen na afloop (`composer test` groen).

**Non-Goals:**
- Geen PHPStan/Larastan (expliciete keuze van Danny).
- Geen Filament resources, models of domeincode — alleen tooling/config/conventie-handhaving.
- Geen `pint.json` — Pint draait op de Laravel-default preset.
- Geen seeders/migraties — die conventies worden pas relevant bij echte domeincode.

## Decisions

- **Dependency-installatie via composer require** (`--dev` voor rector, rector-laravel, laravel-lang/common, ide-helper) i.p.v. handmatige composer.json-edits, zodat de lockfile klopt. Dit is een goedgekeurde dependency-wijziging (onderdeel van deze change).
- **Dutch core translations via `php artisan lang:add nl`** (laravel-lang), conform het `setup-laravel-localization` draaiboek; daarna alleen `admin.php`/`common.php` handmatig scaffolden.
- **Resource-heeft-policy arch-test tolereert een lege resource-set.** De dcf-collits versie asserteert `expect($resources)->not->toBeEmpty()`; dit playground heeft nog geen resources, dus die assertie vervalt — de foreach over nul resources slaagt dan vanzelf. Zodra de eerste resource verschijnt, dwingt de test policies af.
- **`test -p` (parallel)** in het composer-script, zoals dcf-collits; `phpunit.xml` blijft de bron voor testing-env.
- **Rector direct op de bestaande skeleton-code draaien** als onderdeel van de implementatie, zodat `rector:dry-run` daarna schoon is (o.a. strict_types op bestaande bestanden — ook vereist door de arch-test).
- **`config/app.php` defaults aanpassen** (`env('APP_LOCALE', 'nl')` etc.) naast `.env`/`.env.example`, zodat de defaults ook zonder env kloppen.

## Risks / Trade-offs

- [Rector herschrijft skeleton-bestanden agressief] → eerst `rector:dry-run` bekijken; wijzigingen zijn puur mechanisch en gedekt door de bestaande Pest-smoke tests.
- [`arch()->preset()->laravel()` kan op skeleton-code struikelen (bv. lege controllers)] → zelfde ignores gebruiken als dcf-collits (`App\Providers\Filament`); extra ignores alleen toevoegen als een preset-regel aantoonbaar vals-positief is, met commentaar waarom.
- [Parallel testen (`-p`) met `:memory:` sqlite] → standaard ondersteund door Pest 4/Laravel 13; bij problemen valt `-p` weg te laten zonder conventiebreuk.

## Migration Plan

Niet van toepassing (geen productie, geen data). Rollback = git revert van de change-commit.
