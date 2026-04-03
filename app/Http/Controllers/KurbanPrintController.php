<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\KurbanSeason;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class KurbanPrintController extends Controller
{
    public function show(KurbanSeason $season): Response
    {
        $start = request('start');
        $end = request('end');
        $filename = "kurban-gruplari-{$season->year}";

        $groups = $season->groups()
            ->when($start, fn($q) => $q->where('group_no', '>=', $start))
            ->when($end, fn($q) => $q->where('group_no', '<=', $end))
            ->with([
                'entries' => fn ($q) => $q->orderBy('queue_number'),
                'entries.contact',
                'entries.sacrificeCategory',
            ])
            ->orderBy('group_no')
            ->get();

        if ($start || $end) {
            $filename .= "-" . ($start ?? '1') . "-" . ($end ?? 'son');
        }

        $logo1 = $this->encodeImage($season->logo1);
        $logo2 = $this->encodeImage($season->logo2);

        $pdf = Pdf::loadView('kurban.print', compact('season', 'groups', 'logo1', 'logo2'))
            ->setPaper('a4', 'portrait');

        return $pdf->download($filename . ".pdf");
    }

    private function encodeImage(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        $content = Storage::disk('public')->get($path);
        $ext     = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime    = match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif'         => 'image/gif',
            default       => 'image/png',
        };

        return 'data:' . $mime . ';base64,' . base64_encode($content);
    }
}
