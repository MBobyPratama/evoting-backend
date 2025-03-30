<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CandidateController;
use App\Http\Controllers\ElectionListController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VoteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Logout route
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});

// Profile route
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show']);
});

// Election routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/elections', [ElectionListController::class, 'index']);
    Route::post('/elections', [ElectionListController::class, 'store']);
    Route::get('/elections/{id}', [ElectionListController::class, 'show']);
    Route::post('/elections/{id}', [ElectionListController::class, 'update']);
    Route::delete('/elections/{id}', [ElectionListController::class, 'destroy']);
});

// Candidate routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/candidates', [CandidateController::class, 'index']);
    Route::post('/candidates', [CandidateController::class, 'store']);
    Route::get('/candidates/{id}', [CandidateController::class, 'show']);
    Route::post('/candidates/{id}', [CandidateController::class, 'update']);
    Route::delete('/candidates/{id}', [CandidateController::class, 'destroy']);
});

// Vote routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/votes', [VoteController::class, 'store']);
    Route::get('/votes/check/{electionId}', [VoteController::class, 'checkVote']);
    Route::get('/votes/results/{electionId}', [VoteController::class, 'getResults']);
});
