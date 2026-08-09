<?php

declare(strict_types=1);

use App\Models\CrosswordPuzzle;
use App\Models\User;
use App\Policies\CrosswordPuzzlePolicy;

beforeEach(function (): void {
    $this->policy = new CrosswordPuzzlePolicy;
    $this->user = User::factory()->create();
    $this->puzzle = CrosswordPuzzle::factory()->create();
});

it('allows an authenticated user to manage puzzles', function (string $ability): void {
    expect($this->policy->{$ability}($this->user, $this->puzzle))->toBeTrue();
})->with(['view', 'update', 'delete']);

it('allows an authenticated user to list and create puzzles', function (string $ability): void {
    expect($this->policy->{$ability}($this->user))->toBeTrue();
})->with(['viewAny', 'create']);

it('never restores or hard-deletes a puzzle', function (string $ability): void {
    expect($this->policy->{$ability}($this->user, $this->puzzle))->toBeFalse();
})->with(['restore', 'forceDelete']);
