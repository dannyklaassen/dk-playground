<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Enums\ReadingSkill;
use App\Models\ComprehensionExercise;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

#[Provider(Lab::Anthropic)]
#[Timeout(240)]
class ExerciseWriter implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        $skills = collect(ReadingSkill::cases())
            ->map(fn (ReadingSkill $skill): string => "- `{$skill->value}` ({$skill->getLabel()}): {$skill->getDescription()}")
            ->implode("\n");

        return <<<PROMPT
            Je bent een ervaren auteur van begrijpend-leesmateriaal voor de Nederlandse basisschool.
            Je schrijft complete, didactisch verantwoorde begrijpend-leesoefeningen: een tekst met
            precies 6 alinea's en precies 5 multiplechoicevragen met antwoordverantwoording.

            ## Tekstontwerp
            - Schrijf een pakkende, nieuwsgierig makende titel.
            - De tekst is 300-450 woorden lang, passend bij het opgegeven leesniveau: korter en
              eenvoudiger voor jongere lezers, langer en rijker voor oudere lezers.
            - De tekst bestaat uit exact 6 alinea's met afwisselende lengte (korte en langere alinea's
              wisselen elkaar af), zodat de tekst prettig leest.
            - Gebruik geen opsommingen, geen dialogen en geen emoji's.
            - De tekst mag geen voorkennis vereisen: alles wat nodig is om de vragen te beantwoorden
              staat in de tekst zelf.
            - Zins- en woordniveau passen bij het opgegeven leesniveau en level: woordlengte,
              zinslengte, verwijswoorden en impliciete verbanden schalen mee met de moeilijkheid.

            ## Vraagontwerp
            - Schrijf exact 5 multiplechoicevragen met elk exact 4 antwoordopties (A, B, C en D).
            - Elke vraag heeft precies één goed antwoord; de drie afleiders zijn geloofwaardig maar
              aantoonbaar fout op basis van de tekst.
            - Varieer de geoefende leesvaardigheden: gebruik minimaal 3 verschillende vaardigheden
              uit de lijst hieronder, passend bij de levelband.
            - Verdeel de goede antwoorden eerlijk over de letters A t/m D: geen letter mag vaker dan
              2 keer het goede antwoord zijn.
            - Spreid de vragen over de hele tekst, maar laat de alinea's waar de antwoorden staan
              NIET oplopen met de vraagnummers (dus niet vraag 1 uit alinea 1, vraag 2 uit alinea 2,
              enzovoort). Hussel de volgorde bewust, bijvoorbeeld: vraag 1 uit alinea 4, vraag 2 uit
              alinea 1, vraag 3 uit alinea 6. Zo moet het kind bij elke vraag echt terugzoeken in de
              tekst.

            ## Leesvaardigheden
            {$skills}

            ## Antwoordverantwoording
            - `answer.choice` is de letter van het goede antwoord.
            - `answer.paragraph` is het nummer (1-6) van de alinea met de beslissende informatie.
            - `answer.evidence` is een LETTERLIJK citaat (een zin of zinsnede, exact overgenomen,
              zonder parafrase) uit de alinea die `answer.paragraph` aanwijst. Gebruik `null` voor
              vaardigheden zonder aanwijsbare zin, zoals de hoofdgedachte van de hele tekst.

            ## Kwaliteitseisen
            - Alles is in correct, natuurlijk Nederlands op kindniveau.
            - Inhoud is feitelijk juist, veilig en geschikt voor basisschoolkinderen.
            - Vragen zijn ondubbelzinnig te beantwoorden met alleen de tekst.
            PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        $skillValues = array_column(ReadingSkill::cases(), 'value');

        // Anthropic structured outputs reject minItems/maxItems and integer
        // minimum/maximum; exact counts and ranges are enforced by the prompt
        // plus the programmatic validation in GenerateComprehensionExercise.
        return [
            'title' => $schema->string()->required(),
            'paragraphs' => $schema->array()
                ->items($schema->string())
                ->required(),
            'questions' => $schema->array()
                ->items($schema->object(fn (JsonSchema $schema): array => [
                    'question' => $schema->string()->required(),
                    'options' => $schema->object(fn (JsonSchema $schema): array => [
                        'A' => $schema->string()->required(),
                        'B' => $schema->string()->required(),
                        'C' => $schema->string()->required(),
                        'D' => $schema->string()->required(),
                    ])->required(),
                    'skill' => $schema->string()->enum($skillValues)->required(),
                    'answer' => $schema->object(fn (JsonSchema $schema): array => [
                        'choice' => $schema->string()->enum(['A', 'B', 'C', 'D'])->required(),
                        'paragraph' => $schema->integer()->required(),
                        'evidence' => $schema->string()->nullable()->required(),
                    ])->required(),
                ]))
                ->required(),
        ];
    }

    public static function promptFor(ComprehensionExercise $exercise): string
    {
        $angle = filled($exercise->description)
            ? "Omschrijving/invalshoek van de ouder: {$exercise->description}"
            : 'Er is geen omschrijving opgegeven: kies zelf een passende, verrassende invalshoek bij het onderwerp.';

        return <<<PROMPT
            Schrijf een begrijpend-leesoefening met deze invoer:

            - Leesniveau: {$exercise->reading_level->getLabel()}
            - Onderwerp: {$exercise->topic}
            - {$angle}
            - Level: {$exercise->level} (van 50)

            Didactische betekenis van dit level ({$exercise->level_band->getLabel()}):
            {$exercise->level_band->getDescription()}
            PROMPT;
    }
}
