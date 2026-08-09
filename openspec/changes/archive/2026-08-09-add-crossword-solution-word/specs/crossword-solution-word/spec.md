## ADDED Requirements

### Requirement: Puzzelwoord uit de niet-geplaatste kandidaten

Het puzzelwoord MUST gekozen worden uit de opgeslagen kandidaten die niet in het rooster geplaatst zijn. Er MUST NOT een AI-aanroep gedaan worden om een puzzelwoord te bepalen. Het gekozen woord MUST hoogstens 8 letters tellen, zodat de rij invulhokjes altijd op de bladbreedte past; er MUST NOT een aparte ondergrens of een lengteband per groep gelden, want de kandidaten zijn bij validatie al op de lengtegrenzen van de groep gecontroleerd. De kandidaten MUST in een vaste volgorde afgelopen worden, langste woord eerst en alfabetisch als tiebreak, zodat de keuze deterministisch is en geen seed nodig heeft.

#### Scenario: Woord komt uit de overgebleven kandidaten

- **WHEN** een puzzel gegenereerd is met 30 kandidaten waarvan er 14 geplaatst zijn
- **THEN** is het puzzelwoord een van de 16 niet-geplaatste kandidaten en is er geen AI-aanroep gedaan

#### Scenario: Woord van hoogstens acht letters

- **WHEN** de restvoorraad zowel woorden van 8 letters als woorden van 11 letters bevat
- **THEN** komen de woorden van 11 letters niet in aanmerking en wordt een woord van 8 letters als eerste geprobeerd

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

### Requirement: Spreiding gaat voor woordlengte

De gemarkeerde vakjes MUST gespreid worden over verschillende ingangen in plaats van geclusterd te worden binnen één woord. De spreiding MUST afgedwongen worden als een bovengrens op het aantal gemarkeerde vakjes per ingang.

De hele kandidatenlijst MUST eerst afgelopen worden met hooguit één gemarkeerd vakje per ingang. Pas wanneer geen enkele kandidaat op dat niveau te matchen is MUST de lijst opnieuw afgelopen worden zonder bovengrens. Een gespreid woord MUST dus altijd winnen van een langer woord dat alleen geclusterd past.

Een vakje waar twee woorden elkaar kruisen MUST voor beide ingangen meetellen in die bovengrens.

#### Scenario: Elke letter uit een andere ingang

- **WHEN** een puzzelwoord van 6 letters gekozen wordt in een rooster met 14 ingangen die dat toelaten
- **THEN** komen de 6 gemarkeerde vakjes uit 6 verschillende ingangen

#### Scenario: Een korter gespreid woord wint van een langer geclusterd woord

- **WHEN** het langste restwoord alleen te matchen is met twee vakjes uit dezelfde ingang, terwijl een korter restwoord volledig gespreid past
- **THEN** wordt het kortere gespreide woord gekozen

#### Scenario: Bovengrens valt weg als niemand past

- **WHEN** geen enkele kandidaat te matchen is met hooguit één vakje per ingang
- **THEN** wordt de lijst opnieuw afgelopen zonder bovengrens en wint de eerste kandidaat die dan past

#### Scenario: Kruisend vakje telt voor beide ingangen

- **WHEN** een gemarkeerd vakje op de kruising van twee woorden ligt
- **THEN** telt dat vakje mee in de bovengrens van allebei die ingangen

### Requirement: Voorkeursvolgorde van vakjes

Binnen een letter MUST de kandidaat-vakjes in een vaste voorkeursvolgorde staan, uitgedrukt als één strafscore: een vakje krijgt een strafpunt omdat het een kruising is en een strafpunt omdat het het genummerde startvakje van een ingang is. Een vakje dat allebei is MUST dus twee strafpunten krijgen en achter alle andere vakjes komen. Rij en kolom MUST de tiebreak zijn, zodat de volgorde deterministisch is.

#### Scenario: Gewoon vakje gaat voor

- **WHEN** een letter zowel op een gewoon vakje als op een kruisend vakje gevonden kan worden
- **THEN** wordt het gewone vakje gekozen

#### Scenario: Dubbel belast vakje gaat achteraan

- **WHEN** een vakje zowel een kruising als een startvakje is
- **THEN** komt het achter zowel de gewone vakjes als de vakjes met één strafpunt

### Requirement: Botsing met een genummerd startvakje

Wanneer een startvakje toch gemarkeerd wordt, MUST het ingangnummer op zijn plaats linksboven blijven staan en MUST het volgnummer van het puzzelwoord in een andere hoek van hetzelfde vakje getoond worden. Het ingangnummer MUST NOT weggelaten of verplaatst worden, omdat de aanwijzingen eraan hangen.

#### Scenario: Twee nummers in twee hoeken

- **WHEN** er geen ander vakje beschikbaar is en een startvakje gemarkeerd wordt
- **THEN** staat het ingangnummer linksboven en het volgnummer van het puzzelwoord in een andere hoek van datzelfde vakje, zonder dat zij elkaar overlappen

### Requirement: Markering in het rooster

De gemarkeerde vakjes MUST in het gedeelde rooster herkenbaar zijn met een grijsvulling die ook op een zwart-witprinter zichtbaar blijft; er MUST NOT uitsluitend op kleur vertrouwd worden. Elk gemarkeerd vakje MUST zijn volgnummer tonen. De rasterlijnen van het rooster MUST 2px zijn, zodat de puzzel steviger oogt en de grijsvulling zonder extra randverzwaring genoeg contrast houdt.

Wanneer er geen puzzelwoord is MUST het rooster geen enkele markering tonen.

#### Scenario: Gemarkeerd vakje is in monochroom herkenbaar

- **WHEN** een rooster met een puzzelwoord gerenderd wordt
- **THEN** hebben de gemarkeerde vakjes een grijsvulling en hun volgnummer, en is de markering niet uitsluitend een kleurverschil

#### Scenario: Puzzel zonder puzzelwoord

- **WHEN** een puzzel zonder puzzelwoord gerenderd wordt
- **THEN** bevat het rooster geen markeringen en geen volgnummers

### Requirement: Opslag van het puzzelwoord

Het puzzelwoord MUST opgeslagen worden met het woord, de aanwijzing van dat woord en de geordende vakjes in dezelfde bijgesneden coördinaten als de geplaatste ingangen. De opslag MUST leeg mogen zijn: een puzzel zonder puzzelwoord MUST een geldige, volledig gegenereerde puzzel blijven.

Het puzzelwoord MUST geschreven worden op dezelfde plek en in dezelfde bewerking als de geplaatste ingangen, zodat generatie, opnieuw leggen en opnieuw genereren alle drie hetzelfde pad volgen. Het MUST NOT via massa-toewijzing te zetten zijn.

#### Scenario: Puzzelwoord wordt bewaard

- **WHEN** de generatie een puzzelwoord oplevert
- **THEN** zijn het woord, de aanwijzing en de geordende vakjes op de puzzel opgeslagen

#### Scenario: Geen puzzelwoord is geen fout

- **WHEN** geen enkele kandidaat op het rooster te matchen is
- **THEN** wordt de puzzel gewoon als gegenereerd opgeslagen, zonder puzzelwoord, en MUST NOT `failed_at` gezet worden

#### Scenario: Mislukte herroll laat het puzzelwoord staan

- **WHEN** een herroll wordt afgewezen omdat er te weinig woorden geplaatst konden worden
- **THEN** blijven het oude rooster en het oude puzzelwoord ongewijzigd
