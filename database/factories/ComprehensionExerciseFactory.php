<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ReadingLevel;
use App\Enums\ReadingSkill;
use App\Models\ComprehensionExercise;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ComprehensionExercise>
 */
class ComprehensionExerciseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reading_level' => fake()->randomElement(ReadingLevel::cases()),
            'topic' => fake()->words(2, true),
            'description' => fake()->boolean() ? fake()->sentence() : null,
            'level' => fake()->numberBetween(1, 50),
        ];
    }

    public function generated(): static
    {
        $paragraphs = collect(range(1, 6))
            ->map(fn (int $number): string => "Alinea {$number}. ".fake()->paragraph(3))
            ->all();

        $skills = [
            ReadingSkill::FindingInformation,
            ReadingSkill::ReferenceWords,
            ReadingSkill::CauseAndEffect,
            ReadingSkill::MainIdea,
            ReadingSkill::DrawingConclusions,
        ];

        $choices = ['A', 'B', 'C', 'A', 'B'];

        $questions = collect(range(1, 5))->map(fn (int $number): array => [
            'question' => fake()->sentence(),
            'options' => [
                'A' => fake()->sentence(3),
                'B' => fake()->sentence(3),
                'C' => fake()->sentence(3),
                'D' => fake()->sentence(3),
            ],
            'skill' => $skills[$number - 1]->value,
            'answer' => [
                'choice' => $choices[$number - 1],
                'paragraph' => $number,
                'evidence' => $number === 5 ? null : "Alinea {$number}.",
            ],
        ])->all();

        return $this->state(fn (): array => [
            'title' => fake()->sentence(4),
            'paragraphs' => $paragraphs,
            'questions' => $questions,
            'generated_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (): array => [
            'failed_at' => now(),
        ]);
    }
}
