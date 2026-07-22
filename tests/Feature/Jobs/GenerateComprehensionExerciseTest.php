<?php

declare(strict_types=1);

use App\Ai\Agents\ExerciseWriter;
use App\Jobs\GenerateComprehensionExercise;
use App\Models\ComprehensionExercise;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validExercisePayload(array $overrides = []): array
{
    $paragraphs = [
        'De pinguïn is een vogel die niet kan vliegen. Hij leeft op het zuidelijk halfrond.',
        'Pinguïns zwemmen heel snel. Hun vleugels werken onder water als peddels.',
        'Op het land waggelen pinguïns langzaam. Daarom glijden ze vaak op hun buik.',
        'Een pinguïn eet vooral vis en kleine kreeftjes. Hij vangt zijn eten onder water.',
        'De keizerspinguïn is de grootste soort. Hij wordt wel één meter lang.',
        'Veel pinguïns broeden samen in grote groepen. Zo houden ze elkaar warm.',
    ];

    $questions = [
        [
            'question' => 'Wat kan een pinguïn niet?',
            'options' => ['A' => 'Zwemmen', 'B' => 'Vliegen', 'C' => 'Eten', 'D' => 'Lopen'],
            'skill' => 'finding_information',
            'answer' => ['choice' => 'B', 'paragraph' => 1, 'evidence' => 'een vogel die niet kan vliegen'],
        ],
        [
            'question' => 'Waar verwijst "hun" in alinea 2 naar?',
            'options' => ['A' => 'De pinguïns', 'B' => 'De peddels', 'C' => 'De vissen', 'D' => 'De vleugels'],
            'skill' => 'reference_words',
            'answer' => ['choice' => 'A', 'paragraph' => 2, 'evidence' => 'Hun vleugels werken onder water'],
        ],
        [
            'question' => 'Waarom glijden pinguïns vaak op hun buik?',
            'options' => ['A' => 'Omdat ze langzaam waggelen', 'B' => 'Omdat ze moe zijn', 'C' => 'Omdat het sneeuwt', 'D' => 'Omdat ze spelen'],
            'skill' => 'cause_and_effect',
            'answer' => ['choice' => 'A', 'paragraph' => 3, 'evidence' => 'waggelen pinguïns langzaam'],
        ],
        [
            'question' => 'Wat eet een pinguïn vooral?',
            'options' => ['A' => 'Gras', 'B' => 'Brood', 'C' => 'Vis en kleine kreeftjes', 'D' => 'Insecten'],
            'skill' => 'finding_information',
            'answer' => ['choice' => 'C', 'paragraph' => 4, 'evidence' => 'vooral vis en kleine kreeftjes'],
        ],
        [
            'question' => 'Waar gaat deze tekst vooral over?',
            'options' => ['A' => 'De zuidpool', 'B' => 'Vogels die vliegen', 'C' => 'Vissen vangen', 'D' => 'Het leven van pinguïns'],
            'skill' => 'main_idea',
            'answer' => ['choice' => 'D', 'paragraph' => 6, 'evidence' => null],
        ],
    ];

    return [
        'title' => 'De pinguïn: een vogel die zwemt',
        'paragraphs' => $paragraphs,
        'questions' => $questions,
        ...$overrides,
    ];
}

it('stores a valid generation and marks the exercise as generated', function (): void {
    ExerciseWriter::fake([validExercisePayload()]);

    $exercise = ComprehensionExercise::factory()->create();

    (new GenerateComprehensionExercise($exercise))->handle();

    $exercise->refresh();

    expect($exercise->generated_at)->not->toBeNull()
        ->and($exercise->failed_at)->toBeNull()
        ->and($exercise->title)->toBe('De pinguïn: een vogel die zwemt')
        ->and($exercise->paragraphs)->toHaveCount(6)
        ->and($exercise->questions)->toHaveCount(5);
});

it('retries after rejected output and stores the second, valid attempt', function (): void {
    $invalid = validExercisePayload();
    $invalid['paragraphs'][] = 'Een zevende alinea die er niet hoort.';

    ExerciseWriter::fake([$invalid, validExercisePayload()]);

    $exercise = ComprehensionExercise::factory()->create();

    (new GenerateComprehensionExercise($exercise))->handle();

    $exercise->refresh();

    expect($exercise->generated_at)->not->toBeNull()
        ->and($exercise->failed_at)->toBeNull();
});

it('marks the exercise as failed without partial content when every attempt is rejected', function (): void {
    $invalid = validExercisePayload(['paragraphs' => ['Slechts één alinea.']]);

    ExerciseWriter::fake([$invalid, $invalid, $invalid]);

    $exercise = ComprehensionExercise::factory()->create();

    (new GenerateComprehensionExercise($exercise))->handle();

    $exercise->refresh();

    expect($exercise->failed_at)->not->toBeNull()
        ->and($exercise->generated_at)->toBeNull()
        ->and($exercise->title)->toBeNull()
        ->and($exercise->paragraphs)->toBeNull()
        ->and($exercise->questions)->toBeNull();
});

it('rejects output whose evidence does not occur in the referenced paragraph', function (): void {
    $invalid = validExercisePayload();
    $invalid['questions'][0]['answer']['evidence'] = 'deze zin staat nergens in de tekst';

    ExerciseWriter::fake([$invalid, $invalid, $invalid]);

    $exercise = ComprehensionExercise::factory()->create();

    (new GenerateComprehensionExercise($exercise))->handle();

    expect($exercise->refresh()->failed_at)->not->toBeNull();
});

it('accepts evidence that only differs in case, whitespace and punctuation', function (): void {
    $payload = validExercisePayload();
    $payload['questions'][0]['answer']['evidence'] = 'Een  vogel, die NIET kan vliegen!';

    ExerciseWriter::fake([$payload]);

    $exercise = ComprehensionExercise::factory()->create();

    (new GenerateComprehensionExercise($exercise))->handle();

    expect($exercise->refresh()->generated_at)->not->toBeNull();
});

it('repairs a skewed answer distribution by reshuffling options instead of rejecting', function (): void {
    $skewed = validExercisePayload();
    $skewed['questions'][1]['answer']['choice'] = 'B';
    $skewed['questions'][1]['options'] = ['A' => 'De peddels', 'B' => 'De pinguïns', 'C' => 'De vissen', 'D' => 'De vleugels'];
    $skewed['questions'][2]['answer']['choice'] = 'B';
    $skewed['questions'][2]['options'] = ['A' => 'Omdat ze moe zijn', 'B' => 'Omdat ze langzaam waggelen', 'C' => 'Omdat het sneeuwt', 'D' => 'Omdat ze spelen'];
    // three questions now answer "B"

    $correctTexts = array_map(
        fn (array $question): string => $question['options'][$question['answer']['choice']],
        $skewed['questions'],
    );

    ExerciseWriter::fake([$skewed]);

    $exercise = ComprehensionExercise::factory()->create();

    (new GenerateComprehensionExercise($exercise))->handle();

    $exercise->refresh();

    expect($exercise->generated_at)->not->toBeNull()
        ->and($exercise->failed_at)->toBeNull();

    $choices = array_column(array_column($exercise->questions, 'answer'), 'choice');

    expect(max(array_count_values($choices)))->toBeLessThanOrEqual(2);

    foreach ($exercise->questions as $index => $question) {
        expect($question['options'][$question['answer']['choice']])->toBe($correctTexts[$index]);
    }
});

it('rejects output with fewer than three distinct reading skills', function (): void {
    $invalid = validExercisePayload();

    foreach (range(0, 4) as $index) {
        $invalid['questions'][$index]['skill'] = 'finding_information';
    }

    ExerciseWriter::fake([$invalid, $invalid, $invalid]);

    $exercise = ComprehensionExercise::factory()->create();

    (new GenerateComprehensionExercise($exercise))->handle();

    expect($exercise->refresh()->failed_at)->not->toBeNull();
});

it('sets failed_at through the failed hook on an unexpected error', function (): void {
    $exercise = ComprehensionExercise::factory()->create();

    (new GenerateComprehensionExercise($exercise))->failed(new RuntimeException('API unreachable'));

    expect($exercise->refresh()->failed_at)->not->toBeNull();
});
