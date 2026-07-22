<?php

declare(strict_types=1);

use App\Models\User;
use Filament\Facades\Filament;

it('allows users to access the admin panel outside local environments', function (): void {
    $this->app['env'] = 'production';

    expect(User::factory()->create()->canAccessPanel(Filament::getPanel('admin')))->toBeTrue();
});
