<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\DatabaseSeeder;

it('seeds a test user in non-production environments', function (): void {
    $this->seed(DatabaseSeeder::class);

    expect(User::query()->where('email', 'test@example.com')->exists())->toBeTrue();
});

it('seeds nothing on production', function (): void {
    $this->app['env'] = 'production';

    app(DatabaseSeeder::class)->run();

    expect(User::query()->count())->toBe(0);
});
