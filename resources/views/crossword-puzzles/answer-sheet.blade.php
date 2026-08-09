@php
    /** @var \App\Models\CrosswordPuzzle $puzzle */
@endphp
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $puzzle->title }} &ndash; {{ __('admin.crossword_puzzle.print.answer_sheet_title') }}</title>
    @include('crossword-puzzles.partials.print-styles')
</head>
<body>
    <header>
        <strong>{{ __('admin.crossword_puzzle.print.answer_sheet_title') }}</strong>
    </header>

    <h1>{{ $puzzle->title }}</h1>

    @include('crossword-puzzles.partials.grid', ['puzzle' => $puzzle, 'solved' => true])

    @include('crossword-puzzles.partials.solution-word', ['puzzle' => $puzzle, 'solved' => true])
</body>
</html>
