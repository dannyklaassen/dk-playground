## 1. Fundament

- [x] 1.1 Installeer `laravel/ai`, publiceer `config/ai.php`, configureer Anthropic (Opus-model-id) en zet `ANTHROPIC_API_KEY` in `.env.example`
- [x] 1.2 Maak enums `ReadingLevel`, `ReadingSkill` (met `getDescription()`) en `LevelBand` (met `forLevel()`), inclusief NL-labels/omschrijvingen in `lang/nl/common.php`
- [x] 1.3 Maak `ComprehensionExercise` model + migration (ULID, kolomvolgorde conform conventie, json-casts, status-accessor uit `generated_at`/`failed_at`) + factory met states `generated` en `failed`

## 2. AI-generatie

- [x] 2.1 Maak `ExerciseWriter` agent-class (`Agent` + `HasStructuredOutput`): didactische spec als instructies, JSON-schema, user-bericht met de vier invoervelden + `LevelBand`-omschrijving
- [x] 2.2 Maak `GenerateComprehensionExercise` job: synchroon agent-call, programmatische validatie (aantallen, A-D-verdeling max 2×, ≥3 skills, paragraph 1-6, genormaliseerde evidence-substring-check), max 3 pogingen, opslag + `generated_at`, `failed()`-hook zet `failed_at`, timeout ≥300s
- [x] 2.3 Pest-tests voor de job met gemockte agent-responses: succes, afgekeurd→retry→succes, alles afgekeurd→`failed_at`, evidence-check, verdeling-check

## 3. Filament-resource

- [x] 3.1 Genereer `ComprehensionExerciseResource` (List + View, `--not-embedded`, geen Create/Edit-pagina's) + `ComprehensionExercisePolicy` (volledige ability-set)
- [x] 3.2 Formulier in slide-over via `CreateAction` op de List-pagina: leesniveau-select, onderwerp, omschrijving, level (1-50) met live `LevelBand`-helptekst; dispatcht de job na aanmaken
- [x] 3.3 Tabel: onderwerp, leesniveau, level, status-badge (bezig/klaar/mislukt), aanmaakdatum; polling zolang er "bezig"-records zijn; rij-acties (Delete + Opnieuw genereren bij mislukt) in kebab-`ActionGroup`
- [x] 3.4 View-pagina infolist: sectie "Algemeen" (invoergegevens), tekst met genummerde alinea's, vragen met A-D, antwoordblad-sectie; status-melding bij bezig/mislukt; header-acties (kebab) + werkblad/antwoordblad-acties
- [x] 3.5 "Opnieuw genereren"-actie als eigen action-class: wist `failed_at`, dispatcht de job opnieuw
- [x] 3.6 NL-vertalingen in `lang/nl/admin.php` (`comprehension_exercise.*`) voor alle labels, kolommen, acties, secties en notificaties

## 4. Printviews

- [x] 4.1 Auth-beveiligde routes + controller voor werkblad en antwoordblad (404 zonder `generated_at`)
- [x] 4.2 Werkblad-Blade-view met print-CSS: titel, 6 genummerde alinea's, 5 vragen met A-D en invulrondjes — zonder antwoorden
- [x] 4.3 Antwoordblad-Blade-view met print-CSS: tabel (vraag, goed antwoord, alinea, leesvaardigheid) + bewijszin per vraag waar aanwezig

## 5. Tests & afronding

- [x] 5.1 Pest-feature-tests voor de resource: aanmaken (record + job dispatched, `Queue::fake()`), validatie, lijst met statussen, view-pagina, verwijderen, opnieuw genereren
- [x] 5.2 Pest-feature-tests voor de printroutes: werkblad zonder antwoorden, antwoordblad met tabel, 404 bij niet-gegenereerd, auth vereist
- [x] 5.3 Policy-test + controle arch-tests (resource↔policy-reflectietest dekt de nieuwe resource)
- [x] 5.4 `composer run test` groen (rector, pint, volledige suite)
