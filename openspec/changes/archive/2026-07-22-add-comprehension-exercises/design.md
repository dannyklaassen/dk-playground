## Context

Het platform (Laravel 13, Filament v5, vrijwel lege codebase: alleen `User` + admin-panel) krijgt een onderdeel waarmee een ouder AI-gegenereerde begrijpend-leesoefeningen maakt voor basisschoolkinderen. Invoer: leesniveau (begin groep 4 t/m groep 8), onderwerp (vrij tekstveld), optionele omschrijving en een didactisch level (1-50). Uitvoer: een tekst van 6 genummerde alinea's, 5 multiplechoicevragen en een antwoordblad — altijd printbaar, nooit digitaal af te nemen. De volledige functionele/didactische specificatie (leerlijn, levelbanden, ontwerpprincipes voor tekst en vragen, kwaliteitseisen) is door de gebruiker aangeleverd en wordt de system prompt van de AI-agent.

## Goals / Non-Goals

**Goals:**
- Eén oefening per keer genereren op basis van vier invoervelden, opgeslagen als `ComprehensionExercise`.
- Betrouwbare, gestructureerde AI-uitvoer (geen markdown parsen) met programmatische kwaliteitsvalidatie en automatische retry.
- Printbaar werkblad (zonder antwoorden) + los antwoordblad voor de ouder.
- Status van de generatie zichtbaar in het admin-panel.

**Non-Goals:**
- Geen digitale afname of antwoordregistratie door het kind (papier only).
- Geen `Child`-model of voortgangsbewaking per kind; level wordt handmatig gekozen.
- Geen batch-generatie ("komende 10 alvast").
- Geen tweede AI-reviewcall; inhoudelijke kwaliteit komt uit de prompt + programmatische checks.
- Geen beheerde onderwerpenlijst; onderwerp is een vrij tekstveld.

## Decisions

### Naam: `ComprehensionExercise`
"Comprehension" is de vakterm voor begrijpend lezen en laat ruimte voor latere leesoefening-typen (bijv. technisch lezen). Overwogen: `Worksheet` (te generiek zodra er meer werkblad-typen komen), `ReadingExercise` (dekt "begrijpend" niet).

### Inhoud als JSON op de rij, geen aparte vragen-tabel
Een oefening is een onveranderlijk, in één keer gegenereerd geheel; er wordt nooit over vragen heen ge-queried en er is geen digitale afname. `paragraphs` (json, 6 strings) en `questions` (json, 5 objecten) staan op `comprehension_exercises`. Een genormaliseerde vragen-tabel wordt pas relevant bij digitale afname — expliciet buiten scope.

### Vraagstructuur: genest `answer`-object
Per vraag: `question`, `options` (A-D), `skill`, en een genest `answer`-object met `choice` (A-D), `paragraph` (één integer 1-6: de alinea met de beslissende informatie) en `evidence` (nullable: de letterlijke zin/zinsnede waar het antwoord op steunt). De drie antwoord-facetten horen bij elkaar en vormen samen exact één rij van het antwoordblad. `evidence` is nullable omdat vaardigheden als hoofdgedachte geen aanwijsbare zin hebben.

### Lifecycle via timestamps (projectconventie)
Geen status-enum: `generated_at` en `failed_at` (beide nullable). Beide null = bezig; `generated_at` gezet = klaar; `failed_at` gezet = mislukt. Status is een afgeleide accessor.

### AI: `laravel/ai` agent-class met structured output, aangeroepen vanuit een eigen job
`ExerciseWriter` agent implementeert `Agent` + `HasStructuredOutput`; `instructions()` bevat de volledige didactische spec, `schema()` dwingt het uitvoerformaat af (JsonSchema: exact 6 alinea's, exact 5 vragen, enums voor choice/skill). De ingebouwde `->queue()` van laravel/ai wordt **niet** gebruikt: een eigen `GenerateComprehensionExercise`-job roept de agent synchroon aan zodat AI-call, programmatische validatie, retry en opslag op één plek zitten en Laravels job-mechaniek (timeout, failed jobs) het geheel bewaakt.

### Provider: Anthropic, model Opus
Kwaliteit van Nederlands op kindniveau is de bottleneck, niet de prijs (±enkele centen per oefening, 1/dag gebruik). Model-id in config, niet hardcoded.

### Levelbetekenis: `LevelBand`-enum, geen opzoektabel
De spec definieert moeilijkheid per band van 10 levels; een 50-rijen-tabel zou 5 omschrijvingen dupliceren en is data die eigenlijk code is. `LevelBand` (5 cases) met `forLevel(int)`, NL label en omschrijving. Dezelfde omschrijving voedt zowel de helptekst in het formulier als de prompt — één bron, zodat gebruiker en AI dezelfde definitie hanteren. `level` zelf blijft een integer 1-50 in de database.

### Enums Engels met NL-labels
`ReadingLevel` (`begin_group_4` … `group_8`), `ReadingSkill` (8 vaardigheden: `main_idea`, `finding_information`, `cause_and_effect`, `drawing_conclusions`, `reference_words`, `word_meaning_from_context`, `sequence_of_events`, `authors_purpose`) en `LevelBand`. Waarden/cases Engels (DB/code-conventie), labels en omschrijvingen Nederlands via `lang/nl/common.php`. `ReadingSkill` krijgt naast `getLabel()` ook `getDescription()` voor helpteksten.

### Programmatische validatie + retry in de job
Na elke AI-call: exact 6 alinea's, exact 5 vragen à 4 opties, `choice` ∈ A-D, `paragraph` ∈ 1-6, geen antwoordletter vaker dan 2×, minimaal 3 verschillende skills, en — indien `evidence` gezet — genormaliseerde substring-check dat de evidence in de genoemde alinea voorkomt. Faalt een check → nieuwe AI-call (max 3 pogingen totaal), daarna `failed_at`. Deze checks vangen precies de fouten die structured output niet kan afdwingen.

### Print: Blade + print-CSS, browser-print
Twee auth-beveiligde routes (werkblad, antwoordblad) die een printgeoptimaliseerde Blade-view renderen; de browser maakt er PDF van. Geen dompdf/snappy-dependency — onnodig voor thuisgebruik.

## Risks / Trade-offs

- [Evidence-check te streng: model parafraseert i.p.v. citeert → onterechte retries] → Genormaliseerde vergelijking (lowercase, whitespace, leestekens) en de prompt instrueert expliciet letterlijk te citeren; check alleen bij niet-null evidence.
- [Opus-generatie duurt 30-60s; database-queue zonder draaiende worker → oefening blijft "bezig"] → `composer run dev` start de worker al; job-timeout ruim zetten (bijv. 300s) en `failed()`-hook zet `failed_at` zodat de UI nooit eeuwig "bezig" toont.
- [AI levert ondanks prompt+schema 3× ongeldige output → mislukte oefening] → `failed_at` + "Opnieuw genereren"-actie in de UI; kosten van een mislukte poging zijn verwaarloosbaar.
- [`laravel/ai` is 0.x → API kan nog wijzigen] → Acceptabel voor een hobby-/gezinsproject; agent + job kapselen de package-API in, de rest van de app kent alleen het model.
- [Eén alinea per antwoord terwijl hogere levels alinea's combineren] → Bewuste versimpeling: prompt instrueert de alinea met de beslissende informatie te noemen.

## Open Questions

Geen — alle ontwerpbeslissingen zijn in de verkenning met de gebruiker vastgelegd.
