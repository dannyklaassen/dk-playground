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

    /** An invulzin needs a run-up before the gap falls, so it gets more room than a bare description. */
    public const int MAX_CLUE_WORDS = 16;

    public function instructions(): string
    {
        $count = self::WORDS_PER_EXERCISE;
        $clueWords = self::MAX_CLUE_WORDS;

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
            - Elke aanwijzing is een INVULZIN: een zin met een open plek waar precies het
              gezochte woord in past. Schrijf die open plek als precies drie puntjes: ...
              Bijvoorbeeld: "Mensen die voor dieren in het park zorgen heten ..."
            - Waar de open plek in de zin valt, staat bij de opdracht; volg dat strikt.
            - De zin moet grammaticaal kloppen zodra het woord is ingevuld, inclusief lidwoord,
              enkelvoud of meervoud en woordvolgorde.
            - Schrijf geen losse omschrijving en geen vraag; altijd een zin met een open plek.
            - VARIEER de zinsbouw. Hoogstens drie van de {$count} aanwijzingen gebruiken
              "heet ...", "heten ..." of "is een ...". Maak van de rest een gewone zin waarin
              het woord het lijdend voorwerp of een bepaling is, bijvoorbeeld:
              "Tegen de modder trekt de verzorger hoge ..."
              "'s Nachts drinken de dieren uit de grote ..."
            - Begin niet elke aanwijzing met hetzelfde woord.
            - Houd de aanwijzing op hooguit {$clueWords} woorden, de drie puntjes niet meegeteld.
            - De aanwijzing bevat het gezochte woord NOOIT voluit.
            - Het type aanwijzing volgt strikt het opgegeven aanwijzingstype.
            - Taalgebruik en zinsbouw passen bij de opgegeven groep.
            - De aanwijzing is ondubbelzinnig: er is maar één woord uit de tekst dat past. Noem
              dus altijd een kenmerk dat alleen bij dat woord hoort, ook in een gewone zin. Een
              zin die enkel vertelt wat er gebeurt ("... controleren de verzorgers de ...") past
              op meerdere woorden en is daarmee fout. Variatie gaat nooit voor eenduidigheid.

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

        // Only the lowest band may echo part of a compound; see ClueBand::allowsWordPart().
        $wordPart = $band->allowsWordPart()
            ? 'Een deel van een samenstelling mag wel in de zin voorkomen, want de aanwijzing mag het woord bijna noemen.'
            : 'Vermijd ook een deel van een samenstelling van het gezochte woord.';

        // See ClueBand::requiresGapAtEnd(): a gap mid-sentence is the harder read.
        $gapPosition = $band->requiresGapAtEnd()
            ? 'Zet de open plek bij elke aanwijzing aan het EIND van de zin, zodat het kind een hele aanloop leest en daarna invult. Ook dan hoeft de zin geen definitie te zijn: een gewone zin die op zijn lijdend voorwerp of bepaling eindigt, eindigt net zo goed op de open plek. Bijvoorbeeld "Tegen de modder trekken de verzorgers hoge ..." in plaats van "Hoge schoenen tegen modder heten ...".'
            : 'Laat de open plek bij minstens twee aanwijzingen MIDDENIN de zin vallen, zodat het kind ook de woorden na de open plek moet gebruiken. Bijvoorbeeld: "Uit de gevulde ... kunnen de dieren drinken."';

        return <<<PROMPT
            Kies {$count} kernwoorden met een aanwijzing uit deze tekst.

            - Groep: {$group->getLabel()}
            - Woordlengte: minimaal {$group->minWordLength()} en maximaal {$group->maxWordLength()} letters
            - Toegestane woordsoorten: {$group->wordTypes()}

            Welke woorden je kiest ({$band->getLabel()}):
            {$band->getWordChoice()}
            De groep hierboven bepaalt de lengte en de woordsoort; kies binnen die ruimte.

            Type aanwijzing ({$band->getLabel()}):
            {$band->getDescription()}
            {$wordPart}

            Waar de open plek valt ({$band->getLabel()}):
            {$gapPosition}

            ## Tekst: {$exercise->title}

            {$paragraphs}
            PROMPT;
    }
}
