# crossword-puzzle-management

## Purpose

Beheer van kruiswoordpuzzels in het admin-panel: aanmaken via een slide-over-formulier op basis van gegenereerde begrijpend-leesoefeningen, de generatiestatus volgen, de puzzel bekijken, een werkblad en een apart antwoordblad printen, en het rooster opnieuw leggen of de puzzel opnieuw genereren.

## Requirements

### Requirement: Kruiswoordpuzzel aanmaken
Het systeem MUST een ingelogde gebruiker in het admin-panel een kruiswoordpuzzel laten aanmaken via een slide-over-formulier met: begrijpend-leesoefeningen (verplicht, minimaal 3 en maximaal 6, alleen oefeningen waarvan `generated_at` gezet is), groep (verplicht, `PuzzleGroup`-enum met groep 4 t/m 8) en level (verplicht, geheel getal 1-50). Bij het gekozen level MUST de bijbehorende omschrijving van het aanwijzingstype als helptekst zichtbaar zijn, zoals de levelband dat bij een oefening doet. Het aantal woorden en de woordlengtes MUST NOT als formuliervelden aanwezig zijn: die volgen volledig uit de gekozen groep. Na opslaan MUST het record direct bestaan (status "bezig") en MUST de generatie-job gedispatcht worden.

#### Scenario: Succesvol aanmaken
- **WHEN** de gebruiker vier gegenereerde oefeningen selecteert, groep 6 en level 15 kiest en opslaat
- **THEN** bestaat er een `CrosswordPuzzle` met die groep en dat level, zijn de vier oefeningen eraan gekoppeld, zijn `generated_at` en `failed_at` null, en is de generatie-job gedispatcht

#### Scenario: Te weinig teksten geselecteerd
- **WHEN** de gebruiker opslaat met twee geselecteerde oefeningen
- **THEN** toont het formulier een validatiefout en wordt er geen record aangemaakt

#### Scenario: Te veel teksten geselecteerd
- **WHEN** de gebruiker probeert zeven oefeningen te selecteren
- **THEN** staat het formulier dat niet toe en wordt er geen record aangemaakt

#### Scenario: Alleen gegenereerde oefeningen selecteerbaar
- **WHEN** de gebruiker de tekstselectie opent terwijl er oefeningen bestaan met status "bezig" of "mislukt"
- **THEN** zijn alleen oefeningen met `generated_at` gezet selecteerbaar

#### Scenario: Validatie van verplichte velden
- **WHEN** de gebruiker opslaat zonder groep of level, of met een level buiten 1-50
- **THEN** toont het formulier validatiefouten en wordt er geen record aangemaakt

#### Scenario: Helptekst bij het level
- **WHEN** de gebruiker level 25 kiest
- **THEN** toont het formulier de omschrijving van het aanwijzingstype voor de band 21-30 (de aanwijzing verwijst naar de tekst, het kind moet terugzoeken)

### Requirement: Lijstweergave met generatiestatus
De lijstpagina MUST per puzzel minimaal de titel, groep, level, aantal gebruikte teksten, generatiestatus en aanmaakdatum tonen. De status MUST afgeleid worden uit de timestamps: beide null = "bezig", `generated_at` gezet = "klaar", `failed_at` gezet = "mislukt". Zolang een puzzel "bezig" is MUST de lijst de status zonder handmatige verversing actueel maken (polling). De tabel MUST NOT bulk-acties bevatten.

#### Scenario: Status wordt vanzelf actueel
- **WHEN** een puzzel wordt aangemaakt en de generatie op de achtergrond slaagt
- **THEN** verandert de status in de lijst binnen de polling-interval van "bezig" naar "klaar" zonder dat de gebruiker de pagina ververst

#### Scenario: Mislukte generatie zichtbaar
- **WHEN** de generatie definitief mislukt is en `failed_at` gezet is
- **THEN** toont de lijst de status "mislukt" voor die puzzel

### Requirement: Detailweergave van een gegenereerde puzzel
De View-pagina MUST voor een gegenereerde puzzel tonen: de invoergegevens (groep, level met de omschrijving van het aanwijzingstype, de gebruikte oefeningen), het gelegde rooster, en de aanwijzingen gesplitst in horizontaal en verticaal met per aanwijzing het nummer en de bron-oefening. Voor een puzzel die nog bezig of mislukt is MUST de pagina de status tonen in plaats van inhoud.

#### Scenario: Gegenereerde puzzel bekijken
- **WHEN** de gebruiker een puzzel met `generated_at` gezet opent
- **THEN** ziet de gebruiker het rooster, de horizontale en verticale aanwijzingen met nummers, en de lijst met gebruikte oefeningen

#### Scenario: Puzzel nog bezig
- **WHEN** de gebruiker een puzzel opent waarvan `generated_at` en `failed_at` beide null zijn
- **THEN** toont de pagina de status "bezig" en geen rooster of aanwijzingen

### Requirement: Printbaar werkblad
Er MUST een route zijn die een printbaar werkblad voor een gegenereerde puzzel oplevert als HTML met een printstylesheet (A4, marge 2cm). Het werkblad MUST één document van twee pagina's zijn: pagina 1 bevat uitsluitend het lege rooster met de genummerde startvakjes, gevolgd door een pagina-einde, en pagina 2 bevat de aanwijzingen in twee kolommen onder de koppen "Horizontaal" en "Verticaal". Bij een level van 21 of hoger MUST bij elke aanwijzing de bron-oefening vermeld worden zodat het kind weet waar het moet terugzoeken. De hokjesgrootte MUST berekend worden uit de roosterafmeting en de beschikbare breedte, met een bovengrens van ongeveer 12mm. Er MUST NOT een PDF-library gebruikt worden. Voor een puzzel die nog niet gegenereerd is MUST de route een 404 geven.

#### Scenario: Werkblad openen
- **WHEN** de gebruiker het werkblad van een gegenereerde puzzel opent
- **THEN** toont pagina 1 het lege rooster met genummerde startvakjes en pagina 2 de aanwijzingen gesplitst in horizontaal en verticaal

#### Scenario: Bronvermelding bij hoog level
- **WHEN** het werkblad van een puzzel met level 30 wordt geopend
- **THEN** staat bij elke aanwijzing vermeld uit welke oefening het woord komt

#### Scenario: Geen bronvermelding bij laag level
- **WHEN** het werkblad van een puzzel met level 8 wordt geopend
- **THEN** staat er geen bron-oefening bij de aanwijzingen

#### Scenario: Werkblad van een niet-gegenereerde puzzel
- **WHEN** het werkblad wordt opgevraagd voor een puzzel waarvan `generated_at` null is
- **THEN** geeft de route een 404

### Requirement: Printbaar antwoordblad
Er MUST een aparte route zijn die het ingevulde rooster oplevert, zodat de oplossing nooit per ongeluk met het werkblad meegeprint wordt. Het antwoordblad MUST dezelfde printopmaak gebruiken als het werkblad. Voor een puzzel die nog niet gegenereerd is MUST de route een 404 geven.

#### Scenario: Antwoordblad openen
- **WHEN** de gebruiker het antwoordblad van een gegenereerde puzzel opent
- **THEN** toont de pagina het rooster met alle letters ingevuld

#### Scenario: Werkblad bevat de oplossing niet
- **WHEN** het werkblad wordt geopend
- **THEN** bevat het geen enkele ingevulde letter

### Requirement: Opnieuw leggen zonder AI
Er MUST een actie zijn die het rooster opnieuw legt met een nieuwe seed op basis van de al opgeslagen kandidaatwoorden, zonder een AI-call te doen. De actie MUST de aanwijzingen ongewijzigd laten en alleen de plaatsing en de nummering herschrijven.

#### Scenario: Opnieuw leggen levert een ander rooster
- **WHEN** de gebruiker "opnieuw leggen" kiest op een gegenereerde puzzel
- **THEN** is de seed gewijzigd, is het rooster opnieuw gelegd en is er geen AI-call gedaan

#### Scenario: Kandidaten blijven behouden
- **WHEN** het rooster opnieuw gelegd wordt
- **THEN** zijn de opgeslagen kandidaatwoorden met hun aanwijzingen ongewijzigd

### Requirement: Opnieuw genereren met nieuwe woorden
Er MUST een aparte actie zijn die de volledige generatie opnieuw uitvoert: nieuwe kandidaatwoorden en aanwijzingen via AI, gevolgd door een nieuwe plaatsing. De actie MUST de puzzel terugzetten naar status "bezig" en de generatie-job opnieuw dispatchen.

#### Scenario: Opnieuw genereren
- **WHEN** de gebruiker "opnieuw genereren" kiest
- **THEN** worden `generated_at` en `failed_at` op null gezet en wordt de generatie-job opnieuw gedispatcht

### Requirement: Acties gegroepeerd en afgeschermd
Rij-acties in de tabel en header-acties op de View-pagina MUST in een `ActionGroup` met het verticale-ellipsis-icoon staan, met uitzondering van de losse `CreateAction` in de header van de lijstpagina. Toegang tot alle acties en pagina's MUST geregeld zijn via een policy met de zeven abilities, waarbij `forceDelete` `false` teruggeeft. Alle zichtbare teksten MUST via `lang/nl` lopen; er MUST NOT Nederlandse tekst hardcoded in PHP of Blade staan.

#### Scenario: Ongeautoriseerde gebruiker
- **WHEN** een gebruiker zonder rechten de lijst- of View-pagina van kruiswoordpuzzels opvraagt
- **THEN** wordt de toegang geweigerd

#### Scenario: Acties in een groep
- **WHEN** de gebruiker de tabel bekijkt
- **THEN** staan de rij-acties achter één kebab-menu en zijn er geen bulk-acties
