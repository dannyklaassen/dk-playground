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
}
