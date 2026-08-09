<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PuzzleGroup: string implements HasLabel
{
    case Group4 = 'group_4';
    case Group5 = 'group_5';
    case Group6 = 'group_6';
    case Group7 = 'group_7';
    case Group8 = 'group_8';

    public function getLabel(): string
    {
        return __("common.puzzle_group.{$this->value}");
    }

    public function wordCount(): int
    {
        return match ($this) {
            self::Group4 => 10,
            self::Group5 => 12,
            self::Group6 => 14,
            self::Group7 => 16,
            self::Group8 => 18,
        };
    }

    public function minWordLength(): int
    {
        return 4;
    }

    public function maxWordLength(): int
    {
        return match ($this) {
            self::Group4 => 7,
            self::Group5 => 9,
            self::Group6 => 11,
            self::Group7 => 13,
            self::Group8 => 15,
        };
    }

    public function wordTypes(): string
    {
        return __("common.puzzle_group_word_types.{$this->value}");
    }
}
