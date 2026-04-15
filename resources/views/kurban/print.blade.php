<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kurban Grupları - {{ $season->year }}</title>
    <style>
        /*
         * Referans PDF koordinatlarından ölçülen kesin değerler:
         *   Sayfa       : 595.3 x 841.9 pt (A4)
         *   Sol kenar   : ~57pt = 20mm  (DOCX tablosu sol margine taşıyor)
         *   Sağ kenar   : ~71pt = 25mm  (DOCX margin değeri)
         *   Üst/alt     : 25mm  (DOCX pgMar = 1417 twips)
         *   Logo arası  : ~5pt gap (gap1+gap2 = 9.64pt toplam)
         *   Grup kodu   : 40pt, normal ağırlık, 51.3pt yükseklik (LH=1.3)
         *   Kod→tablo   : 20.7pt boşluk
         *   Satır yüks. : 63.65pt (1273 twips)
         *   Hücre font  : 30pt, normal
         *   Kolon oran. : 7.4% | 67.8% | 24.8%  (725/6673/2446 twips)
         */

        @page {
            margin: 0;
            size: A4 portrait;
        }
        
        * { margin: 0; padding: 0; }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            background: #fff;
            color: #000;
        }

        .group-page {
            page-break-after: always;
            text-align: center;
        }
        .group-page:last-child {
            page-break-after: avoid;
        }

        /* ── Logo 1: Bayraklar ─────────────────────────────────────
           DOCX: cx=290.3pt, cy=77pt
           Referans gap sonrası: toplam gap1+gap2 = 9.64pt → 5pt her birinde */
        .logo-flags {
            margin-top: 20mm;
            text-align: center;
            margin-bottom: 5pt;
            line-height: 0;
        }
        .logo-flags img {
            width: 290.3pt;
            /*height: 77pt;*/
        }

        /* ── Logo 2: Vakıf / Başlık ───────────────────────────────
           DOCX: cx=320.3pt, cy=94.8pt */
        .logo-vakif {
            text-align: center;
            margin-bottom: 5pt;
            line-height: 0;
        }
        .logo-vakif img {
            width: 320.3pt;
            /*height: 94.8pt;*/
        }

        /* ── Grup Kodu ────────────────────────────────────────────
           DOCX: sz=80 → 40pt, bold=no, jc=center
           Referans: y=252.34→303.66 (51.3pt yükseklik)
           40pt × LH=1.3 = 52pt metin yüksekliği ≈ 51.3pt ✓
           Kod-tablo arası: 324.35 - 303.66 = 20.69pt → margin-bottom: 20pt */
        .group-code {
            font-size: 35pt;
            font-weight: normal;
            text-align: center;
            line-height: 1.3;
            margin-bottom: 20pt;
            color: #000;
        }

        /* ── Üye Tablosu ──────────────────────────────────────────
           DOCX: trHeight=1273 twips = 63.65pt
           Hücre: sz=60 → 30pt, bold=no, vAlign=center
           Kolon: 725/6673/2446 twips = 7.4%/67.8%/24.8% */
        .members-table {
            padding-left: 20mm;
            padding-right: 20mm;
            width: 100%;
            border-collapse: collapse;
            border: 1pt solid #000;
        }

        .members-table td {
            border: 0.5pt solid #000;
            text-align: center;
            vertical-align: middle;
            font-size: 23pt;
            font-weight: normal;
            height: 63.65pt;
            padding: 0;
            color: #000;
            line-height: 1;
        }

        .col-no   { width: 7.4%;  }
        .col-name { width: 67.8%; }
        .col-type { width: 24.8%; }
    </style>
</head>
<body>

@php
/**
 * Türkçe büyük harf: i→İ, ı→I önce, sonra mb_strtoupper diğerlerini tamamlar.
 * (mb_strtoupper tek başına i→I yapar, Türkçede hatalı)
 */
function trUpper(string $s): string {
    $s = str_replace(['i', 'ı'], ['İ', 'I'], $s);
    return mb_strtoupper($s, 'UTF-8');
}
@endphp

@foreach($groupsWithAssets as $item)
@php
    $group = $item['group'];
    $logo1 = $item['logo1'];
    $logo2 = $item['logo2'];
    $code = $item['code'];
@endphp
<div class="group-page">

    @if($logo1)
    <div class="logo-flags">
        <img src="{{ $logo1 }}" alt="">
    </div>
    @endif

    @if($logo2)
    <div class="logo-vakif">
        <img src="{{ $logo2 }}" alt="">
    </div>
    @endif

    <div class="group-code">{{ $season->groupCode($group->group_no) }}</div>

    <table class="members-table">
        <tbody>
            @foreach($group->entries as $entry)
            <tr>
                <td class="col-no">{{ $entry->queue_number }}</td>
                <td class="col-name">{{ trUpper($entry->full_name ?? '') }}</td>
                <td class="col-type">{{ trUpper($entry->sacrificeCategory?->name ?? '') }}</td>
            </tr>
            @endforeach
            @for($i = $group->entries->count(); $i < 7; $i++)
            <tr>
                <td class="col-no">{{ $i + 1 }}</td>
                <td class="col-name">&nbsp;</td>
                <td class="col-type">&nbsp;</td>
            </tr>
            @endfor
        </tbody>
    </table>

</div>
@endforeach

</body>
</html>
