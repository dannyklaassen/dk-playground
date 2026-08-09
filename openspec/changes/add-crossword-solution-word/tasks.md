## 1. Datamodel

- [ ] 1.1 Voeg aan `PuzzleGroup` een minimale en maximale lengte voor het puzzelwoord toe, oplopend van 4-5 letters voor groep 4 tot 6-9 letters voor groep 8
- [ ] 1.2 Maak een migratie die de nullable json-kolom `solution` aan `crossword_puzzles` toevoegt, direct na `entries` en dus vóór `generated_at`, zonder `down()`
- [ ] 1.3 Voeg `solution` toe aan `#[Fillable]` en aan de array-casts van `CrosswordPuzzle`
- [ ] 1.4 Breid `CrosswordPuzzleFactory::generated()` uit met een puzzelwoord op het bestaande handgelegde rooster, plus een named state zonder puzzelwoord

## 2. De picker (zonder database, zonder AI)

- [ ] 2.1 Maak `App\Support\Crossword\SolutionCell` met rij, kolom en het volgnummer
- [ ] 2.2 Maak `App\Support\Crossword\SolutionWord` met woord, aanwijzing en de geordende vakjes, plus `fromArray()` en `toArray()` voor de json-kolom
- [ ] 2.3 Maak `App\Support\Crossword\SolutionWordPicker` die de geplaatste ingangen en de kandidaten in neemt en een `SolutionWord` of `null` teruggeeft
- [ ] 2.4 Bouw uit de ingangen een index van vakjes: per vakje de letter, bij welke ingangen het hoort, of het een startvakje is en of het een kruising is
- [ ] 2.5 Implementeer de voorkeursvolgorde van vakjes: gewone vakjes eerst, dan kruisende vakjes, dan startvakjes, met rij en kolom als vaste tiebreak
- [ ] 2.6 Implementeer de matcher met terugkrabbelen, die de letters in volgorde van zeldzaamste eerst behandelt en een `maxPerEntry` respecteert waarbij een kruisend vakje voor beide ingangen meetelt
- [ ] 2.7 Implementeer de kandidaatvolgorde: filter op de lengtegrenzen van de groep, dan langste eerst, dan alfabetisch
- [ ] 2.8 Implementeer de escalatie per kandidaat: eerst `maxPerEntry` 1, dan 2, dan geen bovengrens, en pas daarna door naar de volgende kandidaat
- [ ] 2.9 Schrijf Unit-tests voor: één vakje per letter, terugkrabbelen redt een woord dat hebzuchtig zou falen, spreiding over verschillende ingangen, versoepelen naar 2 per ingang, startvakjes worden vermeden, kruisend vakje telt voor beide ingangen, lengtegrenzen per groep, determinisme, en `null` wanneer geen enkele kandidaat past

## 3. Generatie en opnieuw leggen

- [ ] 3.1 Roep de picker aan in `GenerateCrosswordPuzzle` nadat het rooster gelegd is, en sla het resultaat op in `solution`
- [ ] 3.2 Zorg dat een lege uitkomst geen `failed_at` zet en de puzzel gewoon als gegenereerd wordt opgeslagen
- [ ] 3.3 Laat `RelayoutCrosswordPuzzleAction` de picker opnieuw draaien over het nieuwe rooster en `solution` herschrijven, zonder AI-aanroep
- [ ] 3.4 Schrijf Feature-tests: generatie levert een puzzelwoord, geen match leidt tot een gegenereerde puzzel zonder puzzelwoord, en opnieuw leggen behoudt het woord maar wijst nieuwe vakjes toe

## 4. Weergave in het rooster

- [ ] 4.1 Laat `CrosswordPuzzle::cells()` per vakje ook het volgnummer van het puzzelwoord leveren
- [ ] 4.2 Voeg aan de gedeelde grid-partial een grijsvulling toe voor gemarkeerde vakjes, die ook in zwart-wit zichtbaar blijft
- [ ] 4.3 Zet het volgnummer van het puzzelwoord in een andere hoek dan het ingangnummer, zodat beide leesbaar blijven op een gemarkeerd startvakje
- [ ] 4.4 Schrijf een Feature-test die een gemarkeerd startvakje rendert en aantoont dat beide nummers aanwezig zijn

## 5. Werkblad, antwoordblad en detailpagina

- [ ] 5.1 Maak een Blade-partial met de rij genummerde invulhokjes voor het puzzelwoord
- [ ] 5.2 Zet die rij onder het rooster op het werkblad, zonder het woord en zonder de aanwijzing te tonen
- [ ] 5.3 Toon het puzzelwoord voluit op het antwoordblad
- [ ] 5.4 Toon het puzzelwoord met zijn aanwijzing in de Antwoordblad-sectie van de detailpagina
- [ ] 5.5 Laat alle drie de weergaven de markering en de invulhokjes volledig weg wanneer er geen puzzelwoord is
- [ ] 5.6 Schrijf Feature-tests: werkblad toont de invulhokjes maar nergens het woord, antwoordblad toont het woord, en een puzzel zonder puzzelwoord toont geen markering en geen hokjes

## 6. Vertalingen en kwaliteitsgate

- [ ] 6.1 Voeg de sleutels voor het puzzelwoord toe aan `lang/nl/admin.php` onder `crossword_puzzle`, in `fields`, `sections` en `print`
- [ ] 6.2 Controleer dat er geen Nederlandse tekst hardcoded in PHP of Blade staat
- [ ] 6.3 Draai `composer test` en los alle Rector- en Pint-bevindingen op
