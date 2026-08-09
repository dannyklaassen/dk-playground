<?php

declare(strict_types=1);

use App\Ai\Agents\CrosswordWordWriter;
use App\Enums\PuzzleGroup;
use App\Filament\Resources\ComprehensionExercises\ComprehensionExerciseResource;
use App\Filament\Resources\CrosswordPuzzles\Pages\ListCrosswordPuzzles;
use App\Filament\Resources\CrosswordPuzzles\Pages\ViewCrosswordPuzzle;
use App\Jobs\GenerateCrosswordPuzzle;
use App\Models\ComprehensionExercise;
use App\Models\CrosswordPuzzle;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

describe('creating a puzzle', function (): void {
    it('creates a puzzle with four texts and dispatches the generation job', function (): void {
        Queue::fake();

        $exercises = ComprehensionExercise::factory()->generated()->count(4)->create();

        livewire(ListCrosswordPuzzles::class)
            ->callAction(CreateAction::class, [
                'title' => 'Dierenpuzzel',
                'exercises' => $exercises->pluck('id')->all(),
                'group' => 'group_6',
                'level' => 15,
            ])
            ->assertNotified()
            ->assertHasNoFormErrors();

        $puzzle = CrosswordPuzzle::sole();

        expect($puzzle->title)->toBe('Dierenpuzzel')
            ->and($puzzle->group->value)->toBe('group_6')
            ->and($puzzle->level)->toBe(15)
            ->and($puzzle->generated_at)->toBeNull()
            ->and($puzzle->failed_at)->toBeNull()
            ->and($puzzle->exercises)->toHaveCount(4);

        Queue::assertPushed(GenerateCrosswordPuzzle::class);
    });

    it('rejects a puzzle with fewer than three texts', function (): void {
        Queue::fake();

        $exercises = ComprehensionExercise::factory()->generated()->count(2)->create();

        livewire(ListCrosswordPuzzles::class)
            ->callAction(CreateAction::class, [
                'exercises' => $exercises->pluck('id')->all(),
                'group' => 'group_6',
                'level' => 15,
            ])
            ->assertHasActionErrors(['exercises']);

        expect(CrosswordPuzzle::count())->toBe(0);
        Queue::assertNothingPushed();
    });

    it('rejects a puzzle with more than six texts', function (): void {
        Queue::fake();

        $exercises = ComprehensionExercise::factory()->generated()->count(7)->create();

        livewire(ListCrosswordPuzzles::class)
            ->callAction(CreateAction::class, [
                'exercises' => $exercises->pluck('id')->all(),
                'group' => 'group_6',
                'level' => 15,
            ])
            ->assertHasActionErrors(['exercises']);

        expect(CrosswordPuzzle::count())->toBe(0);
        Queue::assertNothingPushed();
    });

    it('refuses a text that was never generated, even when it is submitted directly', function (): void {
        Queue::fake();

        $exercises = ComprehensionExercise::factory()->generated()->count(2)->create();
        $ungenerated = ComprehensionExercise::factory()->create();

        livewire(ListCrosswordPuzzles::class)
            ->callAction(CreateAction::class, [
                'exercises' => [...$exercises->pluck('id')->all(), $ungenerated->id],
                'group' => 'group_6',
                'level' => 15,
            ])
            ->assertHasActionErrors(['exercises.2']);

        expect(CrosswordPuzzle::count())->toBe(0);
        Queue::assertNothingPushed();
    });

    it('validates the required fields and the level range', function (): void {
        Queue::fake();

        livewire(ListCrosswordPuzzles::class)
            ->callAction(CreateAction::class, [
                'exercises' => [],
                'group' => null,
                'level' => 51,
            ])
            ->assertHasActionErrors([
                'exercises' => 'required',
                'group' => 'required',
                'level' => 'max',
            ]);

        expect(CrosswordPuzzle::count())->toBe(0);
        Queue::assertNothingPushed();
    });

    it('defaults the group and the level to those of the most recent puzzle', function (): void {
        CrosswordPuzzle::factory()->create(['group' => 'group_8', 'level' => 42, 'created_at' => now()->subDay()]);

        livewire(ListCrosswordPuzzles::class)
            ->mountAction(CreateAction::class)
            ->assertActionDataSet([
                'group' => PuzzleGroup::Group8,
                'level' => 42,
            ]);
    });
});

describe('the text picker', function (): void {
    it('only offers exercises that have been generated', function (): void {
        $generated = ComprehensionExercise::factory()->generated()->create(['title' => 'Klaar om te kiezen']);
        ComprehensionExercise::factory()->create(['title' => 'Nog bezig']);
        ComprehensionExercise::factory()->failed()->create(['title' => 'Mislukte tekst']);

        livewire(ListCrosswordPuzzles::class)
            ->mountAction(CreateAction::class)
            ->assertFormFieldExists(
                'exercises',
                fn (Select $field): bool => array_keys($field->getOptions()) === [$generated->id],
            );
    });

    it('offers the newest texts first', function (): void {
        $oldest = ComprehensionExercise::factory()->generated()->create(['created_at' => now()->subDays(3)]);
        $newest = ComprehensionExercise::factory()->generated()->create(['created_at' => now()]);
        $middle = ComprehensionExercise::factory()->generated()->create(['created_at' => now()->subDay()]);

        livewire(ListCrosswordPuzzles::class)
            ->mountAction(CreateAction::class)
            ->assertFormFieldExists(
                'exercises',
                fn (Select $field): bool => array_keys($field->getOptions())
                    === [$newest->id, $middle->id, $oldest->id],
            );
    });

    it('preloads only a first batch of texts instead of every one of them', function (): void {
        ComprehensionExercise::factory()->generated()->count(25)->create();

        livewire(ListCrosswordPuzzles::class)
            ->mountAction(CreateAction::class)
            ->assertFormFieldExists('exercises', fn (Select $field): bool => count($field->getOptions()) === 20);
    });

    it('searches the remaining texts in the database instead of only the preloaded ones', function (): void {
        // Older than the 20 newest, so it is not in the preloaded batch.
        $buried = ComprehensionExercise::factory()->generated()->create([
            'title' => 'De krokodil, een oeroude jager',
            'created_at' => now()->subYear(),
        ]);
        ComprehensionExercise::factory()->generated()->count(25)->create(['title' => 'Iets heel anders']);

        livewire(ListCrosswordPuzzles::class)
            ->mountAction(CreateAction::class)
            ->assertFormFieldExists('exercises', function (Select $field) use ($buried): bool {
                expect(array_keys($field->getOptions()))->not->toContain($buried->id);

                return array_keys($field->getSearchResults('krokodil')) === [$buried->id];
            });
    });

    it('never offers a text that has not been generated, not even through search', function (): void {
        $failed = ComprehensionExercise::factory()->failed()->create(['title' => 'Mislukte krokodillentekst']);

        livewire(ListCrosswordPuzzles::class)
            ->mountAction(CreateAction::class)
            ->assertFormFieldExists('exercises', function (Select $field) use ($failed): bool {
                expect($field->getSearchResults('krokodil'))->toBe([]);

                return ! array_key_exists($failed->id, $field->getOptions());
            });
    });

    it('shows the creation date under the title of every selectable text', function (): void {
        $exercise = ComprehensionExercise::factory()->generated()->create([
            'title' => 'Het leven van de tijger',
            'created_at' => '2026-03-09 10:00:00',
        ]);

        livewire(ListCrosswordPuzzles::class)
            ->mountAction(CreateAction::class)
            ->assertFormFieldExists(
                'exercises',
                fn (Select $field): bool => $field->getOptions()[$exercise->id]
                    === 'Het leven van de tijger<br><span style="font-size: .75rem; opacity: .65;">09-03-2026</span>',
            );
    });
});

describe('the table', function (): void {
    it('lists exactly the intended columns, with the agreed headings', function (): void {
        livewire(ListCrosswordPuzzles::class)
            ->assertTableColumnExists(
                'title',
                fn (TextColumn $column): bool => $column->getLabel() === __('admin.crossword_puzzle.fields.title'),
            )
            ->assertTableColumnExists(
                'group',
                fn (TextColumn $column): bool => $column->getLabel() === __('admin.crossword_puzzle.fields.group'),
            )
            ->assertTableColumnExists(
                'level',
                fn (TextColumn $column): bool => $column->getLabel() === __('admin.crossword_puzzle.fields.level'),
            )
            ->assertTableColumnExists(
                'status',
                fn (TextColumn $column): bool => $column->getLabel() === __('admin.crossword_puzzle.columns.status'),
            )
            ->assertTableColumnExists(
                'created_at',
                fn (TextColumn $column): bool => $column->getLabel() === __('admin.crossword_puzzle.columns.created_at'),
            )
            ->assertTableColumnDoesNotExist('exercises_count');
    });

    it('lists puzzles with their statuses', function (): void {
        $pending = CrosswordPuzzle::factory()->create();
        $generated = CrosswordPuzzle::factory()->generated()->create();
        $failed = CrosswordPuzzle::factory()->failed()->create();

        livewire(ListCrosswordPuzzles::class)
            ->assertCanSeeTableRecords([$pending, $generated, $failed])
            ->assertSee(__('common.exercise_status.pending'))
            ->assertSee(__('common.exercise_status.generated'))
            ->assertSee(__('common.exercise_status.failed'));
    });

    it('sorts on status from pending through generated to failed', function (): void {
        $generated = CrosswordPuzzle::factory()->generated()->create();
        $failed = CrosswordPuzzle::factory()->failed()->create();
        $pending = CrosswordPuzzle::factory()->create();

        livewire(ListCrosswordPuzzles::class)
            ->sortTable('status')
            ->assertCanSeeTableRecords([$pending, $generated, $failed], inOrder: true)
            ->sortTable('status', 'desc')
            ->assertCanSeeTableRecords([$failed, $generated, $pending], inOrder: true);
    });

    it('searches puzzles by their name', function (): void {
        $tigers = CrosswordPuzzle::factory()->create(['title' => 'Tijgerpuzzel']);
        $others = CrosswordPuzzle::factory()->count(2)->create(['title' => 'Iets heel anders']);

        livewire(ListCrosswordPuzzles::class)
            ->searchTable('Tijger')
            ->assertCanSeeTableRecords([$tigers])
            ->assertCanNotSeeTableRecords($others);
    });
});

describe('the view page', function (): void {
    it('shows the grid and the clues on the view page', function (): void {
        $puzzle = CrosswordPuzzle::factory()->generated()->create();

        livewire(ViewCrosswordPuzzle::class, ['record' => $puzzle->id])
            ->assertOk()
            ->assertSee(__('admin.crossword_puzzle.sections.grid'))
            ->assertSee(__('admin.crossword_puzzle.sections.across'))
            ->assertSee(__('admin.crossword_puzzle.sections.down'))
            ->assertSee($puzzle->entries[0]['clue']);
    });

    it('shows the empty grid at the top and the solved grid in the answer sheet section', function (): void {
        $puzzle = CrosswordPuzzle::factory()->generated()->create();

        $letters = collect($puzzle->cells())->flatten(1)->filter()->count();

        $html = livewire(ViewCrosswordPuzzle::class, ['record' => $puzzle->id])
            ->assertOk()
            ->assertSee(__('admin.crossword_puzzle.sections.answer_sheet'))
            ->html();

        // Only the answer sheet fills its squares; the grid above it stays blank.
        expect(substr_count($html, '<span class="letter">'))->toBe($letters);
    });

    it('links every source text to its comprehension exercise', function (): void {
        $puzzle = CrosswordPuzzle::factory()->generated()->create();
        $exercise = ComprehensionExercise::factory()->generated()->create(['title' => 'Het leven van de tijger']);

        $puzzle->exercises()->attach($exercise);
        $puzzle->forceFill([
            'entries' => array_map(
                fn (array $entry): array => [...$entry, 'exercise_id' => $exercise->id],
                $puzzle->entries,
            ),
        ])->save();

        livewire(ViewCrosswordPuzzle::class, ['record' => $puzzle->id])
            ->assertOk()
            ->assertSee('Het leven van de tijger')
            ->assertSee(ComprehensionExerciseResource::getUrl('view', ['record' => $exercise->id]), escape: false);
    });

    it('shows the status instead of content for a pending puzzle', function (): void {
        $puzzle = CrosswordPuzzle::factory()->create();

        livewire(ViewCrosswordPuzzle::class, ['record' => $puzzle->id])
            ->assertOk()
            ->assertSee(__('admin.crossword_puzzle.status_messages.pending'))
            ->assertDontSee(__('admin.crossword_puzzle.sections.grid'));
    });

    it('shows the failure message for a failed puzzle', function (): void {
        $puzzle = CrosswordPuzzle::factory()->failed()->create();

        livewire(ViewCrosswordPuzzle::class, ['record' => $puzzle->id])
            ->assertOk()
            ->assertSee(__('admin.crossword_puzzle.status_messages.failed'))
            ->assertDontSee(__('admin.crossword_puzzle.status_messages.pending'))
            ->assertDontSee(__('admin.crossword_puzzle.sections.grid'));
    });
});

describe('the row actions', function (): void {
    it('relays out the grid with a new seed and without calling the agent', function (): void {
        // Any agent call would now throw instead of being answered by the fake.
        CrosswordWordWriter::fake()->preventStrayPrompts();

        $puzzle = CrosswordPuzzle::factory()->generated()->create(['seed' => 1]);
        $candidates = $puzzle->candidates;

        livewire(ListCrosswordPuzzles::class)
            ->callAction(TestAction::make('relayout')->table($puzzle))
            ->assertNotified(__('admin.crossword_puzzle.notifications.relaid'));

        $puzzle->refresh();

        expect($puzzle->seed)->not->toBe(1)
            ->and($puzzle->candidates)->toBe($candidates)
            ->and($puzzle->entries)->not->toBeEmpty()
            ->and($puzzle->generated_at)->not->toBeNull();
    });

    it('leaves the puzzle alone when the new layout would be worse', function (): void {
        // A single candidate can never fill half of the words, so the reroll is refused.
        $puzzle = CrosswordPuzzle::factory()->generated()->create();
        $puzzle->forceFill(['candidates' => [$puzzle->candidates[0]]])->save();

        $before = $puzzle->only(['seed', 'entries', 'grid_rows', 'grid_cols']);

        livewire(ListCrosswordPuzzles::class)
            ->callAction(TestAction::make('relayout')->table($puzzle))
            ->assertNotified(__('admin.crossword_puzzle.notifications.relayout_failed'));

        expect($puzzle->refresh()->only(array_keys($before)))->toBe($before);
    });

    it('regenerates a puzzle by resetting its status and dispatching the job', function (): void {
        Queue::fake();

        $puzzle = CrosswordPuzzle::factory()->failed()->create();

        livewire(ListCrosswordPuzzles::class)
            ->callAction(TestAction::make('regenerate')->table($puzzle))
            ->assertNotified();

        $puzzle->refresh();

        expect($puzzle->failed_at)->toBeNull()
            ->and($puzzle->generated_at)->toBeNull();

        Queue::assertPushed(GenerateCrosswordPuzzle::class);
    });

    it('hides the relayout action for a puzzle that is not generated', function (): void {
        $puzzle = CrosswordPuzzle::factory()->failed()->create();

        livewire(ListCrosswordPuzzles::class)
            ->assertActionHidden(TestAction::make('relayout')->table($puzzle));
    });

    it('links the print actions to their pages for a generated puzzle', function (): void {
        $puzzle = CrosswordPuzzle::factory()->generated()->create();

        livewire(ListCrosswordPuzzles::class)
            ->assertActionVisible(TestAction::make('worksheet')->table($puzzle))
            ->assertActionHasUrl(
                TestAction::make('worksheet')->table($puzzle),
                route('crossword-puzzles.worksheet', $puzzle),
            )
            ->assertActionVisible(TestAction::make('answerSheet')->table($puzzle))
            ->assertActionHasUrl(
                TestAction::make('answerSheet')->table($puzzle),
                route('crossword-puzzles.answer-sheet', $puzzle),
            );
    });

    it('hides the print actions for a puzzle that is not generated', function (bool $failed): void {
        $puzzle = $failed
            ? CrosswordPuzzle::factory()->failed()->create()
            : CrosswordPuzzle::factory()->create();

        livewire(ListCrosswordPuzzles::class)
            ->assertActionHidden(TestAction::make('worksheet')->table($puzzle))
            ->assertActionHidden(TestAction::make('answerSheet')->table($puzzle));
    })->with([
        'pending' => false,
        'failed' => true,
    ]);

    it('renames a puzzle from the table', function (): void {
        $puzzle = CrosswordPuzzle::factory()->generated()->create(['title' => 'Oude naam']);

        livewire(ListCrosswordPuzzles::class)
            ->callAction(TestAction::make(EditAction::getDefaultName())->table($puzzle), ['title' => 'Nieuwe naam'])
            ->assertNotified()
            ->assertHasNoActionErrors();

        expect($puzzle->refresh()->title)->toBe('Nieuwe naam');
    });

    it('offers only the name in the edit form', function (): void {
        $puzzle = CrosswordPuzzle::factory()->generated()->create();

        $edit = TestAction::make(EditAction::getDefaultName())->table($puzzle);

        livewire(ListCrosswordPuzzles::class)
            ->mountAction($edit)
            ->assertFormFieldExists('title')
            ->assertFormFieldDoesNotExist('exercises')
            ->assertFormFieldDoesNotExist('group')
            ->assertFormFieldDoesNotExist('level');
    });

    it('leaves the generated puzzle untouched when only the name changes', function (): void {
        $puzzle = CrosswordPuzzle::factory()->generated()->create();
        $puzzle->exercises()->attach(ComprehensionExercise::factory()->generated()->count(3)->create());

        $before = $puzzle->only(['group', 'level', 'seed', 'candidates', 'entries', 'grid_rows', 'grid_cols']);

        livewire(ListCrosswordPuzzles::class)
            ->callAction(TestAction::make(EditAction::getDefaultName())->table($puzzle), ['title' => 'Nieuwe naam']);

        $puzzle->refresh();

        expect($puzzle->only(array_keys($before)))->toBe($before)
            ->and($puzzle->exercises)->toHaveCount(3)
            ->and($puzzle->generated_at)->not->toBeNull();
    });

    it('deletes a puzzle from the table', function (): void {
        $puzzle = CrosswordPuzzle::factory()->generated()->create();

        livewire(ListCrosswordPuzzles::class)
            ->callAction(TestAction::make(DeleteAction::getDefaultName())->table($puzzle))
            ->assertNotified();

        assertDatabaseMissing(CrosswordPuzzle::class, ['id' => $puzzle->id]);
    });
});
