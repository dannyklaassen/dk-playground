## Why

Ouders hebben geen laagdrempelige manier om dagelijks vers, passend begrijpend-leesmateriaal voor hun kinderen te maken. Bestaande methodes zijn statisch en sluiten niet aan bij de interesses van het kind. Dit onderdeel genereert met AI complete, didactisch opgebouwde begrijpend-leesoefeningen (tekst + 5 multiplechoicevragen + antwoordblad) op een gekozen leesniveau, onderwerp en moeilijkheidslevel — printbaar voor dagelijks gebruik.

## What Changes

- Nieuw `ComprehensionExercise` model + migration: leesniveau, onderwerp, optionele omschrijving, level (1-50), en de gegenereerde inhoud (titel, 6 alinea's, 5 vragen als JSON). Lifecycle via `generated_at` / `failed_at` timestamps.
- Nieuwe enums: `ReadingLevel` (begin groep 4 t/m groep 8), `ReadingSkill` (8 leesvaardigheden, Engelse waarden met NL label + omschrijving), `LevelBand` (5 banden van 10 levels, gedeeld tussen UI-helptekst en AI-prompt).
- AI-generatie via `laravel/ai` (nieuwe dependency) + Anthropic Opus: agent-class met structured output (JSON schema), aangeroepen vanuit een queued job met programmatische validatie (aantallen, A-D-verdeling, alineaverwijzing, evidence-check) en retry.
- Filament-resource "Begrijpend lezen" in het admin-panel: List + View, create via slide-over, status zichtbaar in de lijst.
- Twee printbare views (Blade + print-CSS, browser-print): werkblad (tekst + vragen, zonder antwoorden) en antwoordblad (tabel met goed antwoord, alinea, leesvaardigheid, bewijszin).
- Nederlandse vertalingen (`lang/nl/admin.php`, `common.php`) voor alle labels.

## Capabilities

### New Capabilities

- `comprehension-exercise-management`: aanmaken, bekijken, verwijderen en printen van begrijpend-leesoefeningen in het admin-panel, inclusief statusweergave van de generatie.
- `comprehension-exercise-generation`: AI-generatie van een complete oefening (tekst, vragen, antwoorden) op basis van leesniveau, onderwerp, omschrijving en level, met kwaliteitsvalidatie en retry.

### Modified Capabilities

<!-- geen bestaande capabilities -->

## Impact

- Nieuwe dependency: `laravel/ai` (+ `ANTHROPIC_API_KEY` in `.env`).
- Nieuwe bestanden: model, factory, migration, 3 enums, agent-class, job, Filament-resource (List + View + schemas/tables), 2 printroutes + Blade-views, policy, lang-bestanden, Pest-tests.
- Bestaand geraakt: `AppServiceProvider` (alleen als Filament-defaults nog ontbreken), routes-bestand voor de printpagina's, `config/ai.php` (publiceren).
- Queue: gebruikt de bestaande database-queue; generatie duurt 30-60s per oefening.
