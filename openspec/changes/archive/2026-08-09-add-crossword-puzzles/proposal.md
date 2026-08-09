## Why

De app genereert begrijpend-leesoefeningen, maar na het lezen blijft het bij vijf meerkeuzevragen. Er is geen manier om de woordenschat uit meerdere gelezen teksten terug te laten komen in een vorm die kinderen zelf leuk vinden. Een printbare kruiswoordpuzzel over 3 tot 6 gelezen teksten sluit die lus: het kind moet terug naar de teksten, herhaalt de kernwoorden en doet dat als spelletje in plaats van als toets.

## What Changes

- Nieuwe Filament-resource `CrosswordPuzzle` in het admin-panel, gemodelleerd naar de bestaande `ComprehensionExercise`-resource: aanmaken via slide-over, lijst met live generatiestatus, detailweergave, printbaar werkblad en antwoordblad.
- Aanmaken gebeurt door 3 tot 6 gegenereerde begrijpend-leesoefeningen te selecteren, plus een groep (4 t/m 8) en een level (1-50).
- Twee niveau-assen met een eigen taak: de **groep** bepaalt hoeveel woorden de puzzel telt en hoe lang die mogen zijn, het **level** bepaalt uitsluitend wat voor soort aanwijzing bij een woord hoort (van letterlijke omschrijving tot hoofdgedachte).
- Woordkeuze en aanwijzingen komen van AI, met één aparte call per geselecteerde tekst. Het rooster wordt daarna volledig zonder AI gelegd met een deterministisch criss-cross-algoritme.
- Naast "opnieuw genereren" (nieuwe woorden, kost een AI-call) komt er een aparte actie "opnieuw leggen" die alleen een nieuwe seed gebruikt en dus gratis en instant is.
- Het werkblad is één document van twee pagina's: rooster op pagina 1, aanwijzingen op pagina 2. Het ingevulde antwoordblad blijft een aparte route zodat het niet per ongeluk meegeprint wordt.
- Geen nieuwe dependencies. Printen gaat via hetzelfde Blade-plus-printstylesheet-patroon dat de oefeningen al gebruiken.

## Capabilities

### New Capabilities

- `crossword-puzzle-management`: Beheer van kruiswoordpuzzels in het admin-panel: aanmaken via slide-over met tekstselectie, groep en level, lijst met generatiestatus, detailweergave, printbaar werkblad en antwoordblad, opnieuw genereren, opnieuw leggen en verwijderen, afgeschermd met een policy en volledig Nederlandstalig.
- `crossword-word-generation`: Asynchrone AI-generatie van kandidaatwoorden met aanwijzingen, met één agent-call per geselecteerde tekst, structured output, groep- en levelafhankelijke instructies, en programmatische validatie en ontdubbeling van het resultaat.
- `crossword-grid-layout`: Het deterministische criss-cross-plaatsingsalgoritme dat uit de kandidaatwoorden een samenhangend, compact rooster legt: plaatsingsregels, scorefunctie, best-of-N met een opgeslagen seed, en de nummering van de ingangen.

### Modified Capabilities

Geen. De bestaande specs `comprehension-exercise-generation` en `comprehension-exercise-management` veranderen niet: kruiswoordpuzzels lezen bestaande oefeningen alleen uit.

## Impact

**Nieuw**

- Model `CrosswordPuzzle` (ULID) plus koppeling naar `ComprehensionExercise`, met migraties.
- Enums `PuzzleGroup` (aantal woorden en woordlengte per groep) en `ClueBand` (hinttype per levelband).
- AI-agent voor woord- en hintgeneratie, in het patroon van de bestaande `ExerciseWriter`.
- Queued job voor de generatie, met hetzelfde `generated_at`/`failed_at`-statuspatroon.
- Plaatsingsalgoritme als losse, database-loze klasse zodat het in een Unit-test getest kan worden.
- Filament-resource met List- en View-pagina, twee print-controllers en Blade-views, een policy, en Nederlandse vertalingen in `lang/nl/admin.php` en `lang/nl/common.php`.

**Ongewijzigd**

- Geen wijziging aan bestaande modellen, resources of routes.
- Geen nieuwe composer- of npm-dependencies. Er komt bewust geen PDF-library: de gebruiker print het werkblad zelf naar PDF, net als bij de oefeningen.

**Risico's**

- Het plaatsingsalgoritme is het enige echt nieuwe stuk logica. Het is deterministisch met een seed, dus volledig testbaar zonder AI en zonder database.
- De kwaliteit van een puzzel hangt af van bewuste overgeneratie (8 kandidaatwoorden per tekst voor 10 tot 18 plekken). Levert de AI structureel te weinig bruikbare woorden, dan wordt het rooster leeg. De validatieregels moeten daarom afkeuren, niet corrigeren, zodat dat zichtbaar wordt.
