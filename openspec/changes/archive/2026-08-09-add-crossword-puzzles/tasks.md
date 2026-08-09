## 1. Datamodel en enums

- [x] 1.1 Maak enum `App\Enums\PuzzleGroup` (backed string, `HasLabel`) met cases `Group4` t/m `Group8` en methodes voor aantal woorden, minimale en maximale woordlengte en toegestane woordsoorten, volgens de tabel in de spec
- [x] 1.2 Maak enum `App\Enums\ClueBand` (backed string, `HasLabel`) met de vijf banden 1-10 t/m 41-50, een `forLevel(int $level): self`, en `getDescription()` die het type aanwijzing uit `lang/nl/common.php` haalt, gemodelleerd naar `LevelBand`
- [x] 1.3 Maak enum `App\Enums\Direction` (backed string) met `Across` en `Down`
- [x] 1.4 Maak migratie `create_crossword_puzzles_table` (ULID primary key, kolommen in de vaste volgorde: `group`, `level`, `title`, `seed`, `grid_rows`, `grid_cols`, `candidates` json, `entries` json, dan `generated_at`, `failed_at`, dan timestamps) zonder `down()`
- [x] 1.5 Maak migratie voor de pivot `comprehension_exercise_crossword_puzzle` met auto-increment id en twee `foreignUlid`-kolommen, zonder `down()`
- [x] 1.6 Maak model `App\Models\CrosswordPuzzle` met `HasUlids`, `#[Fillable]`, casts voor de enums en json-kolommen, de `belongsToMany`-relatie naar `ComprehensionExercise`, een afgeleide `status`-attribute en een `clueBand`-attribute
- [x] 1.7 Voeg de omgekeerde `belongsToMany`-relatie toe aan `ComprehensionExercise`
- [x] 1.8 Maak `CrosswordPuzzleFactory` met named states voor gegenereerd, bezig en mislukt

## 2. Plaatsingsalgoritme (zonder database, zonder AI)

- [x] 2.1 Maak value object `App\Support\Crossword\WordCandidate` met woord, aanwijzing en bron-oefening-id
- [x] 2.2 Maak `App\Support\Crossword\Placement` met woord, rij, kolom, richting en het aantal kruisingen
- [x] 2.3 Maak `App\Support\Crossword\Grid` die plaatsingen bijhoudt, botsingen detecteert en de drie plaatsingsregels afdwingt (kruisende letters gelijk, geen parallelle buur, leeg vakje of rand voor en na het woord)
- [x] 2.4 Implementeer in `Grid` het verzamelen van alle geldige kruisingen voor een kandidaat met de al geplaatste woorden
- [x] 2.5 Maak `App\Support\Crossword\LayoutScorer` met de scorefunctie uit de spec, inclusief het strafpunt van 25 per tekst zonder woord in de puzzel
- [x] 2.6 Maak `App\Support\Crossword\LayoutBuilder` die kandidaten, een doelaantal, de bron-oefening-ids en een seed in neemt, 300 pogingen doet met geschudde volgorde en het best scorende rooster teruggeeft
- [x] 2.7 Implementeer het bijsnijden tot de kleinste omsluitende rechthoek en de nummering van de ingangen, waarbij een horizontaal en verticaal woord op hetzelfde startvakje hetzelfde nummer delen
- [x] 2.8 Schrijf Unit-tests in `tests/Unit` voor: de drie plaatsingsregels, gegarandeerde samenhang, reproduceerbaarheid bij gelijke seed, verschil bij een andere seed, scoring van dubbele kruisingen en compactheid, het strafpunt per tekst, gedeelde nummering en het bijsnijden

## 3. AI-generatie van woorden en aanwijzingen

- [x] 3.1 Maak agent `App\Ai\Agents\CrosswordWordWriter` volgens het patroon van `ExerciseWriter` (`Agent` plus `HasStructuredOutput`), met een schema van 8 objecten met `word` en `clue`
- [x] 3.2 Schrijf de agent-instructies: woord komt letterlijk in de tekst voor, lengte binnen de groepsgrenzen, geen spaties of leestekens, aanwijzing noemt het woord niet, taalniveau volgt de groep, type aanwijzing volgt de `ClueBand`, en minstens 2 woorden van 9 letters of langer
- [x] 3.3 Bouw het user-bericht op uit de titel en alinea's van één oefening plus de groepsgrenzen en de bandomschrijving
- [x] 3.4 Maak `App\Support\Crossword\CandidateValidator` die diakrieten normaliseert, hoofdletters afdwingt, en kandidaten afkeurt bij een woord dat niet in de brontekst staat, niet volledig uit A-Z bestaat, buiten de lengtegrenzen valt of een spatie of apostrof bevat
- [x] 3.5 Maak `App\Support\Crossword\CandidateDeduplicator` met de exacte match en de prefixregel (prefix met hooguit drie letters verschil laat het kortste vervallen)
- [x] 3.6 Schrijf Unit-tests voor validator en deduplicator, inclusief de gevallen mug/muggen, plant/planeten, panda's en een woord dat niet in de tekst voorkomt

## 4. Generatie-job

- [x] 4.1 Maak `App\Jobs\GenerateCrosswordPuzzle` met een timeout van minimaal 600 seconden, die per gekoppelde oefening sequentieel de agent aanroept
- [x] 4.2 Voer na de aanroepen validatie en ontdubbeling uit, sla het resultaat op in `candidates`, laat het rooster leggen en sla `entries`, `seed`, `grid_rows` en `grid_cols` op, en zet `generated_at`
- [x] 4.3 Zet `failed_at` bij een onafgevangen exception via `failed()`, en ook wanneer er te weinig bruikbare kandidaten overblijven om een rooster te leggen
- [x] 4.4 Schrijf Feature-tests met een gefakete agent voor: geslaagde generatie, exception leidt tot `failed_at`, en te weinig kandidaten leidt tot `failed_at`

## 5. Filament-resource

- [x] 5.1 Genereer de resource met `--view --not-embedded` en verwijder de gegenereerde Create- en Edit-pagina's, zodat alleen `index` en `view` overblijven
- [x] 5.2 Bouw het formulier: multi-select van gegenereerde `ComprehensionExercise`-records met minimaal 3 en maximaal 6, `PuzzleGroup`-select en level 1-50 met de `ClueBand`-omschrijving als helptekst, gelaagd in een sectie "Algemeen"
- [x] 5.3 Zet `CreateAction` in de header van de lijstpagina met `slideOver()` en dispatch de generatie-job na het aanmaken
- [x] 5.4 Bouw de tabel: titel, groep, level, aantal teksten, status als badge en aanmaakdatum, met polling zolang er een puzzel "bezig" is en zonder bulk-acties
- [x] 5.5 Bouw de infolist voor de View-pagina: sectie "Algemeen" met groep, level plus bandomschrijving en de gebruikte oefeningen, plus het rooster en de aanwijzingen gesplitst in horizontaal en verticaal
- [x] 5.6 Maak `RelayoutCrosswordPuzzleAction` als eigen actieklasse die een nieuwe seed zet, het rooster opnieuw laat leggen en `entries` herschrijft zonder AI-aanroep
- [x] 5.7 Maak `RegenerateCrosswordPuzzleAction` als eigen actieklasse die `generated_at` en `failed_at` op null zet en de job opnieuw dispatcht
- [x] 5.8 Maak `OpenWorksheetAction` en `OpenAnswerSheetAction` naar het voorbeeld van de bestaande oefening-acties
- [x] 5.9 Groepeer alle rij-acties en de header-acties van de View-pagina in een `ActionGroup` met `Heroicon::EllipsisVertical`
- [x] 5.10 Maak `CrosswordPuzzlePolicy` met alle zeven abilities, waarbij `forceDelete()` `false` teruggeeft, en registreer hem

## 6. Printbladen

- [x] 6.1 Maak `CrosswordPuzzleWorksheetController` en `CrosswordPuzzleAnswerSheetController` die een 404 geven zolang `generated_at` null is, plus de bijbehorende routes
- [x] 6.2 Maak een gedeelde Blade-partial voor het rooster die zowel leeg (met alleen de nummers) als ingevuld gerenderd kan worden
- [x] 6.3 Bereken de hokjesgrootte in PHP uit de roosterafmeting en de beschikbare 17cm, afgetopt op ongeveer 12mm, en geef die door als CSS-variabele
- [x] 6.4 Maak `worksheet.blade.php`: pagina 1 met het lege rooster, een pagina-einde, en pagina 2 met de aanwijzingen in twee kolommen onder "Horizontaal" en "Verticaal", met bronvermelding bij level 21 en hoger
- [x] 6.5 Maak `answer-sheet.blade.php` met het ingevulde rooster in dezelfde printopmaak
- [x] 6.6 Schrijf Feature-tests voor beide routes: 404 bij een niet-gegenereerde puzzel, het werkblad bevat geen enkele ingevulde letter, bronvermelding wel bij level 30 en niet bij level 8

## 7. Vertalingen

- [x] 7.1 Voeg `crossword_puzzle` toe aan `lang/nl/admin.php` met `singular` en `plural` in kleine letters, plus `fields`, `columns`, `sections`, `actions`, `notifications` en `print`
- [x] 7.2 Voeg aan `lang/nl/common.php` de groepen `puzzle_group`, `clue_band` en `clue_band_descriptions` toe
- [x] 7.3 Controleer dat er geen Nederlandse tekst hardcoded in PHP of Blade staat

## 8. Tests en kwaliteitsgate

- [x] 8.1 Schrijf Feature-tests voor de resource: aanmaken met vier teksten, validatiefout bij twee teksten, alleen gegenereerde oefeningen selecteerbaar, lijst toont de status en de View-pagina toont het rooster
- [x] 8.2 Schrijf Feature-tests voor de twee acties: opnieuw leggen wijzigt de seed zonder AI-aanroep en laat de kandidaten ongemoeid, opnieuw genereren zet de status terug en dispatcht de job
- [x] 8.3 Schrijf een Feature-test voor de policy die alle zeven abilities dekt
- [x] 8.4 Controleer dat de arch-test die elke Filament-resource aan een policy koppelt ook de nieuwe resource dekt en slaagt
- [x] 8.5 Draai `composer test` en los alle Rector- en Pint-bevindingen op
