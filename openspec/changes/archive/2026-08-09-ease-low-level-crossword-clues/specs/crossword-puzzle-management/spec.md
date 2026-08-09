## MODIFIED Requirements

### Requirement: Printbaar werkblad
Er MUST een route zijn die een printbaar werkblad voor een gegenereerde puzzel oplevert als HTML met een printstylesheet (A4, marge 2cm). Het werkblad MUST één document van twee pagina's zijn: pagina 1 bevat uitsluitend het lege rooster met de genummerde startvakjes, gevolgd door een pagina-einde, en pagina 2 bevat de aanwijzingen in twee kolommen onder de koppen "Horizontaal" en "Verticaal". Bij elke aanwijzing MUST de bron-oefening vermeld worden, ongeacht het level, zodat het kind altijd weet in welke tekst het antwoord te vinden is. De hokjesgrootte van het rooster MUST berekend worden uit de roosterafmeting en de beschikbare breedte, met een bovengrens van ongeveer 12mm. Er MUST NOT een PDF-library gebruikt worden. Voor een puzzel die nog niet gegenereerd is MUST de route een 404 geven.

De twee kolommen MUST vaste kolommen zijn, met de horizontale aanwijzingen links en de verticale rechts. Zij MUST NOT als doorlopende tekstkolommen opgemaakt worden, want dan begint "Verticaal" in de kolom waar "Horizontaal" toevallig ophield.

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

#### Scenario: Bronvermelding bij laag level
- **WHEN** het werkblad van een puzzel met level 8 wordt geopend
- **THEN** staat ook daar bij elke aanwijzing vermeld uit welke oefening het woord komt

#### Scenario: Horizontaal en verticaal in een eigen kolom
- **WHEN** de puzzel meer horizontale dan verticale aanwijzingen heeft
- **THEN** staat "Verticaal" nog steeds bovenaan de rechterkolom en niet halverwege de linkerkolom

#### Scenario: Werkblad van een niet-gegenereerde puzzel
- **WHEN** het werkblad wordt opgevraagd voor een puzzel waarvan `generated_at` null is
- **THEN** geeft de route een 404
