<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CandidateController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);

// Candidate routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('candidates', [CandidateController::class, 'index']);
    Route::post('candidates', [CandidateController::class, 'store']);
    Route::get('candidates/{id}', [CandidateController::class, 'show']);
    Route::post('candidates/{id}', [CandidateController::class, 'update']);
    Route::delete('candidates/{id}', [CandidateController::class, 'destroy']);
});
