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
        .crossword td.filled { border: 2px solid #111; background: #fff; color: #111; }
        /* Grey fill, not colour: worksheets are printed on black and white printers.
           Browsers drop backgrounds when printing unless the fill is declared as
           ink, so without print-color-adjust the marking comes out blank. */
        .crossword td.marked {
            background: #d4d4d4;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .crossword .number {
            position: absolute;
            top: 0;
            left: .1em;
            font-size: calc(var(--cell) * .28);
            line-height: 1.1;
            color: #444;
        }
        /* A letter in the opposite corner from the entry number: different corner
           and different character set, so the two numberings cannot be mixed up
           even when a marked square is also the start of an entry. Shared with
           the boxes under the grid, which only override the size: the a/b/c
           mapping only reads as one system if both corners look the same. */
        .solution-label {
            position: absolute;
            bottom: 0;
            right: .1em;
            line-height: 1.1;
            color: #111;
            font-weight: bold;
            font-style: italic;
        }
        .crossword .solution-label { font-size: calc(var(--cell) * .28); }
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
                        <td @class(['filled', 'marked' => $cell['solution'] !== null])>
                            @if ($cell['number'] !== null)
                                <span class="number">{{ $cell['number'] }}</span>
                            @endif
                            @if ($cell['solution'] !== null)
                                <span class="solution-label">{{ $cell['solution'] }}</span>
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
