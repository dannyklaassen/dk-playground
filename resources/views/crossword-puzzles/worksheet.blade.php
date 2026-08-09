@use('App\Enums\Direction')
@php
    /** @var \App\Models\CrosswordPuzzle $puzzle */
    $titles = $puzzle->exercises->pluck('title', 'id');
@endphp
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $puzzle->title }} &ndash; {{ __('admin.crossword_puzzle.print.worksheet_title') }}</title>
    @include('crossword-puzzles.partials.print-styles')
</head>
<body>
    <header>
        <strong>{{ __('admin.crossword_puzzle.print.worksheet_title') }}</strong>
        <span class="meta">
            {{ __('admin.crossword_puzzle.print.name') }}: <span class="line"></span>
        </span>
    </header>

    <h1>{{ $puzzle->title }}</h1>

    @include('crossword-puzzles.partials.grid', ['puzzle' => $puzzle, 'solved' => false])

    @include('crossword-puzzles.partials.solution-word', ['puzzle' => $puzzle])

    <div class="clues">
        @foreach ([Direction::Across, Direction::Down] as $direction)
            <div>
                <h2>{{ __('admin.crossword_puzzle.print.'.$direction->value) }}</h2>
                @foreach ($puzzle->entriesFor($direction) as $entry)
                    <p class="clue">
                        <span class="number">{{ $entry['number'] }}.</span>
                        {{ $entry['clue'] }}
                        @if (isset($titles[$entry['exercise_id'] ?? '']))
                            <span class="source">{{ $titles[$entry['exercise_id']] }}</span>
                        @endif
                    </p>
                @endforeach
            </div>
        @endforeach
    </div>
</body>
</html>
