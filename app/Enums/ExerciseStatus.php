<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ExerciseStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Generated = 'generated';
    case Failed = 'failed';

    public function getLabel(): string
    {
        return __("common.exercise_status.{$this->value}");
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Generated => 'success',
            self::Failed => 'danger',
        };
    }
}
