<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\SafeReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class SafeReportPdfController extends Controller
{
    public function download(): Response
    {
        $filters = [
            'safe_ids' => request('safe_ids') ? (is_array(request('safe_ids')) ? request('safe_ids') : [request('safe_ids')]) : null,
            'currency_id' => request('currency_id') ? (int) request('currency_id') : null,
            'category_ids' => request('category_ids') ? (is_array(request('category_ids')) ? request('category_ids') : [request('category_ids')]) : null,
            'type' => request('type') ?? null,
            'date_from' => request('date_from') ?? null,
            'date_to' => request('date_to') ?? null,
        ];

        $service = new SafeReportService();
        $data = $service->getReportData($filters);

        $pdf = Pdf::loadView('reports.safe-report-pdf', compact('data', 'filters'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('kasa-raporu.pdf');
    }
}
