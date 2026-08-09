<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CrosswordPuzzle;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class CrosswordPuzzleAnswerSheetController extends Controller
{
    public function __invoke(CrosswordPuzzle $crosswordPuzzle): View
    {
        // Its own ability: the answer sheet gives the solutions away.
        Gate::authorize('viewSolution', $crosswordPuzzle);

        abort_unless($crosswordPuzzle->generated_at !== null, 404);

        return view('crossword-puzzles.answer-sheet', [
            'puzzle' => $crosswordPuzzle,
        ]);
    }
}
