## Why

Een puzzel op level 1 komt in de praktijk te moeilijk uit. Het level stuurt op dit moment alleen het type aanwijzing en niet de woordkeuze, dus groep 6 op level 1 levert woorden als TERRITORIUM en TELESCOPEN op: een makkelijke soort aanwijzing bij een moeilijk woord blijft een moeilijke puzzel. Daarnaast spreken twee regels elkaar tegen, want band 1-10 vraagt om een omschrijving "waarin het sleutelwoord bijna genoemd wordt" terwijl de agent-instructie verbiedt het woord te noemen, ook als deel van een samenstelling. Het resultaat zijn aanwijzingen die om zichzelf heen draaien, zoals "Mensen die voor de dieren zorgen; ze verzorgen ze."

Uit gebruikersfeedback kwam bovendien dat een aanwijzing beter werkt als invulzin: het kind leest een zin met een open plek en vult het antwoord in, in plaats van een losse omschrijving te moeten omzetten in een woord.

## What Changes

- Elke aanwijzing wordt een **invulzin** die eindigt op een open plek waar precies het gezochte woord in past, voor alle levels 1 tot en met 50. Dus "Mensen die voor dieren in het park zorgen heten ..." in plaats van "Mensen die voor de dieren zorgen; ze verzorgen ze."
- De maximale aanwijzingslengte gaat omhoog van twaalf naar zestien woorden, want een invulzin heeft aanloop nodig voordat de open plek valt.
- Het **level stuurt voortaan ook de woordkeuze**, niet alleen het type aanwijzing: op lage levels concrete, alledaagse woorden die een kind al kent, op hoge levels de abstractere en meer vaktalige woorden uit de tekst. De groep blijft de lengte en de woordsoort bepalen; het level kiest binnen die ruimte.
- De **tegenspraak bij band 1-10 wordt opgelost**: bij die band mag een deel van een samenstelling wel in de invulzin voorkomen, want daar is "bijna noemen" het doel. Het volledige woord blijft bij elke band verboden.
- De **bronvermelding komt bij elke aanwijzing op het werkblad**, ongeacht het level. De drempel op level 21 vervalt en `CrosswordPuzzle::showsSource()` verdwijnt.

Bestaande puzzels behouden hun opgeslagen aanwijzingen; alleen opnieuw genereren levert invulzinnen op. Opnieuw leggen raakt de aanwijzingen niet.

## Capabilities

### New Capabilities

Geen.

### Modified Capabilities

- `crossword-word-generation`: de vorm van de aanwijzing wordt vastgelegd als invulzin met een maximale lengte, het level krijgt naast het type aanwijzing ook zeggenschap over de woordkeuze, en de regel over het niet noemen van het woord wordt per band genuanceerd.
- `crossword-puzzle-management`: het werkblad toont de bron-oefening bij elke aanwijzing op elk level in plaats van vanaf level 21.

## Impact

- `app/Ai/Agents/CrosswordWordWriter.php`: instructies en `promptFor()`, inclusief de constante voor de maximale aanwijzingslengte.
- `app/Enums/ClueBand.php` en `lang/nl/common.php`: de banddescripties krijgen naast het type aanwijzing ook een woordkeuze-omschrijving.
- `app/Models/CrosswordPuzzle.php`: `showsSource()` verdwijnt.
- `resources/views/crossword-puzzles/worksheet.blade.php`: de bronvermelding verliest zijn conditie op het level.
- `tests/Feature/CrosswordPuzzlePrintTest.php` en `tests/Feature/Jobs/GenerateCrosswordPuzzleTest.php`: het scenario "geen bronvermelding bij laag level" verdwijnt en de prompt-assertions verschuiven.
- Geen migratie en geen wijziging aan opgeslagen data.
- Buiten scope: de begrijpend-leesteksten en `ExerciseWriter` blijven ongewijzigd.
