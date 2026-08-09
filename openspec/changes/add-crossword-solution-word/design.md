## Context

De kruiswoordpuzzel uit `add-crossword-puzzles` is af: de AI levert kandidaatwoorden met aanwijzingen, een deterministisch algoritme in `app/Support/Crossword/` legt daar een rooster van, en dat rooster wordt geprint als werkblad plus antwoordblad.

Drie eigenschappen van die bestaande opzet maken een puzzelwoord goedkoop:

- `candidates` en `entries` staan los van elkaar opgeslagen. Van de 24 tot 48 gevalideerde kandidaten belanden er maar 10 tot 18 in het rooster, dus er is altijd een voorraad ongebruikte woorden die al uit de teksten komen, al genormaliseerd zijn naar A-Z en al een aanwijzing hebben.
- `Grid::entries()` levert bijgesneden coördinaten met een nummer per ingang, en `CrosswordPuzzle::cells()` bouwt daar een matrix van. Elk vakje is dus al adresseerbaar.
- Het rooster wordt gerenderd door één gedeelde Blade-partial, gebruikt door het werkblad, het antwoordblad en de detailpagina. Eén aanpassing daar landt op alle drie de plekken.

Randvoorwaarden blijven ongewijzigd: geen nieuwe dependencies, geen extra AI-call, forward-only migraties, Nederlandse UI via `lang/nl`, Pest 4.

## Goals / Non-Goals

**Goals:**

- Een puzzelwoord dat er altijd verzorgd uitziet: gespreide markeringen, leesbare nummers, ook op een zwart-witprinter.
- Nul extra AI-kosten. Het doelwoord komt uit wat er al ligt.
- Volledig deterministisch en database-loos te testen, net als de rest van `app/Support/Crossword/`.
- Geen enkele wijziging aan het plaatsingsalgoritme. Het puzzelwoord is nabewerking.

**Non-Goals:**

- Het rooster níet dwingen om een puzzelwoord mogelijk te maken. Dat zou de plaatser en de scorefunctie in gevecht brengen met een tweede doel.
- Geen tweede AI-call voor een thematisch woord dat niet in de teksten voorkomt.
- Geen handmatig gekozen puzzelwoord in het formulier. Het aantal formuliervelden blijft drie.
- Geen puzzelzin van meerdere woorden. Eén woord in één rij hokjes.
- Geen backfill van bestaande puzzels.

## Decisions

### Het puzzelwoord is een toewijzingsprobleem, geen roosterprobleem

De Nederlandse conventie is dat de gemarkeerde vakjes een klein volgnummer dragen en dat je die nummers in volgorde leest. De vakjes hoeven dus niet aan elkaar te grenzen en niet in leesrichting te liggen.

Daarmee vervalt de moeilijke variant. We hoeven geen letters op afgedwongen posities te krijgen; we hoeven alleen voor elk van de n letters van het doelwoord één apart vakje te vinden dat die letter al bevat. Dat is een matching op een af rooster.

*Alternatief overwogen*: het doelwoord tijdens het leggen als constraint meegeven, zodat de gemarkeerde vakjes bijvoorbeeld een diagonaal vormen. Verworpen: dat vecht met de scorefunctie, maakt de best-of-300-zoektocht veel smaller, en levert visueel weinig op omdat de nummers de leesvolgorde toch al bepalen.

### Het doelwoord komt uit de niet-geplaatste kandidaten

De kandidaten die het rooster niet haalden zijn precies wat we nodig hebben: gevalideerd, uit de gelezen teksten, met aanwijzing. De picker loopt ze in een vaste volgorde af en neemt het eerste woord dat te matchen is.

Die volgorde is: eerst filteren op de lengtegrenzen van het puzzelwoord voor de groep, dan langste eerst (een langer puzzelwoord is een grotere beloning), dan alfabetisch als tiebreak. Geen willekeur, dus geen seed nodig.

*Alternatief overwogen*: een aparte AI-call voor een thematisch woord. Verworpen: kosten en een extra faalpad, terwijl de voorraad al klaarligt en inhoudelijk net zo goed aansluit.

### Lengte van het puzzelwoord volgt de groep

Net als het aantal woorden en de woordlengte hoort dit in `PuzzleGroup`, zodat er één bron blijft en de gebruiker niets technisch hoeft in te vullen. De ladder loopt van 4 tot 5 letters voor groep 4 tot 6 tot 9 letters voor groep 8. Negen hokjes van 12mm is 108mm en past ruim binnen de 17cm die op A4 beschikbaar is.

### Spreiding is een cap op het aantal vakjes per ingang, die stapsgewijs versoepelt

Een botte greedy-matcher pakt makkelijk vijf letters uit hetzelfde lange woord. Dan ziet de markering eruit als een streep in plaats van als een spoor door de puzzel, en het kind hoeft maar één antwoord goed te hebben om het puzzelwoord al bijna te kennen.

De oplossing is geen apart spreidingsalgoritme maar één parameter op de matcher: **hooguit `maxPerEntry` gemarkeerde vakjes per ingang**. De picker probeert achtereenvolgens:

1. `maxPerEntry = 1`: elke letter uit een andere ingang. Dit is de gewenste uitkomst en lukt bijna altijd, want er staan 10 tot 18 ingangen tegenover hooguit 9 letters.
2. `maxPerEntry = 2`: nog steeds gespreid, maar een ingang mag twee letters leveren.
3. Geen cap: liever een lelijk puzzelwoord dan geen puzzelwoord.

Pas als alle drie de niveaus falen voor dit woord, gaat de picker door naar de volgende kandidaat. Zo is spreiding een harde eis zolang hij haalbaar is, en zakt hij daarna geleidelijk in plaats van in één keer weg te vallen.

*Alternatief overwogen*: spreiding als score achteraf over meerdere gevonden matchings. Verworpen: dat vraagt om alle matchings enumereren, terwijl een cap hetzelfde bereikt met één parameter.

### Kruisende vakjes tellen mee voor beide ingangen en worden vermeden

Een vakje waar twee woorden elkaar kruisen hoort bij twee ingangen. Dat maakt de telling per ingang dubbelzinnig en het is bovendien het visueel drukste vakje. De cellenvolgorde binnen een ingang zet zulke vakjes daarom achteraan, en als er toch een gekozen wordt telt hij mee voor beide ingangen.

### De matcher is backtracking, geen greedy

Greedy kan zichzelf klemzetten: het pakt het enige vakje met een E voor letter 1, waarna letter 4 geen E meer heeft. Bij hooguit negen letters is backtracking instant en ongeveer vijfentwintig regels.

Twee dingen maken het snel en deterministisch: de letters worden behandeld in volgorde van **zeldzaamste eerst** (de letter met de minste beschikbare vakjes), en de kandidaat-vakjes per letter staan in een vaste voorkeursvolgorde. Zonder willekeur is dezelfde invoer altijd dezelfde uitvoer.

### Het startvakje krijgt twee nummers in twee hoeken, maar liever geen

Een gemarkeerd vakje kan ook het genummerde startvakje van een ingang zijn. Dan zouden er twee nummers in één hokje van hooguit 12mm staan.

Twee lagen, in deze volgorde:

1. **Vermijden.** In de voorkeursvolgorde van vakjes binnen een ingang staan startvakjes achteraan. Met ongeveer 85 gevulde vakjes tegenover 10 tot 18 startvakjes is een startvakje bijna nooit nodig.
2. **Leesbaar opmaken als het toch moet.** Het ingangnummer blijft linksboven staan, want daar hangen de aanwijzingen aan; het volgnummer van het puzzelwoord komt rechtsonder. Twee hoeken, geen overlap. Het vakje is in de partial al `position: relative`, dus dat is een tweede absoluut gepositioneerde span.

Het ingangnummer wordt dus nooit weggelaten of verplaatst. Dat zou de puzzel onoplosbaar maken om een cosmetisch probleem op te lossen.

### De markering is een grijsvlak, geen kleur

Werkbladen worden thuis geprint, vaak op een zwart-witprinter. Een lichte grijsvulling plus een iets zwaardere rand blijft zichtbaar in monochroom en laat het ingevulde antwoord leesbaar. Kleur zou op de helft van de printers verdwijnen.

### Opslag in één nullable json-kolom

`solution` bevat het woord, de aanwijzing van dat woord en de geordende vakjes in bijgesneden coördinaten, in hetzelfde frame als `entries`:

```json
{"word": "OLIFANT", "clue": "...", "cells": [{"row": 3, "col": 5}, ...]}
```

De index in `cells` plus één is het volgnummer. `null` betekent: geen puzzelwoord, en dat is een geldige puzzel.

De aanwijzing wordt bewaard omdat we hem gratis hebben en omdat hij documenteert welke kandidaat gebruikt is. Hij verschijnt op de detailpagina en het antwoordblad, **niet** op het werkblad: het puzzelwoord hoort uit de letters te komen, niet uit een hint.

### Opnieuw leggen draait de picker gewoon opnieuw

Na een herroll kloppen de opgeslagen vakjes niet meer, want het rooster is anders. De relayout-actie draait daarom de picker opnieuw over het nieuwe rooster.

Er is geen speciale logica nodig om "hetzelfde woord" te behouden: de kandidatenlijst en de voorkeursvolgorde veranderen niet, dus hetzelfde woord komt vanzelf weer als eerste aan de beurt. Alleen als dat woord in het nieuwe rooster niet meer te matchen is, wint de volgende kandidaat. Dat is precies het gewenste gedrag, zonder er een regel voor te schrijven.

### De picker staat los van Laravel

`SolutionWordPicker` komt in `app/Support/Crossword/` en krijgt de geplaatste ingangen en de kandidaten binnen, en geeft woord plus vakjes terug. Geen database, geen queue, geen AI, dus testbaar in een Unit-test, net als `Grid`, `LayoutBuilder` en `LayoutScorer`.

## Risks / Trade-offs

**Geen enkele kandidaat is te matchen** → De puzzel krijgt geen puzzelwoord en blijft verder geldig. Met tientallen kandidaten en drie spreidingsniveaus per kandidaat is dat zeldzaam. De bestaande herrolknop geeft een nieuw rooster en dus een nieuwe kans.

**Alle kandidaten zitten in het rooster** → Dan is er geen voorraad om uit te kiezen. Alleen mogelijk als de AI structureel te weinig bruikbare woorden levert; de bewuste overgeneratie van 8 woorden per tekst tegenover 10 tot 18 plekken maakt het onwaarschijnlijk. Uitkomst is opnieuw: geen puzzelwoord, geen fout.

**Het rooster wordt visueel drukker** → De markering is een grijsvlak en de volgnummers staan in een andere hoek dan de ingangnummers. Op het werkblad zijn de gemarkeerde vakjes bovendien leeg, dus er staat maar één nummer in.

**Bestaande puzzels hebben geen puzzelwoord** → De kolom is nullable en er wordt niet gebackfilld. Wie een puzzelwoord wil op een oude puzzel, drukt op "opnieuw leggen"; dat is gratis en instant, en draait de picker alsnog.

**Het puzzelwoord verraadt zichzelf bij weinig spreiding** → Precies waarom `maxPerEntry = 1` het eerste niveau is en niet een nabewerking.
