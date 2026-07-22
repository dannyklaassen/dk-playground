<?php

declare(strict_types=1);

use App\Http\Controllers\ComprehensionExerciseAnswerSheetController;
use App\Http\Controllers\ComprehensionExerciseWorksheetController;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Facades\Route;

Route::get('/', fn (): Factory|\Illuminate\Contracts\View\View => view('welcome'));

Route::middleware('auth')->group(function (): void {
    Route::get('begrijpend-lezen/{comprehensionExercise}/werkblad', ComprehensionExerciseWorksheetController::class)
        ->name('comprehension-exercises.worksheet');
    Route::get('begrijpend-lezen/{comprehensionExercise}/antwoordblad', ComprehensionExerciseAnswerSheetController::class)
        ->name('comprehension-exercises.answer-sheet');
});
