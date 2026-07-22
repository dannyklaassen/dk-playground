<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ReadingLevel: string implements HasLabel
{
    case BeginGroup4 = 'begin_group_4';
    case MidGroup4 = 'mid_group_4';
    case EndGroup4 = 'end_group_4';
    case MidGroup5 = 'mid_group_5';
    case EndGroup5 = 'end_group_5';
    case MidGroup6 = 'mid_group_6';
    case EndGroup6 = 'end_group_6';
    case MidGroup7 = 'mid_group_7';
    case EndGroup7 = 'end_group_7';
    case Group8 = 'group_8';

    public function getLabel(): string
    {
        return __("common.reading_level.{$this->value}");
    }
}
