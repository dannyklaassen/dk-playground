<?php

declare(strict_types=1);

use App\Enums\ReadingSkill;
use App\Models\ComprehensionExercise;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('shows the worksheet without any answers or skills', function (): void {
    $exercise = ComprehensionExercise::factory()->generated()->create();

    actingAs(User::factory()->create())
        ->get(route('comprehension-exercises.worksheet', $exercise))
        ->assertOk()
        ->assertSee($exercise->title)
        ->assertSee($exercise->paragraphs[0])
        ->assertSee($exercise->questions[0]['question'])
        ->assertSee($exercise->questions[0]['options']['A'])
        ->assertDontSee(__('admin.comprehension_exercise.print.answer'))
        ->assertDontSee(__('admin.comprehension_exercise.print.skill'));
});

it('shows the answer sheet with the answer table and evidence', function (): void {
    $exercise = ComprehensionExercise::factory()->generated()->create();

    actingAs(User::factory()->create())
        ->get(route('comprehension-exercises.answer-sheet', $exercise))
        ->assertOk()
        ->assertSee(__('admin.comprehension_exercise.print.answer_sheet_title'))
        ->assertSee($exercise->questions[0]['answer']['choice'])
        ->assertSee($exercise->questions[0]['answer']['evidence'])
        ->assertSee(__('common.not_applicable')) // question 5 has no evidence in the factory
        ->assertSee(ReadingSkill::from($exercise->questions[0]['skill'])->getLabel());
});

it('returns 404 for a worksheet of an exercise that is not generated', function (): void {
    $exercise = ComprehensionExercise::factory()->create();

    actingAs(User::factory()->create())
        ->get(route('comprehension-exercises.worksheet', $exercise))
        ->assertNotFound();
});

it('returns 404 for an answer sheet of an exercise that is not generated', function (): void {
    $exercise = ComprehensionExercise::factory()->failed()->create();

    actingAs(User::factory()->create())
        ->get(route('comprehension-exercises.answer-sheet', $exercise))
        ->assertNotFound();
});

it('requires authentication for the print pages', function (): void {
    $exercise = ComprehensionExercise::factory()->generated()->create();

    get(route('comprehension-exercises.worksheet', $exercise))->assertRedirect();
    get(route('comprehension-exercises.answer-sheet', $exercise))->assertRedirect();
});
