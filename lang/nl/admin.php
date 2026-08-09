<?php

declare(strict_types=1);

return [
    'comprehension_exercise' => [
        'singular' => 'tekst',
        'plural' => 'teksten',
        'navigation' => 'Begrijpend lezen',
        'fields' => [
            'question' => 'Vraag',
            'reading_level' => 'Leesniveau',
            'topic' => 'Onderwerp',
            'description' => 'Omschrijving',
            'level' => 'Moeilijkheidsgraad',
            'paragraphs' => 'Tekst',
            'answer' => 'Antwoord',
            'paragraph' => 'Alinea',
            'skill' => 'Leesvaardigheid',
            'evidence' => 'Bewijszin',
        ],
        'columns' => [
            'status' => 'Status',
            'created_at' => 'Aangemaakt op',
        ],
        'sections' => [
            'details' => 'Kenmerken',
            'status' => 'Status',
            'text' => 'Tekst',
            'questions' => 'Werkblad',
            'answer_sheet' => 'Antwoordblad',
        ],
        'actions' => [
            'regenerate' => 'Opnieuw genereren',
            'worksheet' => 'Werkblad',
            'answer_sheet' => 'Antwoordblad',
        ],
        'notifications' => [
            'regeneration_started' => 'De oefening wordt opnieuw gegenereerd.',
        ],
        'help' => [
            'description' => 'Optioneel: een invalshoek of extra wensen voor de tekst. Leeg laten mag; de AI kiest dan zelf een invalshoek.',
        ],
        'status_messages' => [
            'pending' => 'De oefening wordt gegenereerd. Dit duurt meestal een minuut. Ververs de pagina om de nieuwste status te zien.',
            'failed' => 'Het genereren is mislukt. Gebruik "Opnieuw genereren" om het nog een keer te proberen.',
        ],
        'print' => [
            'worksheet_title' => 'Werkblad begrijpend lezen',
            'answer_sheet_title' => 'Antwoordblad',
            'name' => 'Naam',
            'questions' => 'Vragen',
            'question' => 'Vraag',
            'answer' => 'Antwoord',
            'paragraph' => 'Alinea',
            'skill' => 'Leesvaardigheid',
            'evidence' => 'Bewijszin',
        ],
    ],
    'crossword_puzzle' => [
        'singular' => 'kruiswoordpuzzel',
        'plural' => 'kruiswoordpuzzels',
        'navigation' => 'Kruiswoordpuzzels',
        'fields' => [
            'title' => 'Naam',
            'exercises' => 'Teksten',
            'group' => 'Niveau',
            'level' => 'Moeilijkheidsgraad',
        ],
        'columns' => [
            'status' => 'Status',
            'created_at' => 'Aangemaakt op',
        ],
        'sections' => [
            'status' => 'Status',
            'details' => 'Kenmerken',
            'grid' => 'Rooster',
            'clues' => 'Aanwijzingen',
            'across' => 'Horizontaal',
            'down' => 'Verticaal',
            'answer_sheet' => 'Antwoordblad',
        ],
        'actions' => [
            'relayout' => 'Opnieuw leggen',
            'regenerate' => 'Opnieuw genereren',
            'worksheet' => 'Werkblad',
            'answer_sheet' => 'Antwoordblad',
        ],
        'notifications' => [
            'relaid' => 'Het rooster is opnieuw gelegd.',
            'relayout_failed' => 'Het rooster kon niet beter gelegd worden. De puzzel is niet gewijzigd.',
            'regeneration_started' => 'De puzzel wordt opnieuw gegenereerd.',
        ],
        'help' => [
            'exercises' => 'Kies 3 tot 6 gegenereerde teksten. De woorden in de puzzel komen uit deze teksten.',
        ],
        'status_messages' => [
            'pending' => 'De puzzel wordt gegenereerd. Dit duurt een paar minuten. Ververs de pagina om de nieuwste status te zien.',
            'failed' => 'Het genereren is mislukt. Gebruik "Opnieuw genereren" om het nog een keer te proberen.',
        ],
        'print' => [
            'worksheet_title' => 'Werkblad kruiswoordpuzzel',
            'answer_sheet_title' => 'Antwoordblad kruiswoordpuzzel',
            'name' => 'Naam',
            'across' => 'Horizontaal',
            'down' => 'Verticaal',
        ],
    ],
];
