<?php

declare(strict_types=1);

use App\Models\CrosswordPuzzle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/**
 * The letters of the marked squares of a crossword puzzle, read in the order of
 * their label. Equal to the solution word when the marking is sound.
 */
function markedLetters(CrosswordPuzzle $puzzle): string
{
    $letters = [];

    foreach ($puzzle->cells() as $row) {
        foreach ($row as $cell) {
            if ($cell !== null && $cell['solution'] !== null) {
                $letters[$cell['solution']] = $cell['letter'];
            }
        }
    }

    ksort($letters);

    return implode('', $letters);
}
