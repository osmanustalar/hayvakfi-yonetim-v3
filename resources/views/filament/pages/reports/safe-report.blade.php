<x-filament-panels::page>
    {{-- Filter Form --}}
    <div class="mb-6">
        {{ $this->form }}
    </div>

    @if(!empty($reportData))
        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            {{-- Total Income --}}
            <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900 dark:to-green-800 rounded-lg p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-green-600 dark:text-green-400">Toplam Gelir</p>
                        <p class="text-2xl font-bold text-green-900 dark:text-green-100 mt-2">
                            {{ number_format($reportData['summary']['total_income'], 2, ',', '.') }} ₺
                        </p>
                    </div>
                    <div class="bg-green-500 dark:bg-green-600 rounded-full p-3">
                        <x-heroicon-o-arrow-trending-up class="w-8 h-8 text-white" />
                    </div>
                </div>
            </div>

            {{-- Total Expense --}}
            <div class="bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900 dark:to-red-800 rounded-lg p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-red-600 dark:text-red-400">Toplam Gider</p>
                        <p class="text-2xl font-bold text-red-900 dark:text-red-100 mt-2">
                            {{ number_format($reportData['summary']['total_expense'], 2, ',', '.') }} ₺
                        </p>
                    </div>
                    <div class="bg-red-500 dark:bg-red-600 rounded-full p-3">
                        <x-heroicon-o-arrow-trending-down class="w-8 h-8 text-white" />
                    </div>
                </div>
            </div>

            {{-- Net --}}
            @php
                $net = $reportData['summary']['net'];
                $isPositive = $net >= 0;
                $netCardBg    = $isPositive ? 'bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900 dark:to-blue-800' : 'bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900 dark:to-orange-800';
                $netTextLabel = $isPositive ? 'text-blue-600 dark:text-blue-400' : 'text-orange-600 dark:text-orange-400';
                $netTextValue = $isPositive ? 'text-blue-900 dark:text-blue-100' : 'text-orange-900 dark:text-orange-100';
                $netIconBg    = $isPositive ? 'bg-blue-500 dark:bg-blue-600' : 'bg-orange-500 dark:bg-orange-600';
            @endphp
            <div class="{{ $netCardBg }} rounded-lg p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium {{ $netTextLabel }}">Net Durum</p>
                        <p class="text-2xl font-bold {{ $netTextValue }} mt-2">
                            {{ number_format($net, 2, ',', '.') }} ₺
                        </p>
                    </div>
                    <div class="{{ $netIconBg }} rounded-full p-3">
                        <x-heroicon-o-banknotes class="w-8 h-8 text-white" />
                    </div>
                </div>
            </div>
        </div>

        {{-- Charts Section --}}
        @if(!empty($reportData['by_month']['labels']))
            <div class="mb-6">
                <x-filament::section>
                    <x-slot name="heading">Aylık Trend</x-slot>
                    <div
                        wire:ignore
                        x-data="{
                            chart: null,
                            initChart() {
                                const ctx = document.getElementById('cashFlowChart');
                                if (!ctx || typeof Chart === 'undefined') return;
                                if (this.chart) { this.chart.destroy(); }
                                const data = {{ \Illuminate\Support\Js::from($reportData['by_month']) }};
                                this.chart = new Chart(ctx, {
                                    type: 'line',
                                    data: {
                                        labels: data.labels,
                                        datasets: [
                                            { label: 'Gelir', data: data.income, borderColor: '#22c55e', backgroundColor: 'rgba(34,197,94,0.1)', fill: true, tension: 0.3 },
                                            { label: 'Gider', data: data.expense, borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,0.1)', fill: true, tension: 0.3 }
                                        ]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        plugins: { legend: { position: 'top' } },
                                        scales: { y: { beginAtZero: true } }
                                    }
                                });
                            }
                        }"
                        x-init="$nextTick(() => initChart())"
                    >
                        <canvas id="cashFlowChart" style="max-height: 400px;"></canvas>
                    </div>
                </x-filament::section>
            </div>
        @endif

        {{-- Category Table --}}
        @if($reportData['by_category']->isNotEmpty())
            <div class="mb-6">
                <x-filament::section>
                    <x-slot name="heading">Kategori Bazında Özet</x-slot>
                    <div class="overflow-x-auto">
                        <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Renk</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kategori</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tür</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tutar</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Oran (%)</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($reportData['by_category'] as $category)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="w-6 h-6 rounded-full" style="background-color: {{ $category['color'] }}"></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $category['name'] }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            @if($category['type'] === 'income')
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                                    Gelir
                                                </span>
                                            @else
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                                                    Gider
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-gray-100 font-semibold">
                                            {{ number_format($category['total'], 2, ',', '.') }} ₺
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-500 dark:text-gray-400">
                                            {{ number_format($category['percentage'], 2, ',', '.') }}%
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            </div>
        @endif

        {{-- Safe Table --}}
        @if($reportData['by_safe']->isNotEmpty())
            <div class="mb-6">
                <x-filament::section>
                    <x-slot name="heading">Kasa Bazında Özet</x-slot>
                    <div class="overflow-x-auto">
                        <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kasa</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Para Birimi</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Gelir</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Gider</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Net</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($reportData['by_safe'] as $safe)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $safe['safe_name'] }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $safe['currency_symbol'] }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600 dark:text-green-400 font-semibold">
                                            {{ number_format($safe['income'], 2, ',', '.') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600 dark:text-red-400 font-semibold">
                                            {{ number_format($safe['expense'], 2, ',', '.') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold {{ $safe['net'] >= 0 ? 'text-blue-600 dark:text-blue-400' : 'text-orange-600 dark:text-orange-400' }}">
                                            {{ number_format($safe['net'], 2, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            </div>
        @endif

        {{-- Transaction Count --}}
        <div class="text-center text-sm text-gray-500 dark:text-gray-400 mt-4">
            Toplam {{ $reportData['summary']['count'] }} işlem gösteriliyor
        </div>
    @else
        <x-filament::section>
            <div class="text-center py-12">
                <x-heroicon-o-document-chart-bar class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-100">Rapor bulunamadı</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Filtrele butonuna basarak rapor oluşturun.</p>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
