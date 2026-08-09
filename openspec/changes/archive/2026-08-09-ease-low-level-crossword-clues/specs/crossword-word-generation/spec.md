## ADDED Requirements

### Requirement: Aanwijzingen zijn invulzinnen

Elke aanwijzing MUST een zin zijn met een open plek waar precies het gezochte woord in past, voor alle levels 1 tot en met 50. De open plek MUST als beletselteken geschreven worden. Een losse omschrijving zonder open plek MUST NOT geschreven worden.

Waar de open plek in de zin valt MUST het level volgen. Tot en met band 11-20 MUST de open plek aan het eind van de zin staan, zodat het kind een hele aanloop leest en daarna invult. Vanaf band 21-30 MUST minstens twee van de acht aanwijzingen de open plek middenin de zin hebben, want dan moet het kind de woorden vóór én ná de open plek combineren, en dat is de zwaardere leesvaardigheid.

De positie van de open plek staat los van de zinsbouw: een gewone zin die op zijn lijdend voorwerp eindigt, eindigt ook op de open plek. De variatie-eis hieronder geldt daarom op elke band, ook waar de open plek verplicht aan het eind staat.

De aanwijzing MUST hooguit zestien woorden tellen. Die grens ligt hoger dan bij een losse omschrijving, want een invulzin heeft aanloop nodig voordat de open plek valt.

De invulzin MUST grammaticaal kloppen wanneer het gezochte woord op de open plek wordt ingevuld, zodat de zinsbouw zelf het kind helpt in plaats van hindert.

#### Scenario: Aanwijzing eindigt op een open plek

- **WHEN** de agent een aanwijzing schrijft bij het woord "VERZORGERS"
- **THEN** levert hij een zin op als "Mensen die voor dieren in het park zorgen heten ..." en geen losse omschrijving

#### Scenario: Invulzin op een hoog level

- **WHEN** een puzzel met level 45 gegenereerd wordt
- **THEN** is de aanwijzing ook daar een invulzin, alleen vraagt hij om de hoofdgedachte in plaats van om een omschrijving

#### Scenario: Aanwijzing blijft binnen de lengtegrens

- **WHEN** de prompt voor een tekst wordt opgebouwd
- **THEN** instrueert die om aanwijzingen van hooguit zestien woorden te schrijven

### Requirement: Aanwijzingen variëren in zinsbouw

De acht aanwijzingen bij een tekst MUST in zinsbouw van elkaar verschillen. Hoogstens drie ervan MAY de naamwoordelijke constructie "heet ...", "heten ..." of "is een ..." gebruiken; bij de rest MUST het gezochte woord lijdend voorwerp of bepaling zijn in een gewone zin. Zonder die eis eindigt vrijwel elke aanwijzing op "heten de ...", waardoor het invullen een routine wordt in plaats van lezen.

#### Scenario: Niet elke aanwijzing is een definitie

- **WHEN** de agent acht aanwijzingen bij één tekst schrijft
- **THEN** staan daar zinnen tussen als "Tegen de modder trekken de verzorgers hoge ..." naast de definitievorm "De mensen die voor de dieren zorgen heten de ..."

#### Scenario: Open plek middenin de zin op een hoog level

- **WHEN** een puzzel met level 45 gegenereerd wordt
- **THEN** vraagt de prompt om minstens twee aanwijzingen met de open plek middenin, zoals "Uit de gevulde ... kunnen de dieren 's avonds drinken."

#### Scenario: Open plek aan het eind op een laag level

- **WHEN** een puzzel met level 1 of level 15 gegenereerd wordt
- **THEN** vraagt de prompt om de open plek bij elke aanwijzing aan het eind van de zin

### Requirement: Het level stuurt de woordkeuze

Het level MUST naast het type aanwijzing ook sturen welke woorden uit de tekst gekozen worden. Op lage levels MUST de agent concrete, alledaagse woorden kiezen die een kind van die groep al kent; op hoge levels MUST hij juist de abstractere en meer vaktalige woorden uit de tekst nemen. Zonder deze sturing levert een laag level in een hogere groep woorden op als "TERRITORIUM", waardoor het level in de praktijk niets makkelijker maakt.

De groep MUST de woordlengte en de toegestane woordsoorten blijven bepalen; het level MUST binnen die ruimte kiezen. De twee MUST NOT met elkaar in conflict komen: een level MUST NOT een woord buiten de lengtegrenzen van de groep afdwingen.

De omschrijving van de woordkeuze MUST per aanwijzingsband uit dezelfde enum komen als het type aanwijzing, als een aparte omschrijving naast die van het type. Zij MUST NOT in dezelfde tekst samengevoegd worden, omdat de een een instructie aan de AI is en de ander de helptekst bij het formulierveld.

#### Scenario: Laag level kiest alledaagse woorden

- **WHEN** een puzzel voor groep 6 met level 1 gegenereerd wordt
- **THEN** instrueert de prompt om concrete, alledaagse woorden te kiezen en niet om abstracte begrippen

#### Scenario: Hoog level kiest abstractere woorden

- **WHEN** een puzzel voor groep 6 met level 45 gegenereerd wordt
- **THEN** instrueert de prompt om juist de abstractere woorden uit de tekst te nemen

#### Scenario: De groep blijft de lengte bepalen

- **WHEN** een puzzel voor groep 4 met level 45 gegenereerd wordt
- **THEN** vraagt de prompt nog steeds om woorden van 4 tot 7 letters, ook al stuurt het level naar abstractere woorden

## MODIFIED Requirements

### Requirement: Agent met structured output
Er MUST een agent-class zijn volgens hetzelfde patroon als de bestaande `ExerciseWriter` (`laravel/ai`, `Agent` plus `HasStructuredOutput`). De agent MUST een JSON-schema afdwingen met een array van 8 objecten met `word` (string) en `clue` (string). Markdown-uitvoer parsen is NIET toegestaan. De instructies MUST bevatten: dat het woord letterlijk in de aangeleverde tekst voorkomt, dat het binnen de lengtegrenzen van de groep valt, dat het geen spaties of leestekens bevat, dat de aanwijzing een invulzin met een open plek is, dat de aanwijzingen in zinsbouw variëren, dat de taal van de aanwijzing past bij de groep, en dat het type aanwijzing, de woordkeuze en de plek van de open plek de levelband volgen.

De aanwijzing MUST NOT het gezochte woord voluit bevatten, op geen enkel level. Voor band 1-10 MUST een deel van een samenstelling wél toegestaan zijn, omdat die band juist vraagt om een aanwijzing die het sleutelwoord bijna noemt; een verbod op elk woorddeel maakt die band onuitvoerbaar en levert aanwijzingen op die om zichzelf heen draaien. Voor de banden vanaf 11 MUST ook een woorddeel vermeden worden.

#### Scenario: Schema wordt afgedwongen
- **WHEN** de agent antwoordt
- **THEN** is het resultaat een gevalideerd object met 8 woord-en-aanwijzing-paren, niet een tekst die geparseerd moet worden

#### Scenario: Woorddeel mag op de laagste band
- **WHEN** een puzzel met level 3 het woord "WATERBAKKEN" oplevert
- **THEN** mag de invulzin het woorddeel "bakken" bevatten, maar MUST NOT het volledige woord "waterbakken" bevatten

#### Scenario: Woorddeel vermijden vanaf band 11
- **WHEN** een puzzel met level 25 het woord "WATERBAKKEN" oplevert
- **THEN** instrueert de prompt om ook het woorddeel te vermijden
