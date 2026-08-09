<?php

declare(strict_types=1);

namespace App\Support\Crossword;

use App\Enums\PuzzleGroup;
use Illuminate\Support\Str;

/**
 * Rejects, never repairs: a prompt that keeps producing unusable words has to
 * stay visible instead of being quietly patched up.
 */
class CandidateValidator
{
    /** A clue is one sentence; anything longer is a runaway response. */
    private const int MAX_CLUE_LENGTH = 120;

    /**
     * @param  list<array<string, mixed>>  $words  the raw `words` from the agent
     * @return list<WordCandidate>
     */
    public function validate(array $words, string $sourceText, PuzzleGroup $group, ?string $exerciseId = null): array
    {
        $haystack = $this->normalize($sourceText);
        $candidates = [];

        foreach ($words as $word) {
            $clue = $word['clue'] ?? null;
            $normalized = $this->normalize((string) ($word['word'] ?? ''));
            if (! is_string($clue)) {
                continue;
            }
            if ($clue === '') {
                continue;
            }
            if (mb_strlen($clue) > self::MAX_CLUE_LENGTH) {
                continue;
            }

            if (preg_match('/^[A-Z]+$/', $normalized) !== 1) {
                continue;
            }

            $length = mb_strlen($normalized);
            if ($length < $group->minWordLength()) {
                continue;
            }
            if ($length > $group->maxWordLength()) {
                continue;
            }

            // As a whole word: REGEN must not be accepted on a text that only
            // says REGENACHTIG.
            if (preg_match('/\b'.preg_quote($normalized, '/').'\b/', $haystack) !== 1) {
                continue;
            }

            // The clue may not give the answer away.
            if (str_contains($this->normalize($clue), $normalized)) {
                continue;
            }

            $candidates[] = new WordCandidate($normalized, $clue, $exerciseId);
        }

        return $candidates;
    }

    /** Strip diacritics and upper-case; the grid only knows A-Z. */
    private function normalize(string $text): string
    {
        return Str::upper(Str::ascii(trim($text)));
    }
}
