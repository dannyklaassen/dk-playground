## MODIFIED Requirements

### Requirement: Detailweergave van een gegenereerde puzzel
De View-pagina MUST voor een gegenereerde puzzel tonen: de invoergegevens (groep, level met de omschrijving van het aanwijzingstype, de gebruikte oefeningen), het gelegde rooster, en de aanwijzingen gesplitst in horizontaal en verticaal met per aanwijzing het nummer en de bron-oefening. Bij de Kenmerken MUST het puzzelwoord getoond worden met de aanwijzing van dat woord erbij; heeft de puzzel geen puzzelwoord, dan MUST dat veld leeg blijven in plaats van een lege plek te reserveren. Voor een puzzel die nog bezig of mislukt is MUST de pagina de status tonen in plaats van inhoud.

#### Scenario: Gegenereerde puzzel bekijken
- **WHEN** de gebruiker een puzzel met `generated_at` gezet opent
- **THEN** ziet de gebruiker het rooster, de horizontale en verticale aanwijzingen met nummers, en de lijst met gebruikte oefeningen

#### Scenario: Puzzelwoord bij de Kenmerken
- **WHEN** de gebruiker een gegenereerde puzzel met een puzzelwoord opent
- **THEN** staat het puzzelwoord met zijn aanwijzing bij de Kenmerken

#### Scenario: Puzzel nog bezig
- **WHEN** de gebruiker een puzzel opent waarvan `generated_at` en `failed_at` beide null zijn
- **THEN** toont de pagina de status "bezig" en geen rooster of aanwijzingen

### Requirement: Printbaar werkblad
Er MUST een route zijn die een printbaar werkblad voor een gegenereerde puzzel oplevert als HTML met een printstylesheet (A4, marge 2cm). Het werkblad MUST één document van twee pagina's zijn: pagina 1 bevat uitsluitend het lege rooster met de genummerde startvakjes, gevolgd door een pagina-einde, en pagina 2 bevat de aanwijzingen in twee kolommen onder de koppen "Horizontaal" en "Verticaal". Bij een level van 21 of hoger MUST bij elke aanwijzing de bron-oefening vermeld worden zodat het kind weet waar het moet terugzoeken. De hokjesgrootte van het rooster MUST berekend worden uit de roosterafmeting en de beschikbare breedte, met een bovengrens van ongeveer 12mm. Er MUST NOT een PDF-library gebruikt worden. Voor een puzzel die nog niet gegenereerd is MUST de route een 404 geven.

Heeft de puzzel een puzzelwoord, dan MUST onder het rooster op pagina 1 een rij genummerde lege invulhokjes staan, één per letter, met een vaste hokjesgrootte die niet met het rooster meeschaalt. Het werkblad MUST NOT het puzzelwoord zelf tonen en MUST NOT de aanwijzing van het puzzelwoord tonen, zodat het antwoord uit de letters van het rooster moet komen. Heeft de puzzel geen puzzelwoord, dan MUST de rij invulhokjes volledig achterwege blijven.

#### Scenario: Werkblad openen
- **WHEN** de gebruiker het werkblad van een gegenereerde puzzel opent
- **THEN** toont pagina 1 het lege rooster met genummerde startvakjes en pagina 2 de aanwijzingen gesplitst in horizontaal en verticaal

#### Scenario: Werkblad vraagt om het puzzelwoord
- **WHEN** het werkblad van een puzzel met een puzzelwoord geopend wordt
- **THEN** zijn de betreffende vakjes gemarkeerd met hun volgnummer, staat er onder het rooster een rij genummerde lege hokjes, en staan het puzzelwoord en zijn aanwijzing er nergens

#### Scenario: Werkblad zonder puzzelwoord
- **WHEN** het werkblad van een puzzel zonder puzzelwoord geopend wordt
- **THEN** bevat het rooster geen markeringen en staat er geen rij invulhokjes onder

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
Er MUST een aparte route zijn die het ingevulde rooster oplevert, zodat de oplossing nooit per ongeluk met het werkblad meegeprint wordt. Het antwoordblad MUST dezelfde printopmaak gebruiken als het werkblad. Heeft de puzzel een puzzelwoord, dan MUST het antwoordblad dat woord voluit tonen en MUST het de bijbehorende vakjes in het ingevulde rooster markeren. Voor een puzzel die nog niet gegenereerd is MUST de route een 404 geven.

#### Scenario: Antwoordblad openen
- **WHEN** de gebruiker het antwoordblad van een gegenereerde puzzel opent
- **THEN** toont de pagina het rooster met alle letters ingevuld

#### Scenario: Antwoordblad toont het puzzelwoord
- **WHEN** het antwoordblad van een puzzel met een puzzelwoord geopend wordt
- **THEN** staat het puzzelwoord er voluit en zijn de betreffende vakjes in het ingevulde rooster gemarkeerd

#### Scenario: Werkblad bevat de oplossing niet
- **WHEN** het werkblad wordt geopend
- **THEN** bevat het geen enkele ingevulde letter

### Requirement: Opnieuw leggen zonder AI
Er MUST een actie zijn die het rooster opnieuw legt met een nieuwe seed op basis van de al opgeslagen kandidaatwoorden, zonder een AI-call te doen. De actie MUST de aanwijzingen ongewijzigd laten en alleen de plaatsing en de nummering herschrijven. De actie MUST alleen zichtbaar zijn voor een gegenereerde puzzel; een puzzel die nog bezig of mislukt is heeft geen kandidaten om mee te werken.

Het leggen en het kiezen van het puzzelwoord gebeuren synchroon in het request, dus de actie MUST een rate limit hebben zodat herhaald klikken geen rij workers bezet houdt. Het zoeken naar een passend puzzelwoord MUST een harde bovengrens aan zoekstappen hebben: wordt die bereikt, dan telt het woord als "past niet" en gaat de picker door naar de volgende kandidaat.

Het puzzelwoord MUST bij dezelfde bewerking opnieuw bepaald worden over het nieuwe rooster, eveneens zonder AI-call. Het gekozen woord MAY daarbij wisselen, omdat een woord dat in het nieuwe rooster wel geplaatst is uit de restvoorraad valt. Een puzzel die nog geen puzzelwoord had MUST er via deze actie alsnog een kunnen krijgen.

#### Scenario: Opnieuw leggen levert een ander rooster
- **WHEN** de gebruiker "opnieuw leggen" kiest op een gegenereerde puzzel
- **THEN** is de seed gewijzigd, is het rooster opnieuw gelegd en is er geen AI-call gedaan

#### Scenario: Nieuwe vakjes voor het puzzelwoord
- **WHEN** de gebruiker "opnieuw leggen" kiest op een puzzel met een puzzelwoord
- **THEN** zijn de gemarkeerde vakjes opnieuw toegewezen aan het nieuwe rooster en is er geen AI-call gedaan

#### Scenario: Puzzelwoord dat in het rooster belandt maakt plaats
- **WHEN** het eerder gekozen puzzelwoord in het nieuwe rooster wel geplaatst is
- **THEN** valt het uit de restvoorraad en wordt de volgende kandidaat in de vaste volgorde het puzzelwoord

#### Scenario: Oude puzzel krijgt alsnog een puzzelwoord
- **WHEN** de gebruiker "opnieuw leggen" kiest op een gegenereerde puzzel die nog geen puzzelwoord heeft
- **THEN** wordt er een puzzelwoord toegewezen als er een matchbare kandidaat is

#### Scenario: Kandidaten blijven behouden
- **WHEN** het rooster opnieuw gelegd wordt
- **THEN** zijn de opgeslagen kandidaatwoorden met hun aanwijzingen ongewijzigd

#### Scenario: Opnieuw leggen alleen bij een gelegd rooster
- **WHEN** de gebruiker een puzzel opent die nog bezig of mislukt is
- **THEN** is "opnieuw leggen" niet zichtbaar

### Requirement: Opnieuw genereren met nieuwe woorden
Er MUST een aparte actie zijn die de volledige generatie opnieuw uitvoert: nieuwe kandidaatwoorden en aanwijzingen via AI, gevolgd door een nieuwe plaatsing. De actie MUST de puzzel terugzetten naar status "bezig" en de generatie-job opnieuw dispatchen.

Omdat elke run betaalde AI-calls kost MUST de actie een bevestiging vragen en een rate limit hebben, en MUST hij verborgen zijn voor een gegenereerde puzzel: een puzzel die goed is uitgekomen heeft niets te winnen bij opnieuw genereren. Voor een mislukte én voor een nog bezige puzzel MUST de actie wél zichtbaar zijn, want een job die sneuvelt zonder `failed_at` te zetten laat de puzzel anders voorgoed op "bezig" staan, inclusief de polling die daaraan hangt.

#### Scenario: Opnieuw genereren
- **WHEN** de gebruiker "opnieuw genereren" kiest
- **THEN** worden `generated_at` en `failed_at` op null gezet en wordt de generatie-job opnieuw gedispatcht

#### Scenario: Vastgelopen puzzel is te redden
- **WHEN** de gebruiker een puzzel opent waarvan `generated_at` en `failed_at` beide null zijn
- **THEN** is "opnieuw genereren" zichtbaar

#### Scenario: Geen opnieuw genereren voor een geslaagde puzzel
- **WHEN** de gebruiker een gegenereerde puzzel opent
- **THEN** is "opnieuw genereren" niet zichtbaar

### Requirement: Acties gegroepeerd en afgeschermd
Rij-acties in de tabel en header-acties op de View-pagina MUST in een `ActionGroup` met het verticale-ellipsis-icoon staan, met twee uitzonderingen: de losse `CreateAction` in de header van de lijstpagina, en de twee printknoppen op de View-pagina. Die printknoppen MUST als eigen knop naast de groep staan, omdat printen de gewone handeling op die pagina is en niet achter een kebab-menu hoort. Toegang tot alle acties en pagina's MUST geregeld zijn via een policy met de zeven abilities, waarbij `forceDelete` `false` teruggeeft. Alle zichtbare teksten MUST via `lang/nl` lopen; er MUST NOT Nederlandse tekst hardcoded in PHP of Blade staan.

De rij-acties in de tabel MUST beperkt blijven tot hernoemen en verwijderen. Printen, opnieuw leggen en opnieuw genereren horen bij één puzzel tegelijk en MUST NOT vanuit de lijst aangeboden worden; de gebruiker opent daarvoor de View-pagina.

#### Scenario: Ongeautoriseerde gebruiker
- **WHEN** een gebruiker zonder rechten de lijst- of View-pagina van kruiswoordpuzzels opvraagt
- **THEN** wordt de toegang geweigerd

#### Scenario: Acties in een groep
- **WHEN** de gebruiker de tabel bekijkt
- **THEN** staan de rij-acties achter één kebab-menu en zijn er geen bulk-acties

#### Scenario: Printknoppen los op de View-pagina
- **WHEN** de gebruiker een gegenereerde puzzel opent
- **THEN** staan "werkblad" en "antwoordblad" als eigen knoppen in de header, naast de groep met de overige acties

#### Scenario: Geen puzzelacties op de rij
- **WHEN** de gebruiker de lijst bekijkt
- **THEN** biedt het kebab-menu van een rij geen printen, opnieuw leggen of opnieuw genereren
