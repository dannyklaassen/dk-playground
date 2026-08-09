@use('App\Support\Crossword\SolutionWord')
@php
    /** @var \App\Models\CrosswordPuzzle $puzzle */
    $solutionWord = $puzzle->solutionWord();
    $solved ??= false;
@endphp

@if ($solutionWord !== null)
    {{-- @once: the view page renders this partial twice, the styles only have to land once. --}}
    @once
        <style>
            .solution-word { margin: 1.5rem auto 0; }
            .solution-word h2 { font-size: 12pt; border: 0; margin: 0 0 .5rem; text-align: center; }
            /* Fixed size: at most eight boxes, so they never need to scale down with the grid. */
            .solution-boxes { border-collapse: collapse; margin: 0 auto; }
            .solution-boxes td {
                width: 12mm;
                height: 12mm;
                border: 2px solid #111;
                padding: 0;
                position: relative;
            }
            /* Only the size differs from the label in the grid; the shared
               .solution-label rule keeps both corners identical. */
            .solution-boxes .index { font-size: 8pt; }
            .solution { margin-top: 1.5rem; text-align: center; font-size: 12pt; }
        </style>
    @endonce

    @if ($solved)
        <p class="solution">
            <strong>{{ __('admin.crossword_puzzle.print.solution_word') }}:</strong>
            {{-- Words are stored as A-Z for the grid algorithm; the basisschool writes lower case. --}}
            {{ mb_strtolower($solutionWord->word) }}
        </p>
    @else
        <div class="solution-word">
            <h2>{{ __('admin.crossword_puzzle.print.solution_word') }}</h2>
            <div class="crossword-scroll">
                <table class="solution-boxes">
                    <tr>
                        @foreach (array_keys($solutionWord->cells) as $index)
                            <td><span class="solution-label index">{{ SolutionWord::label($index) }}</span></td>
                        @endforeach
                    </tr>
                </table>
            </div>
        </div>
    @endif
@endif
