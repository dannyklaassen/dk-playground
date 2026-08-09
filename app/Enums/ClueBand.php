<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use InvalidArgumentException;

enum ClueBand: string implements HasLabel
{
    case Band1To10 = 'band_1_10';
    case Band11To20 = 'band_11_20';
    case Band21To30 = 'band_21_30';
    case Band31To40 = 'band_31_40';
    case Band41To50 = 'band_41_50';

    public static function forLevel(int $level): self
    {
        return match (true) {
            $level < 1 => throw new InvalidArgumentException("Level must be between 1 and 50, got {$level}."),
            $level <= 10 => self::Band1To10,
            $level <= 20 => self::Band11To20,
            $level <= 30 => self::Band21To30,
            $level <= 40 => self::Band31To40,
            $level <= 50 => self::Band41To50,
            default => throw new InvalidArgumentException("Level must be between 1 and 50, got {$level}."),
        };
    }

    public function getLabel(): string
    {
        return __("common.clue_band.{$this->value}");
    }

    public function getDescription(): string
    {
        return __("common.clue_band_descriptions.{$this->value}");
    }

    /**
     * Which words to pick from the text. Separate from the clue type: this one
     * is an instruction to the agent, the description above is help text under
     * the level field.
     */
    public function getWordChoice(): string
    {
        return __("common.clue_band_word_choice.{$this->value}");
    }

    /**
     * Only the lowest band may echo part of a compound. That band asks for a
     * clue that nearly names the key word, which a blanket ban on word parts
     * makes impossible; the full word stays off limits everywhere.
     */
    public function allowsWordPart(): bool
    {
        return $this === self::Band1To10;
    }

    /**
     * A gap at the end lets the child read a full run-up and then answer. A gap
     * mid-sentence forces it to combine the words before and after the gap,
     * which is the harder reading skill and belongs on the upper bands.
     *
     * Independent of sentence variety: an ordinary sentence ending in its
     * object also ends on the gap, so the low bands stay varied too.
     */
    public function requiresGapAtEnd(): bool
    {
        return $this === self::Band1To10 || $this === self::Band11To20;
    }
}
