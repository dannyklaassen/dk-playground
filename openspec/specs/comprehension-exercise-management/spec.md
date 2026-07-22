# comprehension-exercise-management

## Purpose

Beheer van begrijpend-leesoefeningen in het admin-panel: aanmaken via slide-over, lijst met live generatiestatus, detailweergave, printbare werk- en antwoordbladen, opnieuw genereren en verwijderen — afgeschermd met een policy en volledig Nederlandstalig.

## Requirements

### Requirement: Oefening aanmaken
Het systeem MUST een ingelogde gebruiker in het admin-panel een begrijpend-leesoefening laten aanmaken via een slide-over-formulier met: leesniveau (verplicht, `ReadingLevel`-enum), onderwerp (verplicht, vrij tekstveld), omschrijving (optioneel, tekstveld) en level (verplicht, geheel getal 1-50). Bij het gekozen level MUST de bijbehorende `LevelBand`-omschrijving als helptekst zichtbaar zijn. Na opslaan MUST het record direct bestaan (status "bezig") en MUST de generatie-job gedispatcht worden.

#### Scenario: Succesvol aanmaken
- **WHEN** de gebruiker het formulier invult met leesniveau "Eind groep 6", onderwerp "pinguïns", level 12 en opslaat
- **THEN** bestaat er een `ComprehensionExercise` met die waarden, zijn `generated_at` en `failed_at` null, en is de generatie-job gedispatcht

#### Scenario: Validatie van verplichte velden
- **WHEN** de gebruiker opslaat zonder leesniveau, onderwerp of level, of met een level buiten 1-50
- **THEN** toont het formulier validatiefouten en wordt er geen record aangemaakt

#### Scenario: Levelband-helptekst
- **WHEN** de gebruiker level 35 kiest
- **THEN** toont het formulier de omschrijving van de band 31-40 (impliciete informatie, bedoeling van de schrijver, woordbetekenis uit context, verbanden tussen alinea's)

### Requirement: Lijstweergave met generatiestatus
De lijstpagina MUST per oefening minimaal onderwerp, leesniveau, level, generatiestatus en aanmaakdatum tonen. De status MUST afgeleid worden uit de timestamps: beide null = "bezig", `generated_at` gezet = "klaar", `failed_at` gezet = "mislukt". Zolang een oefening "bezig" is MUST de lijst de status zonder handmatige verversing actueel maken (polling).

#### Scenario: Status wordt vanzelf actueel
- **WHEN** een oefening wordt aangemaakt en de generatie op de achtergrond slaagt
- **THEN** verandert de status in de lijst binnen de polling-interval van "bezig" naar "klaar" zonder dat de gebruiker de pagina ververst

#### Scenario: Mislukte generatie zichtbaar
- **WHEN** de generatie definitief mislukt is (`failed_at` gezet)
- **THEN** toont de lijst de status "mislukt" voor die oefening

### Requirement: Detailweergave van een gegenereerde oefening
De View-pagina MUST voor een gegenereerde oefening tonen: de invoergegevens (leesniveau, onderwerp, omschrijving, level met bandomschrijving), de titel, de 6 genummerde alinea's, de 5 vragen met antwoordopties A-D, en het antwoordblad (per vraag: goed antwoord, alinea, leesvaardigheid als NL-label, en de bewijszin indien aanwezig). Voor een oefening die nog bezig of mislukt is MUST de pagina de status tonen in plaats van inhoud.

#### Scenario: Gegenereerde oefening bekijken
- **WHEN** de gebruiker een oefening met `generated_at` gezet opent
- **THEN** ziet de gebruiker titel, alle 6 alinea's, alle 5 vragen met A-D en het volledige antwoordblad

#### Scenario: Nog niet gegenereerde oefening bekijken
- **WHEN** de gebruiker een oefening opent waarvan de generatie nog bezig is
- **THEN** toont de pagina de status "bezig" en geen tekst, vragen of antwoordblad

### Requirement: Oefening verwijderen
De gebruiker MUST een oefening kunnen verwijderen via de rij-acties in de lijst (gegroepeerd in het kebab-menu conform de projectconventies).

#### Scenario: Verwijderen
- **WHEN** de gebruiker de verwijder-actie bevestigt
- **THEN** is de oefening verwijderd en niet meer zichtbaar in de lijst

### Requirement: Printbaar werkblad
Het systeem MUST voor een gegenereerde oefening een printgeoptimaliseerde werkblad-pagina bieden met titel, de 6 genummerde alinea's en de 5 vragen met antwoordopties A-D — zonder enige verwijzing naar de juiste antwoorden. De pagina MUST alleen toegankelijk zijn voor ingelogde gebruikers en MUST bereikbaar zijn via een actie op de View-pagina.

#### Scenario: Werkblad openen
- **WHEN** de gebruiker de werkblad-actie gebruikt bij een gegenereerde oefening
- **THEN** opent een printbare pagina met titel, alinea's en vragen, zonder antwoorden of leesvaardigheden

#### Scenario: Werkblad van niet-gegenereerde oefening
- **WHEN** het werkblad wordt opgevraagd voor een oefening zonder `generated_at`
- **THEN** wordt het werkblad niet getoond (404)

#### Scenario: Onbevoegde toegang
- **WHEN** een niet-ingelogde bezoeker de werkblad-URL opent
- **THEN** wordt de toegang geweigerd

### Requirement: Printbaar antwoordblad
Het systeem MUST voor een gegenereerde oefening een aparte printgeoptimaliseerde antwoordblad-pagina bieden met per vraag: het goede antwoord (letter), de alinea, de geoefende leesvaardigheid (NL-label) en — indien aanwezig — de bewijszin. Dezelfde toegangsregels als het werkblad gelden.

#### Scenario: Antwoordblad openen
- **WHEN** de gebruiker de antwoordblad-actie gebruikt bij een gegenereerde oefening
- **THEN** opent een printbare pagina met de antwoordtabel voor alle 5 vragen inclusief bewijszinnen waar aanwezig

### Requirement: Opnieuw genereren na mislukking
Voor een oefening met status "mislukt" MUST de gebruiker een actie "Opnieuw genereren" hebben die `failed_at` wist en de generatie-job opnieuw dispatcht.

#### Scenario: Opnieuw genereren
- **WHEN** de gebruiker "Opnieuw genereren" uitvoert op een mislukte oefening
- **THEN** is `failed_at` null, toont de oefening status "bezig" en is de generatie-job opnieuw gedispatcht

### Requirement: Autorisatie en Nederlandse labels
Alle resource-functionaliteit MUST afgeschermd zijn met een `ComprehensionExercisePolicy` met de volledige ability-set. Alle gebruikersgerichte teksten (labels, kolommen, acties, statussen, secties, notificaties) MUST Nederlands zijn via `lang/nl/`-bestanden conform de vertaalconventies; geen hardgecodeerde Nederlandse strings in PHP.

#### Scenario: Labels komen uit lang-bestanden
- **WHEN** de resource, formulieren, tabellen en printpagina's worden gerenderd
- **THEN** komen alle zichtbare teksten uit `lang/nl/admin.php` of `lang/nl/common.php`
