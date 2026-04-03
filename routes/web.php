<?php

use App\Http\Controllers\KurbanPrintController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/kurban/print/{season}', [KurbanPrintController::class, 'show'])->name('kurban.print');
});
