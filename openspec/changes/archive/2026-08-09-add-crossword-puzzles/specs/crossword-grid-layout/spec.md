## ADDED Requirements

### Requirement: Criss-cross-rooster zonder AI

Het rooster MUST gelegd worden door een deterministisch algoritme in PHP. Er MUST NOT een AI-model gebruikt worden om woorden op coördinaten te plaatsen. Het resultaat MUST een criss-cross-puzzel zijn: alleen de gekozen woorden staan in het rooster en de overige vakjes blijven leeg. Er MUST NOT een volledig gevuld rooster met zwarte vakjes gemaakt worden.

De logica MUST in klassen leven die geen database, queue of AI nodig hebben, zodat zij in een Unit-test te draaien zijn met alleen een lijst kandidaten en een seed als invoer.

#### Scenario: Rooster zonder AI

- **WHEN** het rooster gelegd wordt
- **THEN** gebeurt dat volledig in PHP en wordt er geen AI-aanroep gedaan

#### Scenario: Alleen de gekozen woorden

- **WHEN** een rooster gelegd is
- **THEN** bevat elk gevuld vakje een letter van een geplaatst woord en zijn alle overige vakjes leeg

### Requirement: Plaatsingsregels

Het eerste woord MUST horizontaal in het midden geplaatst worden. Elk volgend woord MUST op een kruising met een al geplaatst woord aanhaken, zodat het rooster altijd één samenhangend geheel is en losse eilandjes niet kunnen ontstaan. Een plaatsing MUST afgewezen worden wanneer een van deze drie regels geschonden wordt:

1. Een kruisende letter komt niet overeen met de letter die er al staat.
2. Het woord ligt parallel direct naast een ander woord, waardoor onbedoelde letterrijtjes ontstaan.
3. Het vakje direct voor of direct na het woord is gevuld in plaats van leeg of buiten het rooster.

Een woord dat nergens geldig geplaatst kan worden MUST overgeslagen worden.

#### Scenario: Kruisende letters moeten kloppen

- **WHEN** een woord op een positie geplaatst zou worden waar een kruisende letter niet overeenkomt
- **THEN** wordt die plaatsing afgewezen

#### Scenario: Geen parallelle woorden naast elkaar

- **WHEN** een woord direct naast en evenwijdig aan een al geplaatst woord zou komen te liggen
- **THEN** wordt die plaatsing afgewezen

#### Scenario: Woord raakt een ander woord aan de kop

- **WHEN** het vakje direct voor of na een woord al gevuld is
- **THEN** wordt die plaatsing afgewezen

#### Scenario: Rooster is samenhangend

- **WHEN** een rooster met meer dan één woord gelegd is
- **THEN** heeft elk woord behalve het eerste minimaal één kruising met een ander woord

#### Scenario: Onplaatsbaar woord

- **WHEN** een kandidaat op geen enkele positie geldig past
- **THEN** wordt hij niet geplaatst en gaat het algoritme door met de volgende kandidaat

### Requirement: Best-of-N met een opgeslagen seed

Het algoritme MUST 300 pogingen doen met een geschudde woordvolgorde en het rooster met de hoogste score kiezen. De gebruikte seed MUST op de puzzel opgeslagen worden, zodat dezelfde kandidaten met dezelfde seed altijd hetzelfde rooster opleveren.

#### Scenario: Reproduceerbaar resultaat

- **WHEN** het algoritme twee keer draait met dezelfde kandidaten en dezelfde seed
- **THEN** is het resulterende rooster in beide gevallen identiek

#### Scenario: Andere seed geeft een ander rooster

- **WHEN** het algoritme draait met dezelfde kandidaten maar een andere seed
- **THEN** is het resulterende rooster over het algemeen anders

### Requirement: Scorefunctie

Elk kandidaat-rooster MUST gescoord worden met deze formule, waarbij een dubbele kruising een woord is dat twee of meer andere woorden kruist:

```
score =  15 x aantal dubbele kruisingen
       + 10 x aantal kruisingen
       +  5 x aantal geplaatste woorden
       -  2 x (breedte + hoogte)
       -  1 x abs(breedte - hoogte)
       - 25 x aantal teksten zonder woord in de puzzel
```

De laatste term MUST het quotum per tekst afdwingen: elke geselecteerde begrijpend-leesoefening hoort minstens één woord in de puzzel te hebben. Dit MUST een strafpunt in de score zijn en MUST NOT een harde eis in de plaatser zijn, zodat een onvervulbaar quotum tot een lagere score leidt in plaats van tot een vastloper. Er MUST NOT een terugvalronde met een lager doelaantal gebouwd worden.

#### Scenario: Dicht geweven rooster wint

- **WHEN** twee kandidaat-roosters evenveel woorden bevatten maar het ene meer dubbele kruisingen heeft
- **THEN** wint het rooster met meer dubbele kruisingen

#### Scenario: Compact rooster wint

- **WHEN** twee kandidaat-roosters dezelfde kruisingen hebben maar het ene groter uitvalt
- **THEN** wint het compactere rooster

#### Scenario: Tekst zonder woord wordt bestraft

- **WHEN** een kandidaat-rooster geen enkel woord uit een van de geselecteerde teksten bevat
- **THEN** krijgt dat rooster 25 strafpunten voor die tekst

#### Scenario: Quotum blokkeert niet

- **WHEN** het quotum voor een tekst niet te vervullen is
- **THEN** levert het algoritme alsnog het best scorende rooster op in plaats van te falen

### Requirement: Nummering en roosterafmetingen

Na plaatsing MUST het rooster genummerd worden volgens de gebruikelijke kruiswoordconventie: de vakjes worden van linksboven naar rechtsonder doorlopen en een vakje krijgt het volgende nummer wanneer daar een woord begint. Een horizontaal en een verticaal woord die op hetzelfde vakje beginnen MUST hetzelfde nummer delen. Het rooster MUST bijgesneden worden tot de kleinste rechthoek die alle geplaatste letters omvat, en de resulterende breedte en hoogte MUST opgeslagen worden.

Per geplaatst woord MUST opgeslagen worden: het woord, de aanwijzing, de bron-oefening, de rij, de kolom, de richting en het nummer.

#### Scenario: Nummering van linksboven

- **WHEN** een rooster genummerd wordt
- **THEN** krijgt het meest linksboven beginnende woord nummer 1 en lopen de nummers op van links naar rechts en van boven naar beneden

#### Scenario: Gedeeld nummer

- **WHEN** een horizontaal en een verticaal woord op hetzelfde vakje beginnen
- **THEN** hebben beide hetzelfde nummer

#### Scenario: Rooster wordt bijgesneden

- **WHEN** de plaatsing klaar is
- **THEN** bevatten de opgeslagen afmetingen geen lege randrijen of randkolommen
