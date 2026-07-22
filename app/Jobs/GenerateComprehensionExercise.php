<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Ai\Agents\ExerciseWriter;
use App\Models\ComprehensionExercise;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class GenerateComprehensionExercise implements ShouldQueue
{
    use Queueable;

    private const int MAX_ATTEMPTS = 3;

    public int $timeout = 600;

    public function __construct(public ComprehensionExercise $exercise) {}

    public function handle(): void
    {
        foreach (range(1, self::MAX_ATTEMPTS) as $attempt) {
            $response = (new ExerciseWriter)->prompt(
                ExerciseWriter::promptFor($this->exercise),
                model: config('ai.providers.anthropic.model'),
            );

            $data = $this->rebalanceAnswerLetters($response->toArray());

            $errors = $this->validationErrors($data);

            if ($errors === []) {
                $this->exercise->update([
                    'title' => $data['title'],
                    'paragraphs' => $data['paragraphs'],
                    'questions' => $data['questions'],
                    'generated_at' => now(),
                    'failed_at' => null,
                ]);

                return;
            }

            Log::warning('Comprehension exercise output rejected', [
                'exercise_id' => $this->exercise->id,
                'attempt' => $attempt,
                'errors' => $errors,
            ]);
        }

        $this->markFailed();
    }

    public function failed(?Throwable $exception): void
    {
        $this->markFailed();
    }

    private function markFailed(): void
    {
        $this->exercise->update(['failed_at' => now()]);
    }

    /**
     * Repair a skewed answer-letter distribution deterministically: move the
     * correct option of an overused letter to the least-used letter by swapping
     * the option texts, so the content of the correct answer never changes.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function rebalanceAnswerLetters(array $data): array
    {
        $questions = $data['questions'] ?? null;

        if (! is_array($questions)) {
            return $data;
        }

        $counts = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0];

        foreach ($questions as $question) {
            $choice = $question['answer']['choice'] ?? null;

            if (! isset($counts[$choice]) || array_keys($question['options'] ?? []) !== ['A', 'B', 'C', 'D']) {
                return $data; // malformed output; leave it to the validation
            }

            $counts[$choice]++;
        }

        foreach ($questions as $index => $question) {
            $from = $question['answer']['choice'];

            if ($counts[$from] <= 2) {
                continue;
            }

            $to = array_keys($counts, min($counts))[0];
            $options = $question['options'];

            $questions[$index]['options'][$from] = $options[$to];
            $questions[$index]['options'][$to] = $options[$from];
            $questions[$index]['answer']['choice'] = $to;

            $counts[$from]--;
            $counts[$to]++;
        }

        $data['questions'] = $questions;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private function validationErrors(array $data): array
    {
        $paragraphs = $data['paragraphs'] ?? null;
        $questions = $data['questions'] ?? null;

        if (! is_array($paragraphs) || count($paragraphs) !== 6) {
            return ['expected exactly 6 paragraphs, got '.(is_array($paragraphs) ? count($paragraphs) : 'none')];
        }

        if (! is_array($questions) || count($questions) !== 5) {
            return ['expected exactly 5 questions, got '.(is_array($questions) ? count($questions) : 'none')];
        }

        $errors = [];
        $choices = [];
        $skills = [];

        foreach ($questions as $index => $question) {
            $number = $index + 1;
            $options = $question['options'] ?? [];
            $answer = $question['answer'] ?? [];
            $choice = $answer['choice'] ?? null;
            $paragraph = $answer['paragraph'] ?? null;
            $evidence = $answer['evidence'] ?? null;

            if (array_keys($options) !== ['A', 'B', 'C', 'D']) {
                $errors[] = "question {$number}: options are not exactly A-D";
            }

            if (! in_array($choice, ['A', 'B', 'C', 'D'], true)) {
                $errors[] = "question {$number}: answer choice is not A-D";
            }

            if (! is_int($paragraph) || $paragraph < 1 || $paragraph > 6) {
                $errors[] = "question {$number}: answer paragraph is not 1-6";
            } elseif ($evidence !== null && ! $this->evidenceOccursInParagraph($evidence, $paragraphs[$paragraph - 1])) {
                $errors[] = "question {$number}: evidence not found in paragraph {$paragraph}";
            }

            $choices[] = $choice;
            $skills[] = $question['skill'] ?? null;
        }

        $validChoices = array_filter($choices, is_string(...));

        if ($validChoices !== [] && max(array_count_values($validChoices)) > 2) {
            $errors[] = 'one answer letter is the correct answer more than twice';
        }

        if (count(array_unique($skills)) < 3) {
            $errors[] = 'fewer than 3 distinct reading skills';
        }

        return $errors;
    }

    private function evidenceOccursInParagraph(string $evidence, string $paragraph): bool
    {
        return str_contains($this->normalize($paragraph), $this->normalize($evidence));
    }

    private function normalize(string $text): string
    {
        $text = Str::lower($text);
        $text = (string) preg_replace('/[^\p{L}\p{N}\s]/u', '', $text);

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }
}
