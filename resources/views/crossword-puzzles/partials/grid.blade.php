@php
    /** @var \App\Models\CrosswordPuzzle $puzzle */
    $solved ??= false;
@endphp

{{-- @once: the view page renders this partial twice, the styles only have to land once. --}}
@once
    <style>
        .crossword-scroll { overflow-x: auto; }
        .crossword { border-collapse: collapse; margin: 0 auto; }
        .crossword td {
            width: var(--cell);
            height: var(--cell);
            padding: 0;
            position: relative;
            vertical-align: middle;
            text-align: center;
        }
        /* The cell states both its colours: it also renders inside the panel, which has a dark mode. */
        .crossword td.filled { border: 1px solid #111; background: #fff; color: #111; }
        .crossword .number {
            position: absolute;
            top: 0;
            left: .1em;
            font-size: calc(var(--cell) * .28);
            line-height: 1.1;
            color: #444;
        }
        .crossword .letter {
            font-size: calc(var(--cell) * .55);
            font-weight: bold;
        }
    </style>
@endonce

<div class="crossword-scroll">
    <table class="crossword" style="--cell: {{ $puzzle->cellSizeMm() }}mm">
        @foreach ($puzzle->cells() as $row)
            <tr>
                @foreach ($row as $cell)
                    @if ($cell === null)
                        <td></td>
                    @else
                        <td class="filled">
                            @if ($cell['number'] !== null)
                                <span class="number">{{ $cell['number'] }}</span>
                            @endif
                            @if ($solved)
                                {{-- Words are stored as A-Z for the grid algorithm; the basisschool writes lower case. --}}
                                <span class="letter">{{ mb_strtolower($cell['letter']) }}</span>
                            @endif
                        </td>
                    @endif
                @endforeach
            </tr>
        @endforeach
    </table>
</div>
