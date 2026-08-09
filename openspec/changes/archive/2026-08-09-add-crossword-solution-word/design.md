## Context

De kruiswoordpuzzel uit `add-crossword-puzzles` is af en gearchiveerd: de AI levert kandidaatwoorden met aanwijzingen, een deterministisch algoritme in `app/Support/Crossword/` legt daar een rooster van, en dat rooster wordt geprint als werkblad plus antwoordblad. De specs daarvan staan in `openspec/specs/crossword-puzzle-management`, `crossword-grid-layout` en `crossword-word-generation`.

Vier eigenschappen van die bestaande opzet maken een puzzelwoord goedkoop:

- `candidates` en `entries` staan los van elkaar opgeslagen. Van de gevalideerde kandidaten belanden er maar 10 tot 18 in het rooster, dus er is een voorraad ongebruikte woorden die al uit de teksten komen, al genormaliseerd zijn naar A-Z en al een aanwijzing hebben.
- `Grid::entries()` levert bijgesneden coördinaten met een nummer per ingang, en `CrosswordPuzzle::cells()` bouwt daar een matrix van. Elk vakje is dus al adresseerbaar.
- Het rooster wordt gerenderd door één gedeelde Blade-partial, gebruikt door het werkblad, het antwoordblad en de detailpagina. Eén aanpassing daar landt op alle drie de plekken.
- `CrosswordPuzzle::relayout()` is de enige plek waar een rooster ontstaat. De generatie-job, "opnieuw leggen" en "opnieuw genereren" lopen er alle drie doorheen.

Randvoorwaarden blijven ongewijzigd: geen nieuwe dependencies, geen extra AI-call, forward-only migraties, Nederlandse UI via `lang/nl`, Pest 4.

### Meting vooraf

De beslissingen hieronder over lengte en spreiding rusten op een meting over de drie bestaande gegenereerde puzzels. Per puzzel is de restvoorraad bepaald en is voor elk restwoord van hoogstens 8 letters geprobeerd of het te matchen is met hooguit 1 vakje per ingang, met hooguit 2, en zonder bovengrens:

| groep | ingangen | vakjes | kandidaten | rest | ≤8 letters | cap 1 | cap 2 | geen cap | nooit |
|-------|----------|--------|------------|------|------------|-------|-------|----------|-------|
| 6     | 14       | 97     | 34         | 20   | 12         | 8     | 2     | 0        | 2     |
| 7     | 16       | 112    | 46         | 30   | 14         | 10    | 0     | 0        | 4     |
| 6     | 14       | 105    | 23         | 9    | 5          | 3     | 0     | 0        | 2     |

In alle drie de gevallen levert "langste eerst, hooguit 8 letters" een woord van precies 8 letters op dat op het strengste spreidingsniveau past.

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

De kandidaten die het rooster niet haalden zijn precies wat we nodig hebben: gevalideerd, uit de gelezen teksten, met aanwijzing. De picker loopt ze in een vaste volgorde af: langste eerst (een langer puzzelwoord is een grotere beloning), alfabetisch als tiebreak. Geen willekeur, dus geen seed nodig.

*Alternatief overwogen*: een aparte AI-call voor een thematisch woord. Verworpen: kosten en een extra faalpad, terwijl de voorraad al klaarligt en inhoudelijk net zo goed aansluit.

### Eén bovengrens van 8 letters, geen ladder per groep

De eerdere opzet gaf `PuzzleGroup` een eigen lengteband per groep, oplopend van 4-5 voor groep 4 tot 6-9 voor groep 8. De meting laat zien dat die ladder geen probleem oplost en er één toevoegt: de restvoorraad hangt aan het aantal teksten (3 tot 6) en niet aan de groep, want een grotere groep plaatst weliswaar meer woorden maar krijgt ook een groter rooster om in te matchen. Een ondergrens per groep zou de dunste voorraad juist verder uitkleden.

Wat overblijft is één regel in de picker: **hoogstens 8 letters**. De ondergrens van 4 komt al gratis uit `CandidateValidator`, en 8 hokjes van 12mm is 96mm, ruim binnen de 170mm die op A4 beschikbaar is. `PuzzleGroup` wordt niet aangeraakt.

### Spreiding is een eerste doorgang over alle kandidaten, niet een ladder per kandidaat

Een botte greedy-matcher pakt makkelijk vijf letters uit hetzelfde lange woord. Dan ziet de markering eruit als een streep in plaats van als een spoor door de puzzel, en het kind hoeft maar één antwoord goed te hebben om het puzzelwoord al bijna te kennen.

De spreiding is één parameter op de matcher: **hooguit `maxPerEntry` gemarkeerde vakjes per ingang**. De vraag is alleen in welke volgorde je hem laat zakken. De eerdere opzet escaleerde per kandidaat (1, dan 2, dan geen cap, dan pas het volgende woord). De meting laat zien dat dat de verkeerde volgorde is: in elke puzzel passen 3 tot 10 woorden op `maxPerEntry = 1`, en `geen cap` was nooit nodig. Een woord geclusterd accepteren terwijl er verderop in de lijst een woord ligt dat wél gespreid past, is een slechtere uitkomst.

Dus twee doorgangen over de hele kandidatenlijst:

1. `maxPerEntry = 1`: elke letter uit een andere ingang. Dit is de gewenste uitkomst en lukt vrijwel altijd.
2. Geen bovengrens: liever een lelijk puzzelwoord dan geen puzzelwoord.

Het tussenniveau `maxPerEntry = 2` vervalt. Het hielp in de meting alleen bij woorden waarvoor in dezelfde puzzel al acht andere woorden op niveau 1 pasten, dus het zou nooit aan bod komen.

*Alternatief overwogen*: spreiding als score achteraf over meerdere gevonden matchings. Verworpen: dat vraagt om alle matchings enumereren, terwijl een cap hetzelfde bereikt met één parameter.

### De voorkeursvolgorde van vakjes is één strafscore

Een vakje waar twee woorden elkaar kruisen hoort bij twee ingangen. Dat maakt de telling per ingang dubbelzinnig en het is bovendien het visueel drukste vakje. Een startvakje draagt al een nummer. Beide willen we achteraan, en een vakje kan allebei zijn.

Drie losse klassen laten dat dubbelgeval in het midden; één opgetelde strafscore niet:

```
voorkeur(vakje) = [ is_kruising + is_startvakje ,  rij ,  kolom ]
                    0 = gewoon vakje
                    1 = kruising óf startvakje
                    2 = allebei
```

Rij en kolom zijn de vaste tiebreak, zodat de volgorde deterministisch is. Een gekozen kruisend vakje telt mee in de bovengrens van beide ingangen.

### De matcher is backtracking, geen greedy

Greedy kan zichzelf klemzetten: het pakt het enige vakje met een E voor letter 1, waarna letter 4 geen E meer heeft. Bij hooguit acht letters is backtracking instant en ongeveer vijfentwintig regels.

Twee dingen maken het snel en deterministisch: de letters worden behandeld in volgorde van **zeldzaamste eerst** (de letter met de minste beschikbare vakjes), en de kandidaat-vakjes per letter staan in de voorkeursvolgorde hierboven. Zonder willekeur is dezelfde invoer altijd dezelfde uitvoer.

### Het startvakje krijgt twee nummers in twee hoeken, maar liever geen

Een gemarkeerd vakje kan ook het genummerde startvakje van een ingang zijn. Dan zouden er twee nummers in één hokje van hooguit 12mm staan.

Twee lagen, in deze volgorde:

1. **Vermijden.** Startvakjes krijgen een strafpunt in de voorkeursvolgorde. Met ongeveer 100 gevulde vakjes tegenover 10 tot 18 startvakjes is een startvakje bijna nooit nodig.
2. **Leesbaar opmaken als het toch moet.** Het ingangnummer blijft linksboven staan, want daar hangen de aanwijzingen aan; het volgnummer van het puzzelwoord komt rechtsonder. Twee hoeken, geen overlap. Het vakje is in de partial al `position: relative`, dus dat is een tweede absoluut gepositioneerde span.

Het ingangnummer wordt dus nooit weggelaten of verplaatst. Dat zou de puzzel onoplosbaar maken om een cosmetisch probleem op te lossen.

### De markering is een grijsvlak, geen kleur, en de rasterlijnen worden 2px

Werkbladen worden thuis geprint, vaak op een zwart-witprinter. Een lichte grijsvulling blijft zichtbaar in monochroom en laat het ingevulde antwoord leesbaar. Kleur zou op de helft van de printers verdwijnen.

Tegelijk gaan de rasterlijnen van 1px naar 2px, wat de puzzel steviger laat ogen. Dat maakt ook de markering eenvoudiger: de grijsvulling hoeft niet meer met een zwaardere rand ondersteund te worden, want elke cel heeft die rand nu. Eén verschil per gemarkeerd vakje, niet twee.

### Opslag in één nullable json-kolom `solution_word`

De kolom heet `solution_word` en niet `solution`, want de infolist gebruikt `solution` al als naam voor het ingevulde rooster in de Antwoordblad-sectie. Twee betekenissen voor hetzelfde woord in hetzelfde model is vragen om verwarring.

De kolom bevat het woord, de aanwijzing van dat woord en de geordende vakjes in bijgesneden coördinaten, in hetzelfde frame als `entries`:

```json
{"word": "OLIFANT", "clue": "...", "cells": [{"row": 3, "col": 5}, ...]}
```

De index in `cells` plus één is het volgnummer. `null` betekent: geen puzzelwoord, en dat is een geldige puzzel.

De aanwijzing wordt bewaard omdat we hem gratis hebben en omdat hij documenteert welke kandidaat gebruikt is. Hij verschijnt bij de Kenmerken op de detailpagina, **niet** op het werkblad: het puzzelwoord hoort uit de letters te komen, niet uit een hint.

`solution_word` staat niet in `#[Fillable]`. Het model laat daar alleen de drie formuliervelden toe; alles wat de generatie oplevert gaat via `forceFill()`.

### Eén aanroepplek: `CrosswordPuzzle::relayout()`

De picker wordt niet in de job en in de actie apart bedraad. `relayout()` is de trechter waar alle drie de paden doorheen gaan, en het is ook de plek waar `entries` ontstaat:

```
GenerateCrosswordPuzzle    ─┐
RelayoutCrosswordPuzzle    ─┼─►  CrosswordPuzzle::relayout()
RegenerateCrosswordPuzzle  ─┘         └─ forceFill(entries, seed, grid_rows, grid_cols, solution_word)
```

Dat levert drie dingen gratis op. Het puzzelwoord wordt in dezelfde `forceFill()` geschreven, dus geen tweede save. De bestaande vroege `return false` bij een te magere herroll blijft gelden, dus een mislukte herroll laat het oude puzzelwoord bij het oude rooster staan. En bestaande puzzels zonder puzzelwoord krijgen er via de herrolknop alsnog een, zonder backfill-migratie.

### Het puzzelwoord mag wisselen bij opnieuw leggen

Na een herroll kloppen de opgeslagen vakjes niet meer, want het rooster is anders. De picker draait daarom opnieuw over het nieuwe rooster.

Het eerder gekozen woord komt daarbij niet gegarandeerd terug. De kandidatenlijst en de voorkeursvolgorde veranderen niet, maar de **restvoorraad** wel: een woord dat in het nieuwe rooster wél geplaatst is, valt uit de voorraad en maakt plaats voor de volgende kandidaat. Dat is geaccepteerd gedrag en geen bug.

*Alternatief overwogen*: het huidige puzzelwoord actief uit de plaatsing houden zodat het altijd in de voorraad blijft. Verworpen: dat is een tweede constraint op de plaatser om een eigenschap te bewaren die niemand mist, en de gebruiker die op "opnieuw leggen" drukt vraagt juist om iets anders.

### De rij invulhokjes heeft een vaste maat

Het rooster schaalt zijn cellen omlaag bij veel kolommen via `cellSizeMm()`. De rij invulhokjes doet dat niet mee: hij telt hoogstens 8 hokjes en heeft de ruimte niet nodig. Vaste 12mm, dus hoogstens 96mm breed. Meeschalen zou de hokjes juist klein maken op de brede puzzels, precies waar het kind de meeste letters moet overschrijven.

### De picker staat los van Laravel

`SolutionWordPicker` komt in `app/Support/Crossword/` en krijgt de geplaatste ingangen en de kandidaten binnen, en geeft woord plus vakjes terug. Geen database, geen queue, geen AI, dus testbaar in een Unit-test, net als `Grid`, `LayoutBuilder` en `LayoutScorer`.

## Risks / Trade-offs

**Geen enkele kandidaat is te matchen** → De puzzel krijgt geen puzzelwoord en blijft verder geldig. In de meting bleven er per puzzel 3 tot 10 matchbare woorden over. De bestaande herrolknop geeft een nieuw rooster en dus een nieuwe kans.

**Weinig teksten geeft een dunne voorraad** → Bij het minimum van 3 teksten hield de gemeten puzzel 9 restwoorden over, waarvan 5 van hoogstens 8 letters en 3 matchbaar. Dat is krap maar voldoende. Uitkomst bij nog magerder opbrengst is opnieuw: geen puzzelwoord, geen fout.

**Het rooster wordt visueel drukker** → De markering is een grijsvlak en de volgnummers staan in een andere hoek dan de ingangnummers. Op het werkblad zijn de gemarkeerde vakjes bovendien leeg, dus er staat maar één nummer in.

**Bestaande puzzels hebben geen puzzelwoord** → De kolom is nullable en er wordt niet gebackfilld. Wie een puzzelwoord wil op een oude puzzel, drukt op "opnieuw leggen"; dat is gratis en instant, en draait de picker alsnog.

**De huidige factory-fixture kan geen puzzelwoord dragen** → Het handgelegde rooster telt 3 ingangen en 13 vakjes, en de enige niet-geplaatste kandidaat (WOLKEN) heeft letters die nergens in het rooster staan. Met 3 ingangen is `maxPerEntry = 1` bovendien goed voor hooguit 3 letters. De fixture krijgt daarom een groter rooster en een vaste groep, zodat de tests niet per run van band wisselen.
