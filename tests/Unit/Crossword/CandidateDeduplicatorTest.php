<?php

declare(strict_types=1);

use App\Support\Crossword\CandidateDeduplicator;
use App\Support\Crossword\WordCandidate;

/**
 * @return list<WordCandidate>
 */
function pool(string ...$words): array
{
    return array_map(fn (string $word): WordCandidate => new WordCandidate($word, "Aanwijzing {$word}", 'exercise-a'), $words);
}

/**
 * @param  list<WordCandidate>  $candidates
 * @return list<string>
 */
function wordsOf(array $candidates): array
{
    return array_map(fn (WordCandidate $candidate): string => $candidate->word, $candidates);
}

it('keeps one of two identical words', function (): void {
    expect(wordsOf((new CandidateDeduplicator)->deduplicate(pool('TIJGER', 'TIJGER'))))
        ->toBe(['TIJGER']);
});

it('drops the singular when the plural is only a few letters longer', function (): void {
    expect(wordsOf((new CandidateDeduplicator)->deduplicate(pool('MUG', 'MUGGEN'))))
        ->toBe(['MUGGEN']);
});

it('drops the singular regardless of the order it arrives in', function (): void {
    expect(wordsOf((new CandidateDeduplicator)->deduplicate(pool('MUGGEN', 'MUG'))))
        ->toBe(['MUGGEN']);
});

it('keeps two words that share a start but differ too much', function (): void {
    expect(wordsOf((new CandidateDeduplicator)->deduplicate(pool('PLANT', 'PLANETEN'))))
        ->toBe(['PLANT', 'PLANETEN']);
});

it('keeps a longer word that is not a prefix extension', function (): void {
    expect(wordsOf((new CandidateDeduplicator)->deduplicate(pool('BOOM', 'BLOEMEN'))))
        ->toBe(['BOOM', 'BLOEMEN']);
});

it('keeps the source exercise of the surviving candidate', function (): void {
    $candidates = (new CandidateDeduplicator)->deduplicate([
        new WordCandidate('MUG', 'Insect', 'exercise-a'),
        new WordCandidate('MUGGEN', 'Insecten', 'exercise-b'),
    ]);

    expect($candidates)->toHaveCount(1)
        ->and($candidates[0]->exerciseId)->toBe('exercise-b');
});
