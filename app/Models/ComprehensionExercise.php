<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExerciseStatus;
use App\Enums\LevelBand;
use App\Enums\ReadingLevel;
use Database\Factories\ComprehensionExerciseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'reading_level',
    'topic',
    'description',
    'level',
    'title',
    'paragraphs',
    'questions',
    'generated_at',
    'failed_at',
])]
class ComprehensionExercise extends Model
{
    /** @use HasFactory<ComprehensionExerciseFactory> */
    use HasFactory;

    use HasUlids;

    #[\Override]
    protected function casts(): array
    {
        return [
            'reading_level' => ReadingLevel::class,
            'level' => 'integer',
            'paragraphs' => 'array',
            'questions' => 'array',
            'generated_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    /**
     * @return Attribute<ExerciseStatus, never>
     */
    protected function status(): Attribute
    {
        return Attribute::get(fn (): ExerciseStatus => match (true) {
            $this->generated_at !== null => ExerciseStatus::Generated,
            $this->failed_at !== null => ExerciseStatus::Failed,
            default => ExerciseStatus::Pending,
        });
    }

    /**
     * @return Attribute<LevelBand, never>
     */
    protected function levelBand(): Attribute
    {
        return Attribute::get(fn (): LevelBand => LevelBand::forLevel($this->level));
    }
}
