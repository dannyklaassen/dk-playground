## Context

De kruiswoordpuzzel wordt gegenereerd door `CrosswordWordWriter`, een `laravel/ai`-agent met structured output. De prompt krijgt drie dingen mee: de tekst van één begrijpend-leesoefening, de `PuzzleGroup` (aantal woorden, woordlengte, woordsoorten) en de `ClueBand` (het type aanwijzing dat bij het level hoort). Beide enums leveren hun omschrijving via `getDescription()`, die uit `lang/nl/common.php` komt en zowel de helptekst in het formulier als de instructie aan de AI voedt.

In productie kwam een puzzel op level 1 in groep 6 te moeilijk uit. Uit de opgeslagen data blijkt waarom:

- Het level raakt de woordkeuze nergens, dus level 1 leverde TERRITORIUM, TELESCOPEN en WATERBAKKEN op.
- De banddescriptie voor 1-10 ("het sleutelwoord wordt bijna genoemd") en de agent-instructie ("noemt het woord zelf NIET, ook niet als deel van een samenstelling") sluiten elkaar uit. Het model loste dat op met "Mensen die voor de dieren zorgen; ze verzorgen ze." bij VERZORGERS, en negeerde de regel bij WATERBAKKEN met "Bakken waaruit de dieren water kunnen drinken."

Daarnaast kwam er gebruikersfeedback dat een invulzin beter werkt dan een losse omschrijving.

Het aanpalende `ExerciseWriter` en de begrijpend-leesteksten blijven expliciet buiten schot: die zijn qua niveau in orde bevonden.

## Goals / Non-Goals

**Goals:**

- Een puzzel op level 1 voelt als level 1, ook in een hogere groep.
- Elke aanwijzing is een invulzin, op elk level.
- Het kind weet altijd in welke tekst het antwoord staat.
- De twee tegenstrijdige regels in de prompt worden opgelost, zodat het model geen aanwijzingen meer produceert die om zichzelf heen draaien.

**Non-Goals:**

- De begrijpend-leesteksten en `ExerciseWriter` blijven ongewijzigd.
- Bestaande puzzels worden niet gemigreerd of herschreven.
- De plaatsingsalgoritmiek, het puzzelwoord en de printopmaak veranderen niet, op de bronvermelding na.
- Er komt geen programmatische validatie op de vorm van de invulzin (zie Risks).

## Decisions

### De woordkeuze krijgt een eigen omschrijving naast het type aanwijzing

`ClueBand` krijgt een tweede omschrijving, `getWordChoice()`, met eigen vertaalsleutels onder `common.clue_band_word_choice.*`. Die gaat alleen de prompt in, als apart kopje naast het type aanwijzing.

*Alternatief: de woordkeuze in de bestaande `getDescription()` vouwen.* Afgewezen omdat die string twee heel verschillende consumenten heeft. De helptekst onder het levelveld beschrijft aan een ouder wat het level doet; de woordkeuze is een imperatieve instructie aan een model ("kies concrete, alledaagse woorden"). Eén string die beide moet zijn, wordt in beide rollen slechter. De bestaande spec-eis dat helptekst en prompt uit één bron komen, blijft gelden voor het type aanwijzing.

*Alternatief: de woordkeuze uit het ruwe level afleiden in plaats van uit de band.* Afgewezen omdat er dan een tweede indeling naast de banden ontstaat die niets toevoegt: de banden zijn precies de trap die we willen.

### De regel over het noemen van het woord wordt per band genuanceerd

Het volledige woord blijft op elk level verboden. Alleen bij band 1-10 mag een deel van een samenstelling voorkomen. Dat is geen versoepeling maar een reparatie: "bijna noemen" en "geen woorddeel gebruiken" kunnen niet allebei waar zijn, en de band wint, want die is de didactische bedoeling van dat level.

Praktisch betekent dit dat de regel uit de vaste `instructions()` naar de per-puzzel prompt in `promptFor()` verhuist, want daar is de band bekend. `instructions()` houdt het verbod op het volledige woord.

### De invulzin komt in de vaste instructies, niet per band

De invulzin is een vormeis die voor alle 50 levels identiek is, dus die hoort in `instructions()` en niet in de banddescripties. De band bepaalt wat er in de zin staat, de instructie bepaalt dat het een zin met een open plek is. Zo blijven de vijf banddescripties ongewijzigd en is er één plek waar de vorm staat.

De maximale aanwijzingslengte gaat van twaalf naar zestien woorden. Twaalf woorden is krap voor een zin die eerst een situatie moet neerzetten en dan pas de open plek laat vallen; de aanwijzingen in de huidige data zitten al tegen die grens aan.

### De bronvermelding verliest zijn conditie

`CrosswordPuzzle::showsSource()` verdwijnt in plaats van `true` te gaan retourneren, en de `@if` in de worksheet-view houdt alleen de check of de titel bekend is. Een methode die altijd hetzelfde antwoord geeft, is een tak die niemand meer leest.

### Geen validatie op de vorm van de invulzin

`GenerateCrosswordPuzzle` valideert kandidaten streng op het woord (staat het in de tekst, lengte, A-Z) en gooit weg wat niet klopt. Voor de aanwijzing gebeurt dat bewust niet, en dat blijft zo. Een regex op een beletselteken zou een aanwijzing afkeuren die inhoudelijk prima is, en een aanwijzing die met een beletselteken eindigt maar nergens op slaat gewoon doorlaten. De vorm is een promptzaak.

## Risks / Trade-offs

- **Het model volgt de invulzin-vorm niet consequent** → Geen programmatische vangnet, dus dit wordt zichtbaar in de gegenereerde puzzels zelf. Bewuste keuze: de bestaande aanpak keurt woorden af maar corrigeert aanwijzingen niet, zodat een slecht presterende prompt zichtbaar blijft in plaats van weggepoetst te worden.
- **Bestaande puzzels hebben nog oude aanwijzingen** → Alleen "opnieuw genereren" levert invulzinnen op, en dat kost betaalde AI-calls. "Opnieuw leggen" raakt de aanwijzingen niet. Dit is geen bug maar het gevolg van de bestaande scheiding tussen kandidaten en plaatsing.
- **De invulzin verkleint het verschil tussen de banden** → Op de hoogste banden is een invulzin gekunstelder dan een open vraag. Dit is een expliciete keuze van de gebruiker: de uniforme vorm weegt zwaarder dan het karakter van de bovenste banden.
- **Een langere aanwijzing kost meer ruimte op het werkblad** → Zestien woorden plus een bronvermelding onder elke aanwijzing, nu op elk level, maakt pagina 2 voller. Bij veel woorden kan dat over de paginarand lopen. De aanwijzingenkolommen staan al op een eigen pagina, dus dit wordt pas een probleem bij groep 8; op dat moment is het een opmaakkwestie, geen promptkwestie.
