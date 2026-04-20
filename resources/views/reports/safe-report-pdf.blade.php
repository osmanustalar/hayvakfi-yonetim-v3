<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>Kasa Raporu</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #333;
            line-height: 1.4;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #333;
        }

        .header h1 {
            font-size: 18px;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 11px;
            color: #666;
        }

        .summary {
            margin-bottom: 20px;
            background: #f9f9f9;
            padding: 10px;
            border-radius: 4px;
        }

        .summary-grid {
            display: table;
            width: 100%;
        }

        .summary-item {
            display: table-cell;
            width: 33.33%;
            padding: 5px;
            text-align: center;
        }

        .summary-label {
            font-size: 9px;
            color: #666;
            margin-bottom: 3px;
        }

        .summary-value {
            font-size: 14px;
            font-weight: bold;
        }

        .summary-value.income {
            color: #16a34a;
        }

        .summary-value.expense {
            color: #dc2626;
        }

        .summary-value.net {
            color: #2563eb;
        }

        h2 {
            font-size: 13px;
            margin: 15px 0 8px 0;
            padding-bottom: 5px;
            border-bottom: 1px solid #ddd;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        table th {
            background: #f3f4f6;
            padding: 6px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 9px;
            border: 1px solid #ddd;
        }

        table td {
            padding: 5px 8px;
            border: 1px solid #ddd;
            font-size: 9px;
        }

        table tr:nth-child(even) {
            background: #f9fafb;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }

        .badge-income {
            background: #dcfce7;
            color: #16a34a;
        }

        .badge-expense {
            background: #fee2e2;
            color: #dc2626;
        }

        .color-dot {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            vertical-align: middle;
        }

        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 8px;
            color: #999;
            padding-top: 10px;
            border-top: 1px solid #ddd;
        }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="header">
        <h1>Kasa Raporu</h1>
        <p>
            Tarih Aralığı:
            {{ $filters['date_from'] ? \Carbon\Carbon::parse($filters['date_from'])->format('d.m.Y') : 'Başlangıç' }}
            -
            {{ $filters['date_to'] ? \Carbon\Carbon::parse($filters['date_to'])->format('d.m.Y') : 'Bitiş' }}
        </p>
    </div>

    {{-- Summary --}}
    <div class="summary">
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-label">Toplam Gelir</div>
                <div class="summary-value income">{{ number_format($data['summary']['total_income'], 2, ',', '.') }} ₺</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Toplam Gider</div>
                <div class="summary-value expense">{{ number_format($data['summary']['total_expense'], 2, ',', '.') }} ₺</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Net</div>
                <div class="summary-value net">{{ number_format($data['summary']['net'], 2, ',', '.') }} ₺</div>
            </div>
        </div>
    </div>

    {{-- Category Summary --}}
    @if($data['by_category']->isNotEmpty())
        <h2>Kategori Özeti</h2>
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">Renk</th>
                    <th style="width: 40%;">Kategori</th>
                    <th style="width: 15%;" class="text-center">Tür</th>
                    <th style="width: 20%;" class="text-right">Tutar</th>
                    <th style="width: 20%;" class="text-right">Oran (%)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['by_category'] as $category)
                    <tr>
                        <td class="text-center">
                            <span class="color-dot" style="background-color: {{ $category['color'] }}"></span>
                        </td>
                        <td>{{ $category['name'] }}</td>
                        <td class="text-center">
                            @if($category['type'] === 'income')
                                <span class="badge badge-income">Gelir</span>
                            @else
                                <span class="badge badge-expense">Gider</span>
                            @endif
                        </td>
                        <td class="text-right">{{ number_format($category['total'], 2, ',', '.') }} ₺</td>
                        <td class="text-right">{{ number_format($category['percentage'], 2, ',', '.') }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Safe Summary --}}
    @if($data['by_safe']->isNotEmpty())
        <h2>Kasa Özeti</h2>
        <table>
            <thead>
                <tr>
                    <th style="width: 30%;">Kasa</th>
                    <th style="width: 15%;" class="text-center">Para Birimi</th>
                    <th style="width: 20%;" class="text-right">Gelir</th>
                    <th style="width: 20%;" class="text-right">Gider</th>
                    <th style="width: 15%;" class="text-right">Net</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['by_safe'] as $safe)
                    <tr>
                        <td>{{ $safe['safe_name'] }}</td>
                        <td class="text-center">{{ $safe['currency_symbol'] }}</td>
                        <td class="text-right" style="color: #16a34a;">{{ number_format($safe['income'], 2, ',', '.') }}</td>
                        <td class="text-right" style="color: #dc2626;">{{ number_format($safe['expense'], 2, ',', '.') }}</td>
                        <td class="text-right" style="font-weight: bold; color: {{ $safe['net'] >= 0 ? '#2563eb' : '#ea580c' }};">
                            {{ number_format($safe['net'], 2, ',', '.') }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Transactions --}}
    @if($data['transactions']->isNotEmpty())
        <h2>İşlem Listesi (İlk 50)</h2>
        <table>
            <thead>
                <tr>
                    <th style="width: 10%;">Tarih</th>
                    <th style="width: 15%;">Kasa</th>
                    <th style="width: 10%;" class="text-center">Tür</th>
                    <th style="width: 25%;">Kategori</th>
                    <th style="width: 15%;" class="text-right">Tutar</th>
                    <th style="width: 25%;">Açıklama</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['transactions']->take(50) as $transaction)
                    <tr>
                        <td>{{ $transaction->process_date->format('d.m.Y') }}</td>
                        <td>{{ $transaction->safe->name ?? '' }}</td>
                        <td class="text-center">
                            @if($transaction->type->value === 'income')
                                <span class="badge badge-income">Gelir</span>
                            @else
                                <span class="badge badge-expense">Gider</span>
                            @endif
                        </td>
                        <td>{{ $transaction->items->pluck('category.name')->filter()->join(', ') }}</td>
                        <td class="text-right">{{ number_format((float) $transaction->total_amount, 2, ',', '.') }} {{ $transaction->safe->currency->symbol ?? '' }}</td>
                        <td>{{ \Str::limit($transaction->description ?? '', 40) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @if($data['transactions']->count() > 50)
            <p style="font-size: 9px; color: #666; text-align: center;">
                * Toplam {{ $data['transactions']->count() }} işlemden ilk 50 tanesi gösterilmektedir.
            </p>
        @endif
    @endif

    {{-- Footer --}}
    <div class="footer">
        <p>Bu rapor {{ now()->format('d.m.Y H:i') }} tarihinde oluşturulmuştur.</p>
        <p>Vakıf Yönetim Sistemi v3</p>
    </div>
</body>
</html>
