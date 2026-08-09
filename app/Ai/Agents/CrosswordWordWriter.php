<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Enums\ClueBand;
use App\Enums\PuzzleGroup;
use App\Models\ComprehensionExercise;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

#[Provider(Lab::Anthropic)]
#[Timeout(120)]
class CrosswordWordWriter implements Agent, HasStructuredOutput
{
    use Promptable;

    public const int WORDS_PER_EXERCISE = 8;

    public function instructions(): string
    {
        $count = self::WORDS_PER_EXERCISE;

        return <<<PROMPT
            Je kiest kernwoorden uit een begrijpend-leestekst voor een kruiswoordpuzzel voor de
            Nederlandse basisschool, en schrijft bij elk woord een aanwijzing.

            ## Woordkeuze
            - Kies precies {$count} woorden die LETTERLIJK in de aangeleverde tekst voorkomen.
            - Kies inhoudelijke kernwoorden: woorden die ertoe doen voor waar de tekst over gaat.
              Geen lidwoorden, voorzetsels, voegwoorden of hulpwerkwoorden.
            - Elk woord bestaat uit één woord zonder spaties, koppeltekens, apostrofs, cijfers of
              leestekens. Alleen de letters a t/m z; diakrieten worden later weggehaald.
            - Respecteer de opgegeven minimale en maximale woordlengte.
            - Zorg voor spreiding in lengte: minstens 2 van de woorden zijn 9 letters of langer
              (voor zover de maximale woordlengte dat toelaat).
            - Geef elk woord in de vorm waarin het in de tekst staat, zonder het te verbuigen.
            - Kies {$count} verschillende woorden; geen enkelvoud én meervoud van hetzelfde woord.

            ## Aanwijzingen
            - Schrijf bij elk woord één korte aanwijzing van hooguit twaalf woorden.
            - De aanwijzing noemt het woord zelf NIET, ook niet als deel van een samenstelling.
            - Het type aanwijzing volgt strikt het opgegeven aanwijzingstype.
            - Taalgebruik en zinsbouw passen bij de opgegeven groep.
            - De aanwijzing is ondubbelzinnig: er is maar één woord uit de tekst dat past.

            ## Kwaliteitseisen
            - Alles in correct, natuurlijk Nederlands op kindniveau.
            - Feitelijk juist en veilig voor basisschoolkinderen.
            PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        // Anthropic structured outputs reject minItems/maxItems; the exact count
        // is enforced by the prompt plus the programmatic validation afterwards.
        return [
            'words' => $schema->array()
                ->items($schema->object(fn (JsonSchema $schema): array => [
                    'word' => $schema->string()->required(),
                    'clue' => $schema->string()->required(),
                ]))
                ->required(),
        ];
    }

    public static function promptFor(ComprehensionExercise $exercise, PuzzleGroup $group, ClueBand $band): string
    {
        $count = self::WORDS_PER_EXERCISE;
        $paragraphs = implode("\n\n", $exercise->paragraphs ?? []);

        return <<<PROMPT
            Kies {$count} kernwoorden met een aanwijzing uit deze tekst.

            - Groep: {$group->getLabel()}
            - Woordlengte: minimaal {$group->minWordLength()} en maximaal {$group->maxWordLength()} letters
            - Toegestane woordsoorten: {$group->wordTypes()}

            Type aanwijzing ({$band->getLabel()}):
            {$band->getDescription()}

            ## Tekst: {$exercise->title}

            {$paragraphs}
            PROMPT;
    }
}
