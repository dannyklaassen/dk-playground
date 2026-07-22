<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ComprehensionExercise;
use Illuminate\Contracts\View\View;

class ComprehensionExerciseWorksheetController extends Controller
{
    public function __invoke(ComprehensionExercise $comprehensionExercise): View
    {
        abort_unless($comprehensionExercise->generated_at !== null, 404);

        return view('comprehension-exercises.worksheet', [
            'exercise' => $comprehensionExercise,
        ]);
    }
}
