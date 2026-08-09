<?php

declare(strict_types=1);

use App\Ai\Agents\CrosswordWordWriter;
use App\Enums\ClueBand;
use App\Enums\PuzzleGroup;
use App\Models\ComprehensionExercise;

function promptForLevel(int $level, PuzzleGroup $group = PuzzleGroup::Group6): string
{
    return CrosswordWordWriter::promptFor(
        ComprehensionExercise::factory()->generated()->create(['title' => 'Het leven van de tijger']),
        $group,
        ClueBand::forLevel($level),
    );
}

it('gives every band its own word choice instruction', function (): void {
    $choices = array_map(
        fn (ClueBand $band): string => $band->getWordChoice(),
        ClueBand::cases(),
    );

    expect($choices)->each->not->toBeEmpty()
        ->and($choices)->toHaveCount(count(array_unique($choices)));
});

it('tells the agent to pick everyday words on a low level', function (): void {
    expect(promptForLevel(1))
        ->toContain(ClueBand::Band1To10->getWordChoice())
        ->not->toContain(ClueBand::Band41To50->getWordChoice());
});

it('tells the agent to pick abstract words on a high level', function (): void {
    expect(promptForLevel(45))
        ->toContain(ClueBand::Band41To50->getWordChoice())
        ->not->toContain(ClueBand::Band1To10->getWordChoice());
});

it('keeps the word length on the group, not on the level', function (): void {
    // Group 4 at the highest level still asks for short words.
    expect(promptForLevel(45, PuzzleGroup::Group4))
        ->toContain('minimaal 4 en maximaal 7 letters');
});

it('allows part of a compound only on the lowest band', function (): void {
    expect(promptForLevel(3))->toContain('Een deel van een samenstelling mag wel')
        ->and(promptForLevel(25))->toContain('Vermijd ook een deel van een samenstelling');
});

it('asks for a fill-in-the-blank sentence within the word limit', function (): void {
    expect((new CrosswordWordWriter)->instructions())
        ->toContain('INVULZIN')
        ->toContain('hooguit '.CrosswordWordWriter::MAX_CLUE_WORDS.' woorden');
});

it('asks for varied sentence structures on every band', function (): void {
    expect((new CrosswordWordWriter)->instructions())
        ->toContain('VARIEER de zinsbouw');
});

it('keeps the gap at the end of the sentence on the low bands', function (): void {
    expect(promptForLevel(1))->toContain('aan het EIND van de zin')
        ->and(promptForLevel(15))->toContain('aan het EIND van de zin');
});

it('lets the gap fall mid-sentence from band 21-30 upwards', function (): void {
    expect(promptForLevel(25))->toContain('MIDDENIN de zin')
        ->and(promptForLevel(45))->toContain('MIDDENIN de zin');
});
