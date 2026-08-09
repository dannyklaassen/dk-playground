<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CrosswordPuzzle;
use App\Models\User;

class CrosswordPuzzlePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CrosswordPuzzle $crosswordPuzzle): bool
    {
        return true;
    }

    /** Seeing the filled-in grid, apart from seeing the puzzle itself. */
    public function viewSolution(User $user, CrosswordPuzzle $crosswordPuzzle): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, CrosswordPuzzle $crosswordPuzzle): bool
    {
        return true;
    }

    public function delete(User $user, CrosswordPuzzle $crosswordPuzzle): bool
    {
        return true;
    }

    public function restore(User $user, CrosswordPuzzle $crosswordPuzzle): bool
    {
        return false;
    }

    public function forceDelete(User $user, CrosswordPuzzle $crosswordPuzzle): bool
    {
        return false;
    }
}
