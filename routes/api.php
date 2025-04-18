<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CandidateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ElectionListController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VoteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/current-election', [ElectionListController::class, 'activeElection']);
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
    Route::post('/elections', [ElectionListController::class, 'store'])->middleware(['role:admin,sanctum']);
    Route::get('/elections/{id}', [ElectionListController::class, 'show']);
    Route::post('/elections/{id}', [ElectionListController::class, 'update'])->middleware(['role:admin,sanctum']);
    Route::delete('/elections/{id}', [ElectionListController::class, 'destroy'])->middleware(['role:admin,sanctum']);
});

// Candidate routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/candidates', [CandidateController::class, 'index']);
    Route::post('/candidates', [CandidateController::class, 'store'])->middleware(['role:admin,sanctum']);
    Route::get('/candidates/{id}', [CandidateController::class, 'show']);
    Route::post('/candidates/{id}', [CandidateController::class, 'update'])->middleware(['role:admin,sanctum']);
    Route::delete('/candidates/{id}', [CandidateController::class, 'destroy'])->middleware(['role:admin,sanctum']);
});

// Vote routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/votes', [VoteController::class, 'store'])->middleware(['role:mahasiswa,sanctum']);
    Route::get('/votes/check/{electionId}', [VoteController::class, 'checkVote'])->middleware(['role:admin,sanctum']);
    Route::get('/votes/results/{electionId}', [VoteController::class, 'getResults'])->middleware(['role:admin,sanctum']);
});

// Dashboard routes
Route::get('/dashboard/stream/{electionId}', [DashboardController::class, 'stream'])->middleware('auth:sanctum,role:admin,sanctum');
