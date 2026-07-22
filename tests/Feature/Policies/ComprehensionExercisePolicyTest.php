<?php

declare(strict_types=1);

use App\Models\ComprehensionExercise;
use App\Models\User;

it('allows a logged-in user to manage exercises but never restore or force-delete', function (): void {
    $user = User::factory()->create();
    $exercise = ComprehensionExercise::factory()->create();

    expect($user->can('viewAny', ComprehensionExercise::class))->toBeTrue()
        ->and($user->can('view', $exercise))->toBeTrue()
        ->and($user->can('create', ComprehensionExercise::class))->toBeTrue()
        ->and($user->can('update', $exercise))->toBeTrue()
        ->and($user->can('delete', $exercise))->toBeTrue()
        ->and($user->can('restore', $exercise))->toBeFalse()
        ->and($user->can('forceDelete', $exercise))->toBeFalse();
});
