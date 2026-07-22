# testing-conventions

## ADDED Requirements

### Requirement: Three test suites with hardened testing env
`phpunit.xml` SHALL define the suites `Unit`, `Feature` and `Arch` (`tests/Arch`) and the testing environment defaults: SQLite `:memory:`, `BCRYPT_ROUNDS=4`, array cache/session/mail, sync queue, `memory_limit=512M` and `PULSE_ENABLED`/`TELESCOPE_ENABLED`/`NIGHTWATCH_ENABLED` set to `false`.

#### Scenario: Arch suite is discovered
- **WHEN** `php artisan test --compact` runs
- **THEN** tests in `tests/Arch/` execute as part of the run

### Requirement: Pest bootstrap binds RefreshDatabase to Feature only
`tests/Pest.php` SHALL bind `RefreshDatabase` suite-wide to the Feature suite via `pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature')`; Unit tests stay database-less. `RefreshDatabase` SHALL never be applied per-test.

#### Scenario: Feature test gets a fresh database
- **WHEN** a Feature test creates a model via a factory without declaring RefreshDatabase itself
- **THEN** the test runs against a freshly migrated in-memory database

### Requirement: Architecture presets enforce project conventions
`tests/Arch/PresetsTest.php` SHALL enforce: the Pest `php()`, `laravel()` (ignoring `App\Providers\Filament`) and `security()` presets; strict types across `App`; no debug statements (`dd`, `dump`, `ray`, `var_dump`, `print_r`); and no usage of `ForceDeleteAction`/`ForceDeleteBulkAction`.

#### Scenario: Debug statement fails the build
- **WHEN** app code contains a `dd()` call and the Arch suite runs
- **THEN** the no-debug arch test fails

### Requirement: Every Filament resource has a full policy
An arch test SHALL assert via reflection that every `*Resource.php` under `app/Filament` declares a model with a matching `App\Policies\{Model}Policy` implementing all seven abilities (`viewAny`, `view`, `create`, `update`, `delete`, `restore`, `forceDelete`). While the project has no Filament resources yet, the test SHALL pass (empty set allowed) instead of failing on the empty-resources assertion.

#### Scenario: Resource without policy fails
- **WHEN** a Filament resource exists whose model has no policy class
- **THEN** the arch test fails naming the missing policy

#### Scenario: No resources yet
- **WHEN** `app/Filament` contains no `*Resource.php` files
- **THEN** the test passes without assertions failing
