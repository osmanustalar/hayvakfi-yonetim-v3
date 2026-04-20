<?php

use App\Http\Controllers\KurbanExcelController;
use App\Http\Controllers\KurbanPrintController;
use App\Http\Controllers\Reports\SafeReportExcelController;
use App\Http\Controllers\Reports\SafeReportPdfController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/admin'));

Route::middleware(['auth'])->group(function () {
    Route::get('/kurban/print/{season}', [KurbanPrintController::class, 'show'])->name('kurban.print');
    Route::get('/kurban/export', [KurbanExcelController::class, 'download'])->name('kurban.export');

    Route::get('/reports/safe/excel', [SafeReportExcelController::class, 'download'])->name('reports.safe.excel');
    Route::get('/reports/safe/pdf', [SafeReportPdfController::class, 'download'])->name('reports.safe.pdf');
});
