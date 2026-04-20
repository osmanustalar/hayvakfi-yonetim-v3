<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\SafeReportService;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SafeReportExcelController extends Controller
{
    public function download(): StreamedResponse
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

        $dateFrom = $filters['date_from'] ?? 'başlangıç';
        $dateTo = $filters['date_to'] ?? 'bitiş';
        $filename = 'kasa-raporu-' . $dateFrom . '-' . $dateTo . '.xlsx';

        $response = new StreamedResponse(function () use ($data): void {
            $options = new Options();
            $writer = new Writer($options);
            $writer->openToFile('php://output');

            // Header style
            $headerStyle = (new Style())->setFontBold();

            // Headers
            $headers = [
                'Tarih',
                'Kasa',
                'Para Birimi',
                'Tür',
                'Kategori(ler)',
                'Tutar',
                'İşlem Sonrası Bakiye',
                'Kişi',
                'Açıklama',
            ];

            $writer->addRow(Row::fromValues($headers, $headerStyle));

            // Transactions
            foreach ($data['transactions'] as $transaction) {
                $categories = $transaction->items->map(fn ($item) => $item->category->name ?? '')->filter()->join(', ');
                $contactName = $transaction->contact
                    ? $transaction->contact->first_name . ' ' . $transaction->contact->last_name
                    : '';

                $writer->addRow(Row::fromValues([
                    $transaction->process_date->format('d.m.Y'),
                    $transaction->safe->name ?? '',
                    $transaction->safe->currency->symbol ?? '',
                    $transaction->type->label(),
                    $categories,
                    number_format((float) $transaction->total_amount, 2, ',', '.'),
                    number_format((float) $transaction->balance_after_created, 2, ',', '.'),
                    $contactName,
                    $transaction->description ?? '',
                ]));
            }

            // Add summary section
            $writer->addRow(Row::fromValues([])); // Empty row
            $writer->addRow(Row::fromValues(['ÖZET'], $headerStyle));
            $writer->addRow(Row::fromValues([
                'Toplam Gelir',
                number_format($data['summary']['total_income'], 2, ',', '.') . ' ₺',
            ]));
            $writer->addRow(Row::fromValues([
                'Toplam Gider',
                number_format($data['summary']['total_expense'], 2, ',', '.') . ' ₺',
            ]));
            $writer->addRow(Row::fromValues([
                'Net',
                number_format($data['summary']['net'], 2, ',', '.') . ' ₺',
            ]));
            $writer->addRow(Row::fromValues([
                'Toplam İşlem',
                $data['summary']['count'],
            ]));

            $writer->close();
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }
}
