# crossword-word-generation

## Purpose

Asynchrone AI-generatie van de woorden en aanwijzingen voor een kruiswoordpuzzel: een queued job roept per gekoppelde begrijpend-leesoefening een agent met structured output aan, valideert de kandidaten programmatisch, ontdubbelt ze over de teksten heen en slaat kandidaten gescheiden van geplaatste ingangen op.

## Requirements

### Requirement: Generatie via queued job
De generatie MUST asynchroon verlopen via een `GenerateCrosswordPuzzle`-job op de bestaande queue. De job MUST per geselecteerde oefening de AI-agent aanroepen, daarna valideren, ontdubbelen, het rooster laten leggen en het resultaat opslaan. De job-timeout MUST ruim genoeg zijn voor zes opeenvolgende agent-aanroepen (minimaal 600 seconden). Als de job definitief faalt, ook bij een onverwachte exception, MUST `failed_at` gezet worden zodat een puzzel nooit eeuwig "bezig" blijft.

#### Scenario: Job verwerkt een puzzel
- **WHEN** de job draait voor een puzzel zonder inhoud
- **THEN** roept hij de agent aan voor elke gekoppelde oefening en slaat bij succes de kandidaatwoorden en het gelegde rooster op

#### Scenario: Onverwachte fout
- **WHEN** de job faalt door een onafgevangen exception
- **THEN** wordt `failed_at` op de puzzel gezet

#### Scenario: Te weinig bruikbare woorden
- **WHEN** er na validatie en ontdubbeling te weinig kandidaten overblijven om een rooster te leggen
- **THEN** wordt `failed_at` gezet in plaats van een lege of half gevulde puzzel op te slaan

### Requirement: Eén agent-aanroep per tekst
Er MUST per geselecteerde begrijpend-leesoefening een aparte aanroep van de agent gedaan worden, met alleen die ene tekst in de prompt. De aanroepen MUST sequentieel binnen dezelfde job plaatsvinden. Elke aanroep MUST 8 kandidaatwoorden met aanwijzing opleveren, wat bewuste overgeneratie is ten opzichte van de 10 tot 18 plekken in de puzzel.

#### Scenario: Vijf teksten, vijf aanroepen
- **WHEN** een puzzel met vijf gekoppelde oefeningen gegenereerd wordt
- **THEN** wordt de agent vijf keer aangeroepen, elke keer met de titel en alinea's van één oefening

#### Scenario: Spreiding in woordlengte
- **WHEN** de prompt voor één tekst wordt opgebouwd
- **THEN** instrueert die om 8 woorden te leveren waarvan minstens 2 van 9 letters of langer, binnen de lengtegrenzen van de gekozen groep

### Requirement: De groep bepaalt aantal en lengte van de woorden
Er MUST een `PuzzleGroup`-enum zijn met de cases groep 4 tot en met groep 8, die per case het aantal woorden in de puzzel, de minimale en maximale woordlengte en de toegestane woordsoorten levert. Deze waarden MUST NOT als formuliervelden aan de gebruiker gevraagd worden. De enum MUST de enige bron zijn, zowel voor de prompt als voor de validatie als voor het doelaantal van de plaatser.

De waarden zijn: groep 4 met 10 woorden van 4 tot 7 letters en alleen concrete zelfstandige naamwoorden; groep 5 met 12 woorden van 4 tot 9 letters; groep 6 met 14 woorden van 4 tot 11 letters, ook werkwoorden; groep 7 met 16 woorden van 4 tot 13 letters, ook abstracte begrippen; groep 8 met 18 woorden van 4 tot 15 letters.

#### Scenario: Prompt voor groep 4
- **WHEN** een puzzel voor groep 4 gegenereerd wordt
- **THEN** vraagt de prompt om woorden van 4 tot 7 letters en uitsluitend concrete zelfstandige naamwoorden

#### Scenario: Doelaantal volgt de groep
- **WHEN** een puzzel voor groep 8 gegenereerd wordt
- **THEN** is het doelaantal woorden in het rooster 18

### Requirement: Het level bepaalt het type aanwijzing
Er MUST een enum zijn die per levelband van 1-50 het type aanwijzing beschrijft, in dezelfde reproductie-naar-inferentieladder als de bestaande `LevelBand` voor oefeningen. De omschrijving MUST uit één bron komen, zodat de helptekst in het formulier en de instructie aan de AI identiek zijn.

De banden zijn: 1-10 een letterlijke omschrijving waarin het sleutelwoord bijna genoemd wordt; 11-20 een definitie in eigen woorden met één denkstap; 21-30 een aanwijzing die naar de tekst verwijst zodat het kind moet terugzoeken; 31-40 een aanwijzing die context gebruikt zoals oorzaak, gevolg of functie; 41-50 een aanwijzing zonder sleutelwoorden die om de hoofdgedachte vraagt.

#### Scenario: Aanwijzing voor level 5
- **WHEN** een puzzel met level 5 gegenereerd wordt
- **THEN** instrueert de prompt om letterlijke omschrijvingen te schrijven waarin het sleutelwoord bijna genoemd wordt

#### Scenario: Aanwijzing voor level 45
- **WHEN** een puzzel met level 45 gegenereerd wordt
- **THEN** instrueert de prompt om aanwijzingen zonder sleutelwoorden te schrijven die om de hoofdgedachte vragen

#### Scenario: Eén bron voor formulier en prompt
- **WHEN** de helptekst in het formulier en de instructie in de prompt voor hetzelfde level worden opgebouwd
- **THEN** komen beide uit dezelfde enum en beschrijven zij hetzelfde type aanwijzing

### Requirement: Agent met structured output
Er MUST een agent-class zijn volgens hetzelfde patroon als de bestaande `ExerciseWriter` (`laravel/ai`, `Agent` plus `HasStructuredOutput`). De agent MUST een JSON-schema afdwingen met een array van 8 objecten met `word` (string) en `clue` (string). Markdown-uitvoer parsen is NIET toegestaan. De instructies MUST bevatten: dat het woord letterlijk in de aangeleverde tekst voorkomt, dat het binnen de lengtegrenzen van de groep valt, dat het geen spaties of leestekens bevat, dat de aanwijzing het woord zelf niet noemt, dat de taal van de aanwijzing past bij de groep en dat het type aanwijzing de levelband volgt.

#### Scenario: Schema wordt afgedwongen
- **WHEN** de agent antwoordt
- **THEN** is het resultaat een gevalideerd object met 8 woord-en-aanwijzing-paren, niet een tekst die geparseerd moet worden

### Requirement: Programmatische validatie na de AI-aanroep
Het resultaat van elke agent-aanroep MUST programmatisch gevalideerd worden; de correctheid MUST NOT aan de AI overgelaten worden. Een kandidaat MUST afgekeurd worden wanneer het woord niet letterlijk in de brontekst voorkomt, wanneer het na normalisatie niet volledig uit A-Z bestaat, wanneer de lengte buiten de grenzen van de groep valt, of wanneer het een spatie of apostrof bevat. Diakrieten MUST genormaliseerd worden naar hun basisletter en woorden MUST in hoofdletters opgeslagen worden. Afgekeurde kandidaten MUST verwijderd worden en NIET gecorrigeerd, zodat een slecht presterende prompt zichtbaar blijft.

#### Scenario: Woord staat niet in de tekst
- **WHEN** de AI een woord teruggeeft dat niet letterlijk in de aangeleverde tekst staat
- **THEN** wordt die kandidaat verwijderd

#### Scenario: Diakriet wordt genormaliseerd
- **WHEN** de AI het woord "muggenbeËt" of een ander woord met een diakriet teruggeeft
- **THEN** wordt de diakriet vervangen door de basisletter en wordt het woord in hoofdletters opgeslagen

#### Scenario: Apostrof wordt afgekeurd
- **WHEN** de AI het woord "panda's" teruggeeft
- **THEN** wordt die kandidaat verwijderd

#### Scenario: Woord buiten de lengtegrens
- **WHEN** de AI voor groep 4 een woord van 11 letters teruggeeft
- **THEN** wordt die kandidaat verwijderd

### Requirement: Ontdubbeling over teksten heen
Kandidaten uit verschillende teksten MUST ontdubbeld worden op exacte match en daarnaast op een prefixregel: wanneer het ene woord een prefix is van het andere met hooguit drie letters verschil, MUST het kortste woord vervallen. Bij een verwijderd duplicaat MUST de overgebleven kandidaat zijn bron-oefening behouden.

#### Scenario: Exact duplicaat
- **WHEN** twee teksten allebei het woord "TIJGER" opleveren
- **THEN** blijft er één kandidaat over

#### Scenario: Enkelvoud en meervoud
- **WHEN** de ene tekst "MUG" oplevert en de andere "MUGGEN"
- **THEN** vervalt "MUG" en blijft "MUGGEN" over

#### Scenario: Woorden met dezelfde start maar te veel verschil
- **WHEN** de kandidaten "PLANT" en "PLANETEN" zijn
- **THEN** blijven beide bestaan, want het lengteverschil is groter dan drie

### Requirement: Kandidaten en geplaatste ingangen apart opgeslagen
De puzzel MUST alle gevalideerde en ontdubbelde kandidaten met hun aanwijzing en bron-oefening opslaan, gescheiden van de deelverzameling die daadwerkelijk in het rooster geplaatst is. Dit MUST het mogelijk maken het rooster opnieuw te leggen zonder een nieuwe AI-aanroep.

#### Scenario: Opnieuw leggen zonder AI
- **WHEN** het rooster opnieuw gelegd wordt met een nieuwe seed
- **THEN** gebruikt de plaatser de opgeslagen kandidaten en wordt er geen agent aangeroepen

#### Scenario: Niet-geplaatste kandidaten blijven bewaard
- **WHEN** de plaatser 14 van de 40 kandidaten plaatst
- **THEN** blijven alle 40 kandidaten opgeslagen en bevatten de geplaatste ingangen er 14
