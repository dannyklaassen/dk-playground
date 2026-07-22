<?php

declare(strict_types=1);

use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Resources\Resource;

arch()->preset()->php();
arch()->preset()->laravel()->ignoring('App\Providers\Filament');
arch()->preset()->security();

arch('the app uses strict types')
    ->expect('App')
    ->toUseStrictTypes();

arch('no debug statements in app code')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'print_r'])
    ->not->toBeUsed();

arch('soft-deletable records are never hard-deleted')
    ->expect([ForceDeleteAction::class, ForceDeleteBulkAction::class])
    ->not->toBeUsed();

it('ensures every Filament resource has a matching policy with all seven abilities', function (): void {
    $appPath = dirname(__DIR__, 2).'/app';
    $violations = [];

    if (is_dir($appPath.'/Filament')) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($appPath.'/Filament'));

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), 'Resource.php')) {
                continue;
            }

            $class = 'App\\'.str_replace('/', '\\', substr($file->getPathname(), strlen($appPath) + 1, -4));
            $reflection = new ReflectionClass($class);

            if ($reflection->isAbstract() || ! $reflection->isSubclassOf(Resource::class)) {
                continue;
            }

            $policy = 'App\\Policies\\'.class_basename($class::getModel()).'Policy';

            if (! class_exists($policy)) {
                $violations[] = "{$class}: missing policy {$policy}";

                continue;
            }

            foreach (['viewAny', 'view', 'create', 'update', 'delete', 'restore', 'forceDelete'] as $ability) {
                if (! method_exists($policy, $ability)) {
                    $violations[] = "{$policy}: missing ability {$ability}()";
                }
            }
        }
    }

    expect($violations)->toBe([]);
});
