<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ComprehensionExercise;
use App\Models\User;

class ComprehensionExercisePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ComprehensionExercise $comprehensionExercise): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ComprehensionExercise $comprehensionExercise): bool
    {
        return true;
    }

    public function delete(User $user, ComprehensionExercise $comprehensionExercise): bool
    {
        return true;
    }

    public function restore(User $user, ComprehensionExercise $comprehensionExercise): bool
    {
        return false;
    }

    public function forceDelete(User $user, ComprehensionExercise $comprehensionExercise): bool
    {
        return false;
    }
}
