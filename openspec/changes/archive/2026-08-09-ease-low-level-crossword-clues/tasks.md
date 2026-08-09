## 1. Woordkeuze per band

- [x] 1.1 Voeg `clue_band_word_choice` toe aan `lang/nl/common.php` met een omschrijving per band, van concrete alledaagse woorden bij 1-10 tot abstracte en vaktalige woorden bij 41-50
- [x] 1.2 Voeg `getWordChoice()` toe aan `App\Enums\ClueBand`, naar hetzelfde patroon als `getDescription()`
- [x] 1.3 Breid `tests/Unit/ClueBandTest.php` uit met een assertie dat elke band een niet-lege woordkeuze-omschrijving heeft en dat 1-10 en 41-50 van elkaar verschillen

## 2. Prompt van de aanwijzingenagent

- [x] 2.1 Verhoog de maximale aanwijzingslengte in `CrosswordWordWriter::instructions()` van twaalf naar zestien woorden en leg die vast als constante naast `WORDS_PER_EXERCISE`
- [x] 2.2 Herschrijf het blok "Aanwijzingen" in `instructions()` zodat elke aanwijzing een invulzin is die op een beletselteken eindigt, grammaticaal klopt met het woord ingevuld, en het volledige woord nooit bevat
- [x] 2.3 Haal het verbod op een woorddeel uit `instructions()` weg, want dat is bandafhankelijk
- [x] 2.4 Voeg in `CrosswordWordWriter::promptFor()` de woordkeuze van de band toe als eigen kopje naast het type aanwijzing
- [x] 2.5 Voeg in `promptFor()` de bandafhankelijke regel over het woorddeel toe: bij band 1-10 toegestaan, vanaf band 11-20 te vermijden
- [x] 2.6 Werk of maak de test op `promptFor()` bij zodat hij voor level 1 en level 45 aantoont dat de woordkeuze meekomt en dat de woorddeel-regel per band verschilt

## 3. Bronvermelding altijd op het werkblad

- [x] 3.1 Verwijder `showsSource()` uit `app/Models/CrosswordPuzzle.php`
- [x] 3.2 Haal de conditie op het level uit `resources/views/crossword-puzzles/worksheet.blade.php`, zodat alleen de check op een bekende titel overblijft
- [x] 3.3 Vervang in `tests/Feature/CrosswordPuzzlePrintTest.php` de test "leaves the source text off the worksheet below level 21" door een test die aantoont dat de bron ook op een laag level vermeld wordt, en hernoem de test voor level 21 zodat die niet meer over een drempel spreekt

## 4. Vaste kolommen voor de aanwijzingen

- [x] 4.1 Controleer dat `.clues` in `resources/views/crossword-puzzles/partials/print-styles.blade.php` een grid met twee vaste kolommen is in plaats van doorlopende tekstkolommen
- [x] 4.2 Voeg een test toe die aantoont dat "Verticaal" een eigen kolom krijgt bij een ongelijk aantal horizontale en verticale aanwijzingen

## 5. Afronden

- [x] 5.1 Draai `vendor/bin/pint --dirty --format agent`
- [x] 5.2 Draai de kruiswoord-tests: `php artisan test --compact --filter=Crossword` plus `tests/Unit/ClueBandTest.php`
- [x] 5.3 Genereer handmatig een puzzel op level 1 in groep 6 en controleer of de aanwijzingen invulzinnen zijn en de woorden alledaags

## 6. Variatie in zinsbouw (na gebruikersfeedback)

- [x] 6.1 Voeg in `CrosswordWordWriter::instructions()` een variatieregel toe die de naamwoordelijke constructie "heet/heten/is een" op hooguit drie van de acht aanwijzingen houdt
- [x] 6.2 Maak de positie van de open plek bandafhankelijk via `ClueBand::requiresGapAtEnd()`: aan het eind tot en met band 11-20, vanaf band 21-30 minstens twee keer middenin
- [x] 6.3 Werk de spec-requirement "Aanwijzingen zijn invulzinnen" bij, want die eiste de open plek altijd aan het eind, en voeg de requirement "Aanwijzingen variëren in zinsbouw" toe
- [x] 6.4 Haal het woord "omschrijving"/"definitie" uit de bandomschrijvingen van band 1-10 en 11-20 in `lang/nl/common.php`, want dat schreef de definitievorm voor en sprak de variatie-eis tegen
- [x] 6.5 Scherp de eenduidigheidsregel aan, want de variatie leverde verhalende zinnen op die op meerdere woorden pasten
