<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\KurbanEntry;
use App\Models\KurbanList;
use App\Models\KurbanSeason;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KurbanExcelController extends Controller
{
    public function download(): StreamedResponse
    {
        $seasonId = (int) request('season');
        $listId   = request('list') ? (int) request('list') : null;
        $paid     = request('paid'); // 'all' | '1' | '0'

        $season = KurbanSeason::findOrFail($seasonId);

        $query = KurbanEntry::with([
            'contact',
            'list.season',
            'group',
            'sacrificeCategory',
        ])
            ->whereHas('list', fn ($q) => $q->where('kurban_season_id', $seasonId))
            ->when($listId, fn ($q) => $q->where('kurban_list_id', $listId))
            ->when($paid === '1', fn ($q) => $q->where('is_paid', true))
            ->when($paid === '0', fn ($q) => $q->where('is_paid', false))
            ->orderBy('queue_number');

        $entries = $query->get();
        $filename = 'kurban-kayitlari-' . $season->year;

        if ($listId) {
            $list = KurbanList::find($listId);
            if ($list) {
                $filename .= '-' . str($list->name)->slug();
            }
        }

        $filename .= '.xlsx';

        $response = new StreamedResponse(function () use ($entries): void {
            $options = new Options;
            $writer  = new Writer($options);
            $writer->openToFile('php://output');

            // Başlık satırı stili
            $headerStyle = (new Style)->setFontBold();

            $headers = [
                'Sıra No',
                'Ad',
                'Soyad',
                'Telefon',
                'Şehir',
                'Kurban Türü',
                'Hayvan Türü',
                'Grup No',
                'Liste',
                'Sezon',
                'Ödendi mi?',
                'Ödeme Tarihi',
                'Notlar',
            ];

            $writer->addRow(Row::fromValues($headers, $headerStyle));

            foreach ($entries as $entry) {
                $writer->addRow(Row::fromValues([
                    $entry->queue_number,
                    $entry->contact?->first_name ?? '',
                    $entry->contact?->last_name ?? '',
                    $entry->contact?->phone ?? '',
                    $entry->contact?->city ?? '',
                    $entry->sacrificeCategory?->name ?? '',
                    $entry->livestock_type?->label() ?? '',
                    $entry->group?->group_no ?? '',
                    $entry->list?->name ?? '',
                    $entry->list?->season?->year ?? '',
                    $entry->is_paid ? 'Evet' : 'Hayır',
                    $entry->paid_date?->format('d.m.Y') ?? '',
                    $entry->notes ?? '',
                ]));
            }

            $writer->close();
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }
}
