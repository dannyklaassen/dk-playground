<?php

declare(strict_types=1);

use App\Enums\ReadingLevel;
use App\Filament\Resources\ComprehensionExercises\Pages\ListComprehensionExercises;
use App\Filament\Resources\ComprehensionExercises\Pages\ViewComprehensionExercise;
use App\Jobs\GenerateComprehensionExercise;
use App\Models\ComprehensionExercise;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

it('creates an exercise and dispatches the generation job', function (): void {
    Queue::fake();

    livewire(ListComprehensionExercises::class)
        ->callAction(CreateAction::class, [
            'reading_level' => 'end_group_6',
            'topic' => 'pinguïns',
            'description' => null,
            'level' => 12,
        ])
        ->assertNotified()
        ->assertHasNoFormErrors();

    assertDatabaseHas(ComprehensionExercise::class, [
        'reading_level' => 'end_group_6',
        'topic' => 'pinguïns',
        'level' => 12,
        'generated_at' => null,
        'failed_at' => null,
    ]);

    Queue::assertPushed(GenerateComprehensionExercise::class);
});

it('defaults the reading level and difficulty to those of the most recent exercise', function (): void {
    ComprehensionExercise::factory()->create([
        'reading_level' => 'end_group_6',
        'level' => 40,
        'created_at' => now()->subDay(),
    ]);
    ComprehensionExercise::factory()->create([
        'reading_level' => 'mid_group_5',
        'level' => 23,
        'created_at' => now(),
    ]);

    livewire(ListComprehensionExercises::class)
        ->mountAction(CreateAction::class)
        ->assertSchemaStateSet([
            'reading_level' => ReadingLevel::MidGroup5,
            'level' => 23,
        ], schema: 'mountedActionSchema0');
});

it('validates required fields and the level range', function (): void {
    Queue::fake();

    livewire(ListComprehensionExercises::class)
        ->callAction(CreateAction::class, [
            'reading_level' => null,
            'topic' => null,
            'level' => 51,
        ])
        ->assertHasActionErrors([
            'reading_level' => 'required',
            'topic' => 'required',
            'level' => 'max',
        ]);

    expect(ComprehensionExercise::count())->toBe(0);
    Queue::assertNothingPushed();
});

it('lists exercises with their statuses', function (): void {
    $pending = ComprehensionExercise::factory()->create();
    $generated = ComprehensionExercise::factory()->generated()->create();
    $failed = ComprehensionExercise::factory()->failed()->create();

    livewire(ListComprehensionExercises::class)
        ->assertCanSeeTableRecords([$pending, $generated, $failed]);
});

it('shows a generated exercise on the view page', function (): void {
    $exercise = ComprehensionExercise::factory()->generated()->create();

    livewire(ViewComprehensionExercise::class, ['record' => $exercise->id])
        ->assertOk()
        ->assertSee($exercise->title)
        ->assertSeeInOrder([
            __('admin.comprehension_exercise.fields.question').' 1',
            $exercise->questions[0]['question'],
            __('admin.comprehension_exercise.fields.question').' 5',
            $exercise->questions[4]['question'],
        ]);
});

it('shows the status instead of content for a pending exercise', function (): void {
    $exercise = ComprehensionExercise::factory()->create();

    livewire(ViewComprehensionExercise::class, ['record' => $exercise->id])
        ->assertOk()
        ->assertSee(__('admin.comprehension_exercise.status_messages.pending'));
});

it('deletes an exercise from the table', function (): void {
    $exercise = ComprehensionExercise::factory()->generated()->create();

    livewire(ListComprehensionExercises::class)
        ->callAction(TestAction::make(DeleteAction::getDefaultName())->table($exercise));

    assertDatabaseMissing(ComprehensionExercise::class, ['id' => $exercise->id]);
});

it('regenerates a failed exercise', function (): void {
    Queue::fake();

    $exercise = ComprehensionExercise::factory()->failed()->create();

    livewire(ListComprehensionExercises::class)
        ->callAction(TestAction::make('regenerate')->table($exercise))
        ->assertNotified();

    expect($exercise->refresh()->failed_at)->toBeNull();
    Queue::assertPushed(GenerateComprehensionExercise::class);
});

it('hides the regenerate action for exercises that are not failed', function (): void {
    $exercise = ComprehensionExercise::factory()->generated()->create();

    livewire(ListComprehensionExercises::class)
        ->assertActionHidden(TestAction::make('regenerate')->table($exercise));
});
