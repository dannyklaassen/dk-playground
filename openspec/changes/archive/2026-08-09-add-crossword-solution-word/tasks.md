## 1. Datamodel

- [x] 1.1 Maak een migratie die de nullable json-kolom `solution_word` aan `crossword_puzzles` toevoegt, direct na `entries` en dus vóór `generated_at`, zonder `down()`
- [x] 1.2 Voeg `solution_word` toe aan de array-casts van `CrosswordPuzzle`, en **niet** aan `#[Fillable]`: het is generatie-uitvoer en gaat via `forceFill()`
- [x] 1.3 Vervang het handgelegde rooster in `CrosswordPuzzleFactory::generated()` door een groter rooster met minstens 8 ingangen en een vaste groep, en zorg dat er niet-geplaatste kandidaten in zitten die op dat rooster matchbaar zijn
- [x] 1.4 Voeg een named state toe voor een gegenereerde puzzel zonder puzzelwoord

## 2. De picker (zonder database, zonder AI)

- [x] 2.1 Maak `App\Support\Crossword\SolutionCell` met rij en kolom
- [x] 2.2 Maak `App\Support\Crossword\SolutionWord` met woord, aanwijzing en de geordende vakjes, plus `fromArray()` en `toArray()` voor de json-kolom
- [x] 2.3 Maak `App\Support\Crossword\SolutionWordPicker` die de geplaatste ingangen en de kandidaten in neemt en een `SolutionWord` of `null` teruggeeft
- [x] 2.4 Bouw uit de ingangen een index van vakjes: per vakje de letter, bij welke ingangen het hoort, of het een startvakje is en of het een kruising is
- [x] 2.5 Implementeer de voorkeursvolgorde als één strafscore per vakje (`is_kruising + is_startvakje`), met rij en kolom als vaste tiebreak
- [x] 2.6 Implementeer de matcher met terugkrabbelen, die de letters in volgorde van zeldzaamste eerst behandelt en een `maxPerEntry` respecteert waarbij een kruisend vakje voor beide ingangen meetelt
- [x] 2.7 Implementeer de kandidaatvolgorde: filter op hoogstens 8 letters, dan langste eerst, dan alfabetisch
- [x] 2.8 Implementeer de twee doorgangen: eerst de hele kandidatenlijst met `maxPerEntry` 1, en alleen als niemand past de hele lijst opnieuw zonder bovengrens
- [x] 2.9 Schrijf Unit-tests voor: één vakje per letter, terugkrabbelen redt een woord dat hebzuchtig zou falen, spreiding over verschillende ingangen, een korter gespreid woord wint van een langer geclusterd woord, de tweede doorgang zonder bovengrens, startvakjes en kruisingen achteraan met het dubbelgeval als laatste, de bovengrens van 8 letters, determinisme, en `null` wanneer geen enkele kandidaat past

## 3. Aansluiten op de generatie

- [x] 3.1 Roep de picker aan in `CrosswordPuzzle::relayout()` nadat het rooster gelegd is, en schrijf `solution_word` in dezelfde `forceFill()` als `entries`; dit is de enige aanroepplek, dus generatie, opnieuw leggen en opnieuw genereren zijn er alle drie mee gedekt
- [x] 3.2 Controleer dat de bestaande vroege `return false` blijft gelden, zodat een afgewezen herroll het oude rooster én het oude puzzelwoord ongemoeid laat
- [x] 3.3 Controleer dat een lege uitkomst geen `failed_at` zet en de puzzel gewoon als gegenereerd wordt opgeslagen
- [x] 3.4 Schrijf Feature-tests: generatie levert een puzzelwoord, geen match leidt tot een gegenereerde puzzel zonder puzzelwoord, opnieuw leggen wijst nieuwe vakjes toe zonder AI-aanroep, en een gegenereerde puzzel zonder puzzelwoord krijgt er via opnieuw leggen alsnog een

## 4. Weergave in het rooster

- [x] 4.1 Laat `CrosswordPuzzle::cells()` per vakje ook het volgnummer van het puzzelwoord leveren
- [x] 4.2 Zet de rasterlijnen in de gedeelde grid-partial van 1px naar 2px
- [x] 4.3 Voeg een grijsvulling toe voor gemarkeerde vakjes, die ook in zwart-wit zichtbaar blijft en de ingevulde letter leesbaar laat
- [x] 4.4 Zet het volgnummer van het puzzelwoord rechtsonder, tegenover het ingangnummer linksboven, zodat beide leesbaar blijven op een gemarkeerd startvakje
- [x] 4.5 Schrijf een Feature-test die een gemarkeerd startvakje rendert en aantoont dat beide nummers aanwezig zijn

## 5. Werkblad, antwoordblad en detailpagina

- [x] 5.1 Maak een Blade-partial met de rij genummerde invulhokjes voor het puzzelwoord, met een vaste hokjesgrootte van 12mm die niet met `cellSizeMm()` meeschaalt
- [x] 5.2 Zet die rij onder het rooster op pagina 1 van het werkblad, zonder het woord en zonder de aanwijzing te tonen
- [x] 5.3 Toon het puzzelwoord voluit op het antwoordblad
- [x] 5.4 Toon het puzzelwoord met zijn aanwijzing in de Kenmerken-sectie van de detailpagina
- [x] 5.5 Laat alle drie de weergaven de markering en de invulhokjes volledig weg wanneer er geen puzzelwoord is
- [x] 5.6 Schrijf Feature-tests: werkblad toont de invulhokjes maar nergens het woord of de aanwijzing, antwoordblad toont het woord, detailpagina toont het bij de Kenmerken, en een puzzel zonder puzzelwoord toont geen markering en geen hokjes

## 6. Vertalingen en kwaliteitsgate

- [x] 6.1 Voeg de sleutels voor het puzzelwoord toe aan `lang/nl/admin.php` onder `crossword_puzzle`, in `fields` en `print`
- [x] 6.2 Controleer dat er geen Nederlandse tekst hardcoded in PHP of Blade staat
- [x] 6.3 Draai `composer test` en los alle Rector- en Pint-bevindingen op

## 7. Acties opruimen en begrenzen

- [x] 7.1 Haal printen, opnieuw leggen en opnieuw genereren van de tabelrij en laat daar alleen hernoemen en verwijderen staan
- [x] 7.2 Zet de twee printknoppen los naast de `ActionGroup` in de header van de View-pagina
- [x] 7.3 Verberg "opnieuw genereren" voor een gegenereerde puzzel, maar houd hem zichtbaar voor een mislukte en voor een nog bezige puzzel
- [x] 7.4 Geef "opnieuw leggen" een rate limit en de `SolutionWordPicker` een bovengrens aan zoekstappen
- [x] 7.5 Schrijf Feature-tests voor de zichtbaarheid per status en voor de acties die van de rij verdwenen zijn
