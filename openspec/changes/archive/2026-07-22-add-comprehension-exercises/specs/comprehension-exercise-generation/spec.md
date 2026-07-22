## ADDED Requirements

### Requirement: Generatie via queued job
De generatie MUST asynchroon verlopen via een `GenerateComprehensionExercise`-job op de bestaande queue. De job MUST de AI-agent synchroon aanroepen en zelf validatie, retry en opslag afhandelen. De job-timeout MUST ruim genoeg zijn voor meerdere Opus-pogingen (minimaal 300 seconden). Als de job definitief faalt (ook bij onverwachte exceptions) MUST `failed_at` gezet worden zodat de oefening nooit eeuwig "bezig" blijft.

#### Scenario: Job verwerkt oefening
- **WHEN** de job draait voor een oefening zonder inhoud
- **THEN** roept hij de AI-agent aan en slaat bij succes de inhoud op

#### Scenario: Onverwachte fout
- **WHEN** de job faalt door een onafgevangen exception (bijv. API onbereikbaar na Laravels job-retries)
- **THEN** wordt `failed_at` op de oefening gezet

### Requirement: AI-agent met didactische system prompt
Er MUST een agent-class zijn (`laravel/ai`, `Agent` + `HasStructuredOutput`) waarvan de instructies de volledige aangeleverde didactische specificatie bevatten: rol (ervaren auteur van begrijpend-leesmateriaal voor de Nederlandse basisschool), tekstontwerp (pakkende titel, 300-450 woorden afhankelijk van leesniveau, exact 6 alinea's met afwisselende lengte, geen opsommingen/dialogen/emoji's, geen benodigde voorkennis), vraagontwerp (5 multiplechoicevragen, geloofwaardige afleiders, precies één goed antwoord, gevarieerde leesvaardigheden, eerlijke A-D-verdeling) en de kwaliteitseisen. De prompt MUST instrueren dat `evidence` een letterlijk citaat uit de genoemde alinea is (of null bij vaardigheden zonder aanwijsbare zin) en dat `paragraph` de alinea met de beslissende informatie is.

#### Scenario: Prompt bevat de didactische spec
- **WHEN** de agent-instructies worden opgebouwd
- **THEN** bevatten zij de tekst-, vraag- en kwaliteitseisen uit de functionele specificatie

### Requirement: Invoervariabelen en levelband in de prompt
Het user-bericht aan de agent MUST de vier invoervelden bevatten: leesniveau (NL-label), onderwerp, omschrijving (of de instructie zelf een invalshoek te kiezen als deze leeg is) en level. De didactische betekenis van het level MUST uit de `LevelBand`-enum komen — dezelfde bron als de helptekst in het formulier — zodat gebruiker en AI dezelfde definitie hanteren.

#### Scenario: Prompt voor level 12
- **WHEN** een oefening met level 12 wordt gegenereerd
- **THEN** bevat de prompt de bandomschrijving 11-20 (eenvoudige verwijswoorden, informatie combineren binnen één alinea, eenvoudige oorzaak-gevolgrelaties)

#### Scenario: Lege omschrijving
- **WHEN** de omschrijving leeg is
- **THEN** instrueert de prompt de AI zelfstandig een passende invalshoek te kiezen

### Requirement: Structured output schema
De agent MUST een JSON-schema afdwingen met exact deze structuur: `title` (string), `paragraphs` (array van exact 6 strings), `questions` (array van exact 5 objecten met `question` (string), `options` (object met verplichte keys A, B, C, D), `skill` (enum van de 8 `ReadingSkill`-waarden), `answer` (object met `choice` (enum A-D), `paragraph` (integer 1-6), `evidence` (string, nullable))). Markdown-uitvoer parsen is NIET toegestaan.

#### Scenario: Uitvoer volgt het schema
- **WHEN** de agent een oefening genereert
- **THEN** is het resultaat een gestructureerd object dat exact het schema volgt en zonder parsen opgeslagen kan worden

### Requirement: Programmatische kwaliteitsvalidatie
Na elke AI-call MUST de job valideren: exact 6 alinea's; exact 5 vragen met elk 4 opties; `answer.choice` ∈ A-D; `answer.paragraph` ∈ 1-6; geen antwoordletter komt vaker dan 2 keer voor over de 5 vragen; minimaal 3 verschillende leesvaardigheden; en voor elke niet-null `evidence` dat deze — na normalisatie (lowercase, samengevoegde whitespace, genegeerde leestekens) — als substring voorkomt in de alinea die `answer.paragraph` aanwijst.

#### Scenario: Scheve antwoordverdeling
- **WHEN** de AI-uitvoer 3 van de 5 vragen antwoordletter "B" geeft
- **THEN** wordt de uitvoer afgekeurd

#### Scenario: Evidence niet in de genoemde alinea
- **WHEN** een vraag `evidence` bevat die na normalisatie niet voorkomt in de alinea van `answer.paragraph`
- **THEN** wordt de uitvoer afgekeurd

#### Scenario: Geldige uitvoer
- **WHEN** de uitvoer aan alle checks voldoet
- **THEN** wordt de uitvoer geaccepteerd en opgeslagen

### Requirement: Retry bij afgekeurde uitvoer
Bij afgekeurde uitvoer MUST de job een nieuwe AI-call doen, tot maximaal 3 pogingen in totaal. Slaagt geen enkele poging, dan MUST `failed_at` gezet worden en MUST geen gedeeltelijke inhoud opgeslagen zijn.

#### Scenario: Tweede poging slaagt
- **WHEN** de eerste AI-call afgekeurde uitvoer levert en de tweede geldige
- **THEN** wordt de inhoud van de tweede poging opgeslagen en `generated_at` gezet

#### Scenario: Alle pogingen falen
- **WHEN** drie opeenvolgende AI-calls afgekeurde uitvoer leveren
- **THEN** is `failed_at` gezet en zijn `title`, `paragraphs` en `questions` null

### Requirement: Opslag van het resultaat
Bij geaccepteerde uitvoer MUST de job `title`, `paragraphs` en `questions` op de oefening opslaan en `generated_at` zetten. Een eventueel eerder gezette `failed_at` MUST daarbij gewist zijn.

#### Scenario: Succesvolle generatie opgeslagen
- **WHEN** de job geldige uitvoer accepteert
- **THEN** bevat de oefening titel, 6 alinea's en 5 vragen en is `generated_at` gezet en `failed_at` null

### Requirement: Provider- en modelconfiguratie
De generatie MUST Anthropic als provider gebruiken met een Opus-model. Het model-id en de API-key MUST uit configuratie/environment komen (`config/ai.php`, `ANTHROPIC_API_KEY`), niet hardcoded in agent of job.

#### Scenario: Model uit configuratie
- **WHEN** de agent wordt aangeroepen
- **THEN** gebruikt hij het in de configuratie ingestelde Anthropic Opus-model
