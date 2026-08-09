<?php

declare(strict_types=1);

use App\Enums\PuzzleGroup;
use App\Support\Crossword\CandidateValidator;

$text = "In de tuin zoemen muggen rond de planten. Een panda's beeld staat er ook. "
    .'De muggenbeet jeukt en het dierenziekenhuis is ver weg.';

/**
 * @return list<array{word: string, clue: string}>
 */
function words(string ...$words): array
{
    return array_map(fn (string $word): array => ['word' => $word, 'clue' => 'Een aanwijzing'], $words);
}

it('normalises diacritics and stores words in upper case', function () use ($text): void {
    $candidates = (new CandidateValidator)->validate(words('muggenbeËt'), $text, PuzzleGroup::Group6);

    expect($candidates)->toHaveCount(1)
        ->and($candidates[0]->word)->toBe('MUGGENBEET');
});

it('removes a word that does not occur in the source text', function () use ($text): void {
    expect((new CandidateValidator)->validate(words('olifant'), $text, PuzzleGroup::Group6))->toBe([]);
});

it('removes a word with an apostrophe', function () use ($text): void {
    expect((new CandidateValidator)->validate(words("panda's"), $text, PuzzleGroup::Group6))->toBe([]);
});

it('removes a word with a space', function () use ($text): void {
    expect((new CandidateValidator)->validate(words('de tuin'), $text, PuzzleGroup::Group6))->toBe([]);
});

it('removes a word that is longer than the group allows', function () use ($text): void {
    // 17 letters, over group 4's maximum of 7.
    expect((new CandidateValidator)->validate(words('dierenziekenhuis'), $text, PuzzleGroup::Group4))->toBe([]);
});

it('removes a word that is shorter than the group allows', function () use ($text): void {
    expect((new CandidateValidator)->validate(words('tuin', 'de'), $text, PuzzleGroup::Group4))
        ->toHaveCount(1);
});

it('removes a word that only occurs inside a longer word', function () use ($text): void {
    // The text says MUGGEN and MUGGENBEET, never MUG on its own.
    expect((new CandidateValidator)->validate(words('mug'), $text, PuzzleGroup::Group6))->toBe([]);
});

it('removes a candidate whose clue gives the word away', function () use ($text): void {
    $candidates = (new CandidateValidator)->validate(
        [['word' => 'planten', 'clue' => 'Wat je doet met planten in de tuin']],
        $text,
        PuzzleGroup::Group6,
    );

    expect($candidates)->toBe([]);
});

it('removes a candidate whose clue is a runaway text', function () use ($text): void {
    $candidates = (new CandidateValidator)->validate(
        [['word' => 'planten', 'clue' => str_repeat('heel erg lang ', 20)]],
        $text,
        PuzzleGroup::Group6,
    );

    expect($candidates)->toBe([]);
});

it('removes a candidate without a usable clue', function (mixed $clue) use ($text): void {
    expect((new CandidateValidator)->validate([['word' => 'planten', 'clue' => $clue]], $text, PuzzleGroup::Group6))
        ->toBe([]);
})->with([
    'missing' => null,
    'empty' => '',
    'not a string' => 42,
]);

it('keeps the clue and the source exercise on an accepted candidate', function () use ($text): void {
    $candidates = (new CandidateValidator)->validate(
        [['word' => 'planten', 'clue' => 'Ze groeien in de tuin']],
        $text,
        PuzzleGroup::Group6,
        'exercise-a',
    );

    expect($candidates[0]->clue)->toBe('Ze groeien in de tuin')
        ->and($candidates[0]->exerciseId)->toBe('exercise-a');
});
