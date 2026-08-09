<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CrosswordPuzzle;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class CrosswordPuzzleWorksheetController extends Controller
{
    public function __invoke(CrosswordPuzzle $crosswordPuzzle): View
    {
        Gate::authorize('view', $crosswordPuzzle);

        abort_unless($crosswordPuzzle->generated_at !== null, 404);

        return view('crossword-puzzles.worksheet', [
            'puzzle' => $crosswordPuzzle->load('exercises'),
        ]);
    }
}
