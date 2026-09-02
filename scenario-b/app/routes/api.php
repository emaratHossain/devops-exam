<?php

use App\Http\Controllers\NoteController;
use Illuminate\Support\Facades\Route;

// Every route needs the X-Tenant header. See App\Http\Middleware\ResolveTenant.
Route::middleware('tenant')->group(function () {
    Route::post('/notes', [NoteController::class, 'store']);
    Route::get('/notes', [NoteController::class, 'index']);
    Route::get('/notes/{id}', [NoteController::class, 'show'])->whereNumber('id');
    Route::get('/search', [NoteController::class, 'search']);
    Route::get('/stats', [NoteController::class, 'stats']);
});
