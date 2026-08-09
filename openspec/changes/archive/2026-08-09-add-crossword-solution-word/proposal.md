## Why

Als de kruiswoordpuzzel vol is, houdt het op. Er is geen moment waarop het kind ziet dat het klaar is en geen beloning voor het uitpuzzelen van dat ene laatste woord. Een puzzelwoord, de Nederlandse conventie waarbij een aantal gemarkeerde vakjes samen een extra woord vormen, sluit die lus: het kind moet zijn eigen antwoorden teruglezen om het te vinden, en er is één duidelijk eindpunt.

De bouwstenen liggen er al. De puzzel bewaart alle gevalideerde kandidaten los van de geplaatste ingangen, en van de kandidaten belanden er maar tien tot achttien in het rooster. Het puzzelwoord kan dus uit die overgebleven voorraad komen: woorden die al uit de gelezen teksten komen, al gevalideerd zijn op A-Z en op de lengtegrenzen van de groep, en al een aanwijzing hebben. Geen extra AI-call, geen extra kosten.

## What Changes

- Bij het genereren wordt na het leggen van het rooster een puzzelwoord gekozen uit de kandidaten die niet in het rooster staan, met een bovengrens van 8 letters.
- Voor elke letter van dat woord wordt een apart vakje in het rooster aangewezen. De vakjes krijgen een markering en een volgnummer 1 tot en met n; in die volgorde gelezen vormen ze het woord.
- De gemarkeerde vakjes worden **gespreid over verschillende ingangen**, zodat de markering niet als cluster binnen één woord terechtkomt. Spreiding wint van woordlengte: eerst worden alle kandidaten geprobeerd met hooguit één vakje per ingang, pas als geen enkele kandidaat zo past valt de bovengrens weg.
- Een gemarkeerd vakje dat óók het genummerde startvakje van een ingang is, wordt zoveel mogelijk vermeden en anders leesbaar opgemaakt, zodat er nooit twee nummers over elkaar vallen.
- Het werkblad krijgt onderaan een rij genummerde invulhokjes voor het puzzelwoord. Het antwoordblad toont het woord, en op de detailpagina staat het bij de Kenmerken.
- "Opnieuw leggen" berekent woord en vakjes opnieuw over het nieuwe rooster. Het puzzelwoord mag daarbij wisselen: een woord dat in het nieuwe rooster wél geplaatst is, valt uit de voorraad en maakt plaats voor de volgende kandidaat.
- Lukt het niet om een woord te matchen, dan krijgt de puzzel geen puzzelwoord. Dat is een lege uitkomst, geen fout: de puzzel blijft gewoon geldig.
- De rasterlijnen van het rooster gaan van 1px naar 2px, zodat de puzzel robuuster oogt en de grijze markering ernaast rustig blijft.
- De acties worden opgeruimd nu de View-pagina de plek is waar je met één puzzel werkt: printen, opnieuw leggen en opnieuw genereren verdwijnen van de tabelrij, de twee printknoppen komen los naast de groep te staan, en "opnieuw genereren" is verborgen zodra een puzzel geslaagd is, maar blijft bereikbaar voor een puzzel die op "bezig" is blijven hangen.
- "Opnieuw leggen" krijgt een rate limit en de picker een bovengrens aan zoekstappen: beide draaien synchroon in het request, dus ze mogen niet onbegrensd doorzoeken.

## Capabilities

### New Capabilities

- `crossword-solution-word`: Het puzzelwoord bij een kruiswoordpuzzel. De keuze van het doelwoord uit de niet-geplaatste kandidaten, het toewijzen van één rooster-vakje per letter met spreiding over de ingangen, de omgang met een startvakje dat ook gemarkeerd is, en het opslaan van woord en vakjes.

### Modified Capabilities

- `crossword-puzzle-management`: het puzzelwoord landt in vier bestaande eisen. `Detailweergave van een gegenereerde puzzel` toont het woord bij de Kenmerken, `Printbaar werkblad` krijgt de rij invulhokjes onder het rooster, `Printbaar antwoordblad` toont het woord voluit, en `Opnieuw leggen zonder AI` wijst woord en vakjes opnieuw toe.

`crossword-grid-layout` en `crossword-word-generation` blijven ongewijzigd. Het plaatsingsalgoritme, de scorefunctie en de AI-fase veranderen niet: het puzzelwoord is nabewerking op een al gelegd rooster.

## Impact

**Nieuw**

- `App\Support\Crossword\SolutionWordPicker`: kiest het doelwoord en wijst de vakjes toe. Database-loos en AI-loos, net als de rest van `app/Support/Crossword/`, dus testbaar in een Unit-test.
- Eén nullable json-kolom `solution_word` op `crossword_puzzles` met het woord, de aanwijzing en de geordende vakjes.

**Gewijzigd**

- `CrosswordPuzzle::relayout()` roept de picker aan en schrijft `solution_word` in dezelfde `forceFill()`. Dat is de enige aanroepplek: de generatie-job, "opnieuw leggen" en "opnieuw genereren" lopen er alle drie doorheen.
- `CrosswordPuzzle::cells()` levert per vakje ook het volgnummer van het puzzelwoord.
- De gedeelde Blade-partial voor het rooster markeert die vakjes, zet het volgnummer erin en tekent 2px rasterlijnen.
- Werkblad, antwoordblad en de Kenmerken-sectie van de detailpagina tonen het puzzelwoord.
- `CrosswordPuzzleFactory::generated()` krijgt een groter handgelegd rooster: het huidige rooster van drie ingangen kan geen puzzelwoord dragen.

**Ongewijzigd**

- Geen nieuwe dependencies en geen extra AI-call. Het doelwoord komt uit de al opgeslagen kandidaten.
- `PuzzleGroup` krijgt geen lengtegrenzen voor het puzzelwoord. Uit de meting blijkt dat de restvoorraad aan het aantal teksten hangt en niet aan de groep, dus één bovengrens van 8 letters volstaat en de ondergrens van 4 komt al uit `CandidateValidator`.
- `Grid`, `LayoutBuilder` en `LayoutScorer` blijven zoals ze zijn. Het rooster wordt niet anders gelegd om een puzzelwoord mogelijk te maken.
- `solution_word` staat niet in `#[Fillable]`: het is generatie-uitvoer en gaat net als `entries` via `forceFill()`.

**Risico's**

- Een woord met een letter die niet in het rooster voorkomt is niet te matchen. Gemeten over de bestaande puzzels valt 2 tot 4 van de restvoorraad daarop af, terwijl er 3 tot 10 woorden overblijven die zelfs op het strengste spreidingsniveau passen. De bestaande herrolknop is de menselijke ontsnapping.
- De dunste gemeten voorraad (3 teksten, 23 kandidaten) hield 5 woorden van hoogstens 8 letters over, waarvan 3 matchbaar. Bij nog magerder AI-opbrengst krijgt de puzzel geen puzzelwoord, en dat is een geldige uitkomst.
