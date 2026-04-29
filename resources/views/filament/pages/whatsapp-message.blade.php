<x-filament-panels::page>
    <div class="space-y-6">
        {{ $this->form }}

        @if ($recipient_count > 0)
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-700 dark:bg-green-900/30 dark:text-green-300">
                <strong>{{ number_format($recipient_count) }}</strong> kişiye mesaj gönderilecek (benzersiz telefon numarası bazında)
            </div>
        @else
            <div class="rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-800 dark:border-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300">
                Seçime uygun telefon numarası olan kişi bulunamadı.
            </div>
        @endif
    </div>
</x-filament-panels::page>
