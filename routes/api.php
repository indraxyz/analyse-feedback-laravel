<?php

use App\Http\Controllers\AnalyseFeedbackController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['status' => 'ok']));

Route::post('/analyse-feedback', [AnalyseFeedbackController::class, 'store'])
    ->middleware('throttle:analyse-feedback');
