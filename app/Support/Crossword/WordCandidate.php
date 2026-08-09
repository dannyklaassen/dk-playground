<?php

declare(strict_types=1);

namespace App\Support\Crossword;

readonly class WordCandidate
{
    public function __construct(
        public string $word,
        public string $clue,
        public ?string $exerciseId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $candidate
     */
    public static function fromArray(array $candidate): self
    {
        return new self(
            (string) $candidate['word'],
            (string) $candidate['clue'],
            $candidate['exercise_id'] === null ? null : (string) $candidate['exercise_id'],
        );
    }

    /**
     * @return array{word: string, clue: string, exercise_id: string|null}
     */
    public function toArray(): array
    {
        return [
            'word' => $this->word,
            'clue' => $this->clue,
            'exercise_id' => $this->exerciseId,
        ];
    }

    public function length(): int
    {
        return mb_strlen($this->word);
    }
}
