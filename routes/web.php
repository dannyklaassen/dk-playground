<?php

declare(strict_types=1);

use App\Http\Controllers\ComprehensionExerciseAnswerSheetController;
use App\Http\Controllers\ComprehensionExerciseWorksheetController;
use App\Http\Controllers\CrosswordPuzzleAnswerSheetController;
use App\Http\Controllers\CrosswordPuzzleWorksheetController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::middleware('auth')->group(function (): void {
    Route::get('begrijpend-lezen/{comprehensionExercise}/werkblad', ComprehensionExerciseWorksheetController::class)
        ->name('comprehension-exercises.worksheet');
    Route::get('begrijpend-lezen/{comprehensionExercise}/antwoordblad', ComprehensionExerciseAnswerSheetController::class)
        ->name('comprehension-exercises.answer-sheet');
    Route::get('kruiswoordpuzzels/{crosswordPuzzle}/werkblad', CrosswordPuzzleWorksheetController::class)
        ->name('crossword-puzzles.worksheet');
    Route::get('kruiswoordpuzzels/{crosswordPuzzle}/antwoordblad', CrosswordPuzzleAnswerSheetController::class)
        ->name('crossword-puzzles.answer-sheet');
});
