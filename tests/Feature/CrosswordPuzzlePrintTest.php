<?php

declare(strict_types=1);

use App\Models\ComprehensionExercise;
use App\Models\CrosswordPuzzle;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/** A generated puzzle whose entries all point at one attached source text. */
function puzzleWithSource(int $level): CrosswordPuzzle
{
    $puzzle = CrosswordPuzzle::factory()->generated()->create(['level' => $level, 'title' => 'Dierenpuzzel']);
    $exercise = ComprehensionExercise::factory()->generated()->create(['title' => 'Het leven van de tijger']);

    $puzzle->exercises()->attach($exercise);

    $puzzle->forceFill([
        'entries' => array_map(
            fn (array $entry): array => [...$entry, 'exercise_id' => $exercise->id],
            $puzzle->entries,
        ),
    ])->save();

    return $puzzle;
}

/**
 * Every solution letter of the grid, lower case, in the order the cells render.
 *
 * @return list<string>
 */
function solutionLetters(CrosswordPuzzle $puzzle): array
{
    return collect($puzzle->cells())
        ->flatten(1)
        ->filter()
        ->pluck('letter')
        ->map(fn (string $letter): string => mb_strtolower($letter))
        ->values()
        ->all();
}

it('shows the worksheet with the clues but without any answer', function (): void {
    $puzzle = CrosswordPuzzle::factory()->generated()->create();

    $response = actingAs(User::factory()->create())
        ->get(route('crossword-puzzles.worksheet', $puzzle))
        ->assertOk()
        ->assertSee(__('admin.crossword_puzzle.print.across'))
        ->assertSee(__('admin.crossword_puzzle.print.down'))
        ->assertSee($puzzle->entries[0]['clue']);

    foreach ($puzzle->entries as $entry) {
        $response->assertDontSee($entry['word']);
    }

    expect(substr_count($response->getContent(), '<span class="letter">'))->toBe(0);
});

it('shows the answer sheet with every letter filled in, in lower case', function (): void {
    $puzzle = CrosswordPuzzle::factory()->generated()->create();
    $letters = solutionLetters($puzzle);

    $html = actingAs(User::factory()->create())
        ->get(route('crossword-puzzles.answer-sheet', $puzzle))
        ->assertOk()
        ->assertSee(__('admin.crossword_puzzle.print.answer_sheet_title'))
        ->getContent();

    preg_match_all('/<span class="letter">(.+?)<\/span>/', $html, $matches);

    // Ten entries of 58 letters minus their ten shared squares, every one of
    // them in its own cell.
    expect($letters)->toHaveCount(48)
        ->and($matches[1])->toBe($letters);
});

it('asks for the solution word on the worksheet without giving it away', function (): void {
    $puzzle = CrosswordPuzzle::factory()->generated()->create();
    $solutionWord = $puzzle->solutionWord();

    $html = actingAs(User::factory()->create())
        ->get(route('crossword-puzzles.worksheet', $puzzle))
        ->assertOk()
        ->assertSee(__('admin.crossword_puzzle.print.solution_word'))
        ->assertDontSee($solutionWord->word)
        ->assertDontSee($solutionWord->clue)
        ->getContent();

    // One marked square in the grid and one empty box underneath per letter.
    expect(substr_count($html, 'class="filled marked"'))->toBe(count($solutionWord->cells))
        ->and(substr_count($html, 'class="solution-label index"'))->toBe(count($solutionWord->cells));
});

it('prints the grey marking instead of letting the browser drop it', function (): void {
    $puzzle = CrosswordPuzzle::factory()->generated()->create();

    $html = actingAs(User::factory()->create())
        ->get(route('crossword-puzzles.worksheet', $puzzle))
        ->assertOk()
        ->getContent();

    expect($html)->toMatch('/\.crossword td\.marked \{[^}]*print-color-adjust: exact/s');
});

it('leaves the marking and the boxes off a worksheet without a solution word', function (): void {
    $puzzle = CrosswordPuzzle::factory()->withoutSolutionWord()->create();

    $html = actingAs(User::factory()->create())
        ->get(route('crossword-puzzles.worksheet', $puzzle))
        ->assertOk()
        ->assertDontSee(__('admin.crossword_puzzle.print.solution_word'))
        ->getContent();

    expect($html)->not->toContain('class="filled marked"')
        ->and($html)->not->toContain('solution-boxes');
});

it('shows the solution word in full on the answer sheet', function (): void {
    $puzzle = CrosswordPuzzle::factory()->generated()->create();
    $solutionWord = $puzzle->solutionWord();

    $html = actingAs(User::factory()->create())
        ->get(route('crossword-puzzles.answer-sheet', $puzzle))
        ->assertOk()
        ->assertSee(__('admin.crossword_puzzle.print.solution_word'))
        ->assertSee(mb_strtolower($solutionWord->word))
        ->getContent();

    expect(substr_count($html, 'class="filled marked"'))->toBe(count($solutionWord->cells));
});

it('keeps the entry number and the solution letter apart on a marked starting square', function (): void {
    // The B of BERGEN moved onto the numbered starting square of KORAAL.
    $puzzle = CrosswordPuzzle::factory()->generated()->create();
    $puzzle->forceFill([
        'solution_word' => [
            ...$puzzle->solution_word,
            'cells' => [['row' => 3, 'col' => 15], ['row' => 2, 'col' => 0]],
            'word' => 'BK',
        ],
    ])->save();

    $html = actingAs(User::factory()->create())
        ->get(route('crossword-puzzles.worksheet', $puzzle))
        ->assertOk()
        ->getContent();

    // A digit for the entry and a letter for the solution word, in their own corners.
    expect($html)->toMatch(
        '/<td class="filled marked">\s*<span class="number">4<\/span>\s*<span class="solution-label">b<\/span>/',
    );
});

it('names the source text on the worksheet on every level', function (int $level): void {
    $puzzle = puzzleWithSource($level);

    actingAs(User::factory()->create())
        ->get(route('crossword-puzzles.worksheet', $puzzle))
        ->assertOk()
        ->assertSee('Het leven van de tijger');
})->with([
    'lowest level' => 1,
    'below the old threshold' => 8,
    'at the old threshold' => 21,
    'highest level' => 50,
]);

it('lays the clues out in two fixed columns', function (): void {
    $puzzle = puzzleWithSource(8);

    actingAs(User::factory()->create())
        ->get(route('crossword-puzzles.worksheet', $puzzle))
        ->assertOk()
        // Flowing text columns would let "Verticaal" start wherever "Horizontaal" ended.
        ->assertSee('grid-template-columns: 1fr 1fr', escape: false)
        ->assertDontSee('.clues { columns: 2', escape: false);
});

it('keeps rendering the worksheet when an entry carries no source text', function (): void {
    $puzzle = puzzleWithSource(30);

    $puzzle->forceFill([
        'entries' => array_map(
            function (array $entry): array {
                unset($entry['exercise_id']);

                return $entry;
            },
            $puzzle->entries,
        ),
    ])->save();

    actingAs(User::factory()->create())
        ->get(route('crossword-puzzles.worksheet', $puzzle))
        ->assertOk()
        ->assertDontSee('Het leven van de tijger');
});

it('returns 404 for a worksheet of a puzzle that is not generated', function (): void {
    $puzzle = CrosswordPuzzle::factory()->create();

    actingAs(User::factory()->create())
        ->get(route('crossword-puzzles.worksheet', $puzzle))
        ->assertNotFound();
});

it('returns 404 for an answer sheet of a puzzle that is not generated', function (): void {
    $puzzle = CrosswordPuzzle::factory()->failed()->create();

    actingAs(User::factory()->create())
        ->get(route('crossword-puzzles.answer-sheet', $puzzle))
        ->assertNotFound();
});

it('refuses the worksheet to someone who may not view the puzzle', function (): void {
    $puzzle = CrosswordPuzzle::factory()->generated()->create();

    Gate::before(fn (User $user, string $ability): ?bool => $ability === 'view' ? false : null);

    actingAs(User::factory()->create())
        ->get(route('crossword-puzzles.worksheet', $puzzle))
        ->assertForbidden();
});

it('refuses the answer sheet on its own ability, with the worksheet still allowed', function (): void {
    $puzzle = CrosswordPuzzle::factory()->generated()->create();

    Gate::before(fn (User $user, string $ability): ?bool => $ability === 'viewSolution' ? false : null);

    actingAs(User::factory()->create())
        ->get(route('crossword-puzzles.answer-sheet', $puzzle))
        ->assertForbidden();

    actingAs(User::factory()->create())
        ->get(route('crossword-puzzles.worksheet', $puzzle))
        ->assertOk();
});

it('requires authentication for the crossword print pages', function (): void {
    $puzzle = CrosswordPuzzle::factory()->generated()->create();

    get(route('crossword-puzzles.worksheet', $puzzle))
        ->assertRedirect(route('filament.admin.auth.login'));
    get(route('crossword-puzzles.answer-sheet', $puzzle))
        ->assertRedirect(route('filament.admin.auth.login'));
});
