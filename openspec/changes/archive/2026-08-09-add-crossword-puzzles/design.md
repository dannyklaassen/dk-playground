## Context

De app bevat begrijpend-leesoefeningen: een AI-gegenereerde tekst van 6 alinea's met 5 meerkeuzevragen, aangemaakt in een Filament-panel en uit te printen als werkblad plus antwoordblad. Alle 17 bestaande oefeningen staan op leesniveau eind groep 6 met levels 1 tot 28.

Een kruiswoordpuzzel bouwt hierop voort: het kind heeft 3 tot 6 teksten gelezen, en de puzzel laat de kernwoorden daaruit terugkomen. Dat betekent dat de puzzel géén eigen inhoud verzint maar volledig teruggrijpt op bestaande records.

Randvoorwaarden vanuit het project:

- Geen nieuwe dependencies. De bestaande printoplossing is een Blade-view met een printstylesheet, waar de gebruiker zelf naar PDF print.
- Statuspatroon met nullable `generated_at` / `failed_at` en een afgeleide status, geen statuskolom.
- Nederlandse UI via `lang/nl`, Engelse identifiers, forward-only migraties, Pest 4.

## Goals / Non-Goals

**Goals:**

- Een printbare criss-cross-puzzel op A4 die er altijd verzorgd uitziet, zonder handmatig nawerk.
- Een strikte scheiding tussen wat AI doet (taal) en wat een algoritme doet (rooster), zodat het roosterdeel deterministisch en zonder AI te testen is.
- Twee begrijpelijke niveauknoppen die didactisch te verantwoorden zijn en die de gebruiker niet dwingen technische parameters in te vullen.
- Opnieuw leggen moet gratis en instant zijn, zodat de gebruiker net zo lang kan doorklikken tot het rooster mooi ligt.

**Non-Goals:**

- Geen Amerikaanse kruiswoordpuzzel met een volledig gevuld rooster en zwarte vakjes. Dat vereist een woordenboek van honderdduizend woorden en is een ander product.
- Geen digitale invulmodus voor het kind. Het eindproduct is papier.
- Geen PDF-library. De gebruiker print zelf naar PDF.
- Geen kalibratiemechaniek, statistieken of moeilijkheidsfeedback. De aantallen staan in een enum en worden desnoods in de code bijgesteld.
- Geen aanpassing aan bestaande oefeningen, resources of routes.

## Decisions

### AI kiest de woorden, een algoritme legt het rooster

Een taalmodel kan niet betrouwbaar letters op coördinaten uitlijnen. Het levert roosters die er plausibel uitzien maar waarin kruisende letters niet kloppen, en het resultaat moet dan alsnog programmatisch geverifieerd worden, wat evenveel code kost als het zelf leggen. Plaatsen is bovendien deterministisch, gratis en in milliseconden te herhalen.

Daarom: AI levert uitsluitend woorden en aanwijzingen. Het rooster wordt gelegd door PHP.

*Alternatief overwogen*: de AI het volledige rooster laten teruggeven in het structured-output schema. Verworpen om bovenstaande redenen.

### Eén AI-call per tekst, niet één call voor alles

Per tekst een korte prompt met alleen die ene tekst geeft betere woordkeuze dan één prompt met zes teksten erin. De twee nadelen zijn allebei zonder AI op te lossen: dubbelen worden na afloop weggefilterd, en lengtespreiding wordt per tekst in de prompt afgedwongen (minimaal 2 woorden van 9 letters of langer, binnen de lengtegrens van de groep).

*Alternatief overwogen*: één call over alle teksten, zodat de AI zelf dubbelen kan vermijden. Verworpen: de winst is klein en programmatisch te halen, het verlies aan aandacht per tekst is groot.

### De calls draaien sequentieel binnen één job

De zes calls zijn technisch parallel uit te voeren, maar dat vraagt een job-batch met een afrondende callback. Zes calls van ongeveer 20 seconden achter elkaar is ongeveer 2 minuten, ruim binnen een verhoogde job-timeout, en het houdt de foutafhandeling in één klasse.

Bewust niet gebouwd: parallelle dispatch. Blijkt de wachttijd in de praktijk hinderlijk, dan is de stap naar een batch klein.

### Kandidaten en geplaatste ingangen worden apart opgeslagen

Dit is wat "opnieuw leggen" gratis maakt. Het record bewaart twee dingen:

- `candidates`: alle gevalideerde en ontdubbelde woorden met aanwijzing en bron-oefening, zoals ze uit de AI-fase komen.
- `entries`: de deelverzameling die daadwerkelijk geplaatst is, met rij, kolom, richting en nummer.

Opnieuw leggen draait de plaatser opnieuw over `candidates` met een nieuwe seed en herschrijft alleen `entries`. Er is geen AI-call nodig. Zou alleen de geplaatste set bewaard worden, dan kon een herroll nooit andere woorden kiezen en was de knop zinloos.

### Een pivot-tabel voor de gekozen teksten, geen json-kolom

`entries` verwijst per woord al naar een bron-oefening, maar dat is niet genoeg: een geselecteerde tekst kan eindigen met nul geplaatste woorden en zou dan uit beeld verdwijnen. De selectie zelf moet los vastgelegd worden.

Een pivot geeft bovendien de omgekeerde vraag ("in welke puzzels zit deze tekst?") gratis, en Filament's multi-select werkt direct op een `belongsToMany`. Volgens de projectconventie krijgt een zuivere pivot een auto-increment id, met `foreignUlid`-kolommen naar beide ULID-modellen.

### Criss-cross, en samenhang volgt uit de constructie

Elk woord wordt uitsluitend geplaatst op een kruising met een al geplaatst woord. Losse eilandjes kunnen daardoor niet ontstaan. Het echte kwaliteitsrisico is een boomstructuur: een slierterig rooster waarin elk woord precies één kruising heeft. Dat is een schoonheidsprobleem, geen correctheidsprobleem, en wordt door de scorefunctie aangepakt.

Drie harde plaatsingsregels:

1. Kruisende letters moeten gelijk zijn.
2. Een woord mag niet parallel direct naast een ander woord liggen, anders ontstaan onbedoelde letterrijtjes.
3. Voor en achter elk woord moet een leeg vakje of de rand zitten.

### Best-of-N met een scorefunctie, quotum als strafpunt

300 pogingen met geschudde woordvolgorde, hoogste score wint. Bij tien tot achttien woorden kost dat milliseconden.

```
score =  15 x aantal dubbele kruisingen
       + 10 x aantal kruisingen
       +  5 x aantal geplaatste woorden
       -  2 x (breedte + hoogte)
       -  1 x abs(breedte - hoogte)
       - 25 x aantal teksten zonder woord in de puzzel
```

Het zwaarste gewicht ligt op dubbele kruisingen, want dat is wat het verschil maakt tussen technisch correct en "ziet eruit als een echte kruiswoordpuzzel". De compactheidstermen duwen de boomstructuur weg. De laatste term is het quotum per tekst, bewust als strafpunt in de score en niet als harde eis in de plaatser: dat houdt de plaatser simpel en maakt een zeldzaam onvervulbaar quotum een lagere score in plaats van een vastloper.

Het quotum is minimaal één woord per tekst, niet twee, omdat groep 4 met 6 teksten tien woorden over zes teksten verdeelt.

*Bewust niet gebouwd*: een terugvalronde die het opnieuw probeert met minder woorden. De plaatser laat woorden die nergens passen al vallen, en de score weegt "meer woorden" al af tegen "compact". Valt het resultaat structureel te leeg uit, dan is het antwoord een lager compactheidsgewicht, niet een extra ronde.

### Twee niveau-assen met een gescheiden taak

Bij een oefening stuurt het leesniveau de tekst en het level de vraag. Bij een puzzel bestaan de teksten al, dus de assen krijgen een andere taak:

- **Groep 4 t/m 8** stuurt woordkeuze en omvang. Onderbouwing: ongeveer anderhalve minuut per woord houdt de opgave op één werksessie van 15 tot 30 minuten; de oplopende lengtegrens volgt schrijfvaardigheid, want een kind uit groep 4 raakt de tel kwijt bij zestien hokjes, en niet leesvaardigheid; achttien woorden is bovendien de fysieke A4-grens bij hokjes van 10mm.
- **Level 1 t/m 50** stuurt uitsluitend het type aanwijzing, in dezelfde reproductie-naar-inferentieladder als de bestaande `LevelBand`.

Begin, midden en eind zijn hier niet van toepassing. Bij het schrijven van een tekst is dat onderscheid betekenisvol, bij een aanwijzing van tien woorden is het schijnprecisie.

Aantal woorden en woordlengte zijn geen formuliervelden. Ze volgen volledig uit de groep-enum, zodat de gebruiker nooit een technische parameter hoeft in te vullen.

### Printen blijft Blade plus printstylesheet

Hetzelfde patroon als de bestaande werk- en antwoordbladen: een HTTP-route, een Blade-view, `@media print` met `@page { margin: 2cm }`. Geen PDF-library.

Het werkblad is één document van twee pagina's met een pagina-einde na het rooster. Het antwoordblad blijft een aparte route, zodat de oplossing nooit per ongeluk meegeprint wordt.

De hokjesgrootte wordt niet vastgezet maar in PHP berekend uit de roosterafmeting en afgetopt op ongeveer 12mm, doorgegeven als CSS-variabele. Bij 2cm marges is er 17cm breedte beschikbaar. Kleine puzzels krijgen zo vanzelf grote hokjes, wat prettiger schrijven is.

### Plaatsingslogica staat los van Laravel

Het algoritme komt in `app/Support/Crossword/` als database-loze klassen die een lijst kandidaten en een seed in nemen en een rooster teruggeven. Zo is het te testen in een Unit-test zonder database, zonder queue en zonder AI.

## Risks / Trade-offs

**De AI levert te weinig bruikbare woorden, waardoor het rooster leeg blijft** → Bewuste overgeneratie van 8 kandidaten per tekst tegenover 10 tot 18 plekken. De validatie keurt af en corrigeert niet, zodat een slecht presterende prompt zichtbaar wordt in plaats van stilletjes opgevangen.

**Nederlandse samenstellingen zijn lang** (`dierenziekenhuis` is 17 letters) → De maximale woordlengte per groep begrenst dit al. Het rooster groeit bovendien mee met het langste geplaatste woord, en de hokjesgrootte schaalt mee zodat het altijd op A4 past.

**Woorden uit verschillende teksten lijken op elkaar** (mug en muggen) → Ontdubbeling op exacte match plus een prefixregel: is het ene woord een prefix van het andere met hooguit drie letters verschil, dan valt het kortste af.

**Diakrieten en apostrofs breken het rooster** → Diakrieten worden genormaliseerd naar hun basisletter, woorden met een apostrof of spatie worden afgekeurd. Het rooster kent alleen A t/m Z.

**Puzzel blijft slierterig ondanks de score** → De herrolknop is de menselijke ontsnapping: nieuwe seed, instant, gratis. Dat is goedkoper dan blijven sleutelen aan scoregewichten.

**Groep en tekstniveau kunnen botsen** (zes teksten van eind groep 6 met groep 4 als puzzelniveau) → Bewust niet geblokkeerd. Dezelfde onderwerpen op een makkelijker niveau voor een jonger kind is een reëel gebruik. De aanwijzing wordt makkelijker, het woord blijft wat het is.

**Veel teksten bij een lage groep geeft dunne dekking** (tien woorden over zes teksten) → Geaccepteerd. Het strafpunt in de score zorgt dat elke tekst minstens één woord krijgt; verder merkt de gebruiker dit vanzelf.
