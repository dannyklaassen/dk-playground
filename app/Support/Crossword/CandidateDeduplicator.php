<?php

declare(strict_types=1);

namespace App\Support\Crossword;

class CandidateDeduplicator
{
    /** A prefix this much shorter is the same word in another form (MUG / MUGGEN). */
    private const int MAX_PREFIX_DIFFERENCE = 3;

    /**
     * @param  list<WordCandidate>  $candidates
     * @return list<WordCandidate>
     */
    public function deduplicate(array $candidates): array
    {
        $kept = [];

        foreach ($candidates as $candidate) {
            foreach ($kept as $index => $existing) {
                if ($existing->word === $candidate->word) {
                    continue 2;
                }

                if (! $this->isVariantOf($existing->word, $candidate->word)) {
                    continue;
                }

                // Keep the longer form; it carries more of the word than its stem.
                if ($candidate->length() > $existing->length()) {
                    $kept[$index] = $candidate;
                }

                continue 2;
            }

            $kept[] = $candidate;
        }

        return array_values($kept);
    }

    private function isVariantOf(string $one, string $other): bool
    {
        [$short, $long] = mb_strlen($one) <= mb_strlen($other) ? [$one, $other] : [$other, $one];

        return str_starts_with($long, $short)
            && mb_strlen($long) - mb_strlen($short) <= self::MAX_PREFIX_DIFFERENCE;
    }
}
