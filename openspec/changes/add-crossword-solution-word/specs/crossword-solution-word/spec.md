## ADDED Requirements

### Requirement: Puzzelwoord uit de niet-geplaatste kandidaten

Het puzzelwoord MUST gekozen worden uit de opgeslagen kandidaten die niet in het rooster geplaatst zijn. Er MUST NOT een AI-aanroep gedaan worden om een puzzelwoord te bepalen. Het gekozen woord MUST binnen de lengtegrenzen van het puzzelwoord voor de gekozen groep vallen, en die grenzen MUST uit de `PuzzleGroup`-enum komen zodat er één bron blijft. De kandidaten MUST in een vaste volgorde afgelopen worden, langste woord eerst, zodat de keuze deterministisch is en geen seed nodig heeft.

#### Scenario: Woord komt uit de overgebleven kandidaten

- **WHEN** een puzzel gegenereerd is met 30 kandidaten waarvan er 14 geplaatst zijn
- **THEN** is het puzzelwoord een van de 16 niet-geplaatste kandidaten en is er geen AI-aanroep gedaan

#### Scenario: Woord binnen de lengtegrenzen van de groep

- **WHEN** voor groep 4 een puzzelwoord gekozen wordt
- **THEN** is dat woord minimaal 4 en maximaal 5 letters lang

#### Scenario: Deterministische keuze

- **WHEN** de keuze twee keer gemaakt wordt voor dezelfde kandidaten en hetzelfde rooster
- **THEN** is het gekozen woord beide keren hetzelfde

### Requirement: Eén rooster-vakje per letter

Voor elke letter van het puzzelwoord MUST precies één vakje in het gelegde rooster aangewezen worden dat die letter bevat. Elk vakje MUST hooguit één keer gebruikt worden. De volgorde van de vakjes MUST de volgorde van de letters in het woord volgen, zodat het volgnummer van een vakje zijn positie in het woord is. De vakjes MUST NOT aan elkaar hoeven grenzen en MUST NOT in leesrichting hoeven liggen.

De toewijzing MUST met terugkrabbelen werken en MUST NOT puur hebzuchtig zijn, zodat een woord niet ten onrechte afvalt doordat een vroege letter het laatste bruikbare vakje van een latere letter inneemt.

#### Scenario: Elke letter krijgt een eigen vakje

- **WHEN** het puzzelwoord 7 letters telt
- **THEN** zijn er 7 verschillende vakjes aangewezen, genummerd 1 tot en met 7, en bevat vakje n de n-de letter van het woord

#### Scenario: Terugkrabbelen redt een woord dat hebzuchtig zou falen

- **WHEN** een vroege letter en een latere letter allebei alleen op hetzelfde vakje passen, maar er voor de vroege letter elders nog een vakje is
- **THEN** wordt dat andere vakje voor de vroege letter gekozen en slaagt de toewijzing alsnog

#### Scenario: Vakjes hoeven niet naast elkaar te liggen

- **WHEN** de vakjes van een puzzelwoord aangewezen zijn
- **THEN** worden zij uitsluitend door hun volgnummer geordend en is hun onderlinge ligging in het rooster niet van belang

### Requirement: Spreiding over de ingangen

De gemarkeerde vakjes MUST gespreid worden over verschillende ingangen in plaats van geclusterd te worden binnen één woord. De spreiding MUST afgedwongen worden als een bovengrens op het aantal gemarkeerde vakjes per ingang, en die bovengrens MUST stapsgewijs versoepelen: eerst hooguit één vakje per ingang, dan hooguit twee, en pas daarna zonder bovengrens. Pas wanneer alle drie de niveaus falen MUST de volgende kandidaat geprobeerd worden.

Een vakje waar twee woorden elkaar kruisen MUST voor beide ingangen meetellen in die bovengrens, en MUST achteraan staan in de voorkeursvolgorde van vakjes.

#### Scenario: Elke letter uit een andere ingang

- **WHEN** een puzzelwoord van 6 letters gekozen wordt in een rooster met 14 ingangen die dat toelaten
- **THEN** komen de 6 gemarkeerde vakjes uit 6 verschillende ingangen

#### Scenario: Versoepelen in plaats van opgeven

- **WHEN** een puzzelwoord niet te matchen is met hooguit één vakje per ingang
- **THEN** wordt hetzelfde woord opnieuw geprobeerd met hooguit twee vakjes per ingang, en daarna zonder bovengrens, voordat een ander woord aan de beurt komt

#### Scenario: Kruisend vakje telt voor beide ingangen

- **WHEN** een gemarkeerd vakje op de kruising van twee woorden ligt
- **THEN** telt dat vakje mee in de bovengrens van allebei die ingangen

### Requirement: Botsing met een genummerd startvakje

Een vakje dat het genummerde startvakje van een ingang is MUST achteraan staan in de voorkeursvolgorde, zodat het alleen gekozen wordt wanneer er geen ander vakje beschikbaar is. Wanneer een startvakje toch gemarkeerd wordt, MUST het ingangnummer op zijn plaats linksboven blijven staan en MUST het volgnummer van het puzzelwoord in een andere hoek van hetzelfde vakje getoond worden. Het ingangnummer MUST NOT weggelaten of verplaatst worden, omdat de aanwijzingen eraan hangen.

#### Scenario: Startvakje wordt vermeden

- **WHEN** een letter zowel op een startvakje als op een gewoon vakje van dezelfde ingang gevonden kan worden
- **THEN** wordt het gewone vakje gekozen

#### Scenario: Twee nummers in twee hoeken

- **WHEN** er geen ander vakje beschikbaar is en een startvakje gemarkeerd wordt
- **THEN** staat het ingangnummer linksboven en het volgnummer van het puzzelwoord in een andere hoek van datzelfde vakje, zonder dat zij elkaar overlappen

### Requirement: Opslag van het puzzelwoord

Het puzzelwoord MUST opgeslagen worden met het woord, de aanwijzing van dat woord en de geordende vakjes in dezelfde bijgesneden coördinaten als de geplaatste ingangen. De opslag MUST leeg mogen zijn: een puzzel zonder puzzelwoord MUST een geldige, volledig gegenereerde puzzel blijven.

#### Scenario: Puzzelwoord wordt bewaard

- **WHEN** de generatie een puzzelwoord oplevert
- **THEN** zijn het woord, de aanwijzing en de geordende vakjes op de puzzel opgeslagen

#### Scenario: Geen puzzelwoord is geen fout

- **WHEN** geen enkele kandidaat op het rooster te matchen is
- **THEN** wordt de puzzel gewoon als gegenereerd opgeslagen, zonder puzzelwoord, en MUST NOT `failed_at` gezet worden

### Requirement: Opnieuw leggen wijst de vakjes opnieuw toe

Wanneer het rooster opnieuw gelegd wordt MUST de toewijzing van de vakjes opnieuw berekend worden over het nieuwe rooster, zonder AI-aanroep. Omdat de kandidaten en hun volgorde niet veranderen MUST hetzelfde woord opnieuw als eerste geprobeerd worden; alleen wanneer dat woord in het nieuwe rooster niet meer te matchen is MUST een volgende kandidaat gekozen worden.

#### Scenario: Zelfde woord, nieuwe vakjes

- **WHEN** de gebruiker "opnieuw leggen" kiest op een puzzel met een puzzelwoord
- **THEN** is het puzzelwoord hetzelfde gebleven, zijn de gemarkeerde vakjes opnieuw toegewezen aan het nieuwe rooster, en is er geen AI-aanroep gedaan

#### Scenario: Ander woord wanneer het oude niet meer past

- **WHEN** het eerder gekozen woord in het nieuwe rooster niet te matchen is
- **THEN** wordt de volgende kandidaat in de vaste volgorde gekozen

### Requirement: Weergave op werkblad, antwoordblad en detailpagina

De gemarkeerde vakjes MUST in het rooster herkenbaar zijn met een grijsvulling die ook op een zwart-witprinter zichtbaar blijft; er MUST NOT uitsluitend op kleur vertrouwd worden. Elk gemarkeerd vakje MUST zijn volgnummer tonen.

Het werkblad MUST onder het rooster een rij genummerde invulhokjes hebben, één per letter van het puzzelwoord. Het werkblad MUST NOT het puzzelwoord zelf tonen en MUST NOT de aanwijzing van het puzzelwoord tonen, zodat het antwoord uit de letters moet komen. Het antwoordblad en de detailpagina MUST het puzzelwoord wel tonen.

Wanneer er geen puzzelwoord is MUST geen van deze drie weergaven een markering, een rij invulhokjes of een lege plek daarvoor tonen.

#### Scenario: Werkblad vraagt om het puzzelwoord

- **WHEN** het werkblad van een puzzel met een puzzelwoord geopend wordt
- **THEN** zijn de betreffende vakjes gemarkeerd met hun volgnummer en staat er onder het rooster een rij genummerde lege hokjes, en staat het puzzelwoord er nergens

#### Scenario: Antwoordblad toont het puzzelwoord

- **WHEN** het antwoordblad van een puzzel met een puzzelwoord geopend wordt
- **THEN** staat het puzzelwoord er voluit en zijn de betreffende vakjes in het ingevulde rooster gemarkeerd

#### Scenario: Puzzel zonder puzzelwoord

- **WHEN** een puzzel zonder puzzelwoord geprint of bekeken wordt
- **THEN** bevat het rooster geen markeringen en staat er geen rij invulhokjes onder
