<?php

declare(strict_types=1);

use App\Support\Crossword\SolutionWord;

it('labels the squares a, b, c up to the maximum a sheet holds', function (): void {
    expect(SolutionWord::label(0))->toBe('a')
        ->and(SolutionWord::label(SolutionWord::MAX_LENGTH - 1))->toBe('h');
});

it('refuses a square beyond the maximum instead of printing punctuation', function (int $index): void {
    SolutionWord::label($index);
})->throws(InvalidArgumentException::class)->with([-1, SolutionWord::MAX_LENGTH]);

it('degrades a half-written stored value to an empty solution word', function (): void {
    $solutionWord = SolutionWord::fromArray(['cells' => [['row' => 2]]]);

    expect($solutionWord->word)->toBe('')
        ->and($solutionWord->clue)->toBe('')
        ->and($solutionWord->cells[0]->row)->toBe(2)
        ->and($solutionWord->cells[0]->column)->toBe(0);
});
