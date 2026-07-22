# code-quality

## Purpose

Code quality toolchain: Rector + Pint with a fix/verify composer script split; no PHPStan/Larastan.

## Requirements

### Requirement: Rector configuration
The project SHALL include `rector/rector` and `driftingly/rector-laravel` as dev-dependencies with a strict-typed `rector.php` targeting `app`, `config`, `public`, `resources` and `routes`, configured with `withPhpSets(php84: true)`, the six rector-laravel sets (laravel130, facade-aliases-to-full-names, array-str-functions-to-static-call, code-quality, eloquent-magic-method-to-query-builder, collection) and prepared sets `deadCode`, `codeQuality`, `typeDeclarations`, `privatization`, `earlyReturn`.

#### Scenario: Rector dry-run passes on clean tree
- **WHEN** `composer rector:dry-run` is executed on the bootstrapped codebase
- **THEN** it exits successfully without proposed changes

### Requirement: Composer quality-script chain
`composer.json` SHALL define the fix/verify script split: `rector`, `rector:dry-run`, `code-quality` (`@rector` + `pint --parallel`), `code-quality:check` (`@rector:dry-run` + `pint --parallel --test`), a `test` script that runs `config:clear`, then `@code-quality:check`, then `php artisan test -p`, and `ci:check` as timeout-free alias of `test`.

#### Scenario: Quality gate runs before the suite
- **WHEN** `composer test` is executed
- **THEN** rector dry-run and pint --test run first, and the Pest suite only runs when both pass

### Requirement: No PHPStan or Larastan
The project SHALL NOT include `phpstan/phpstan` or `larastan/larastan`; static-type coverage comes from Rector's `typeDeclarations` set and the Pest arch presets.

#### Scenario: Dependencies stay PHPStan-free
- **WHEN** `composer.json` is inspected
- **THEN** no phpstan or larastan package is present in any dependency section
