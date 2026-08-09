## Why

Als de kruiswoordpuzzel vol is, houdt het op. Er is geen moment waarop het kind ziet dat het klaar is en geen beloning voor het uitpuzzelen van dat ene laatste woord. Een puzzelwoord, de Nederlandse conventie waarbij een aantal gemarkeerde vakjes samen een extra woord vormen, sluit die lus: het kind moet zijn eigen antwoorden teruglezen om het te vinden, en er is één duidelijk eindpunt.

De bouwstenen liggen er al. De puzzel bewaart alle gevalideerde kandidaten los van de geplaatste ingangen, en van de veertig kandidaten belanden er maar tien tot achttien in het rooster. Het puzzelwoord kan dus uit die overgebleven voorraad komen: woorden die al uit de gelezen teksten komen, al gevalideerd zijn op A-Z en op de lengtegrenzen van de groep, en al een aanwijzing hebben. Geen extra AI-call, geen extra kosten.

## What Changes

- Bij het genereren wordt na het leggen van het rooster een puzzelwoord gekozen uit de kandidaten die niet in het rooster staan.
- Voor elke letter van dat woord wordt een apart vakje in het rooster aangewezen. De vakjes krijgen een markering en een volgnummer 1 tot en met n; in die volgorde gelezen vormen ze het woord.
- De gemarkeerde vakjes worden **gespreid over verschillende ingangen**, zodat de markering niet als cluster binnen één woord terechtkomt.
- Een gemarkeerd vakje dat óók het genummerde startvakje van een ingang is, wordt zoveel mogelijk vermeden en anders leesbaar opgemaakt, zodat er nooit twee nummers over elkaar vallen.
- Het werkblad krijgt onderaan een rij genummerde invulhokjes voor het puzzelwoord. Het antwoordblad en de detailpagina tonen het woord.
- "Opnieuw leggen" berekent de vakjestoewijzing opnieuw. Het gekozen woord blijft daarbij hetzelfde, want het hangt aan de kandidaten en niet aan het rooster.
- Lukt het niet om een woord te matchen, dan krijgt de puzzel geen puzzelwoord. Dat is een lege uitkomst, geen fout: de puzzel blijft gewoon geldig.

## Capabilities

### New Capabilities

- `crossword-solution-word`: Het puzzelwoord bij een kruiswoordpuzzel. De keuze van het doelwoord uit de niet-geplaatste kandidaten, het toewijzen van één rooster-vakje per letter met spreiding over de ingangen, de omgang met een startvakje dat ook gemarkeerd is, het opslaan van woord en vakjes, het opnieuw toewijzen bij opnieuw leggen, en de weergave op het werkblad, het antwoordblad en de detailpagina.

### Modified Capabilities

Geen. De capabilities uit `add-crossword-puzzles` (`crossword-grid-layout`, `crossword-word-generation`, `crossword-puzzle-management`) staan nog niet in `openspec/specs/`, want die change is nog niet gearchiveerd. Alle nieuwe eisen gaan daarom in een eigen capability, die volledig over het puzzelwoord gaat. Het plaatsingsalgoritme, de scorefunctie en de AI-fase veranderen niet: het puzzelwoord is nabewerking op een al gelegd rooster.

## Impact

**Nieuw**

- `App\Support\Crossword\SolutionWordPicker`: kiest het doelwoord en wijst de vakjes toe. Database-loos en AI-loos, net als de rest van `app/Support/Crossword/`, dus testbaar in een Unit-test.
- Eén nullable json-kolom `solution` op `crossword_puzzles` met het woord, de aanwijzing en de geordende vakjes.

**Gewijzigd**

- `CrosswordPuzzle::cells()` levert per vakje ook het volgnummer van het puzzelwoord.
- De gedeelde Blade-partial voor het rooster markeert die vakjes en zet het volgnummer erin.
- `GenerateCrosswordPuzzle` roept de picker aan na het leggen; `RelayoutCrosswordPuzzleAction` wijst de vakjes opnieuw toe.
- Werkblad, antwoordblad en de infolist van de detailpagina tonen het puzzelwoord.
- `PuzzleGroup` krijgt de lengtegrenzen van het puzzelwoord per groep, op dezelfde plek als de andere getallen.

**Ongewijzigd**

- Geen nieuwe dependencies en geen extra AI-call. Het doelwoord komt uit de al opgeslagen kandidaten.
- `Grid`, `LayoutBuilder` en `LayoutScorer` blijven zoals ze zijn. Het rooster wordt niet anders gelegd om een puzzelwoord mogelijk te maken.

**Volgorde**

- Deze change bouwt voort op `add-crossword-puzzles`. Archiveer die eerst, zodat de uiteindelijke specs in `openspec/specs/` in de juiste volgorde ontstaan.

**Risico's**

- Een woord met een letter die maar één keer in het rooster voorkomt, of met twee zeldzame letters, is niet te matchen. Met tientallen kandidaten om uit te kiezen zal dat zelden alle opties raken, en de bestaande herrolknop is de menselijke ontsnapping.
