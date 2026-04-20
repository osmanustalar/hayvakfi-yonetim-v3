<?php

declare(strict_types=1);

namespace App\Filament\Pages\Reports;

use App\Models\Currency;
use App\Models\Safe;
use App\Models\SafeTransactionCategory;
use App\Services\Reports\SafeReportService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SafeReport extends Page
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Kasa Raporu';

    protected static string|\UnitEnum|null $navigationGroup = 'Raporlar';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.reports.safe-report';

    public ?array $safe_ids = null;

    public ?int $currency_id = null;

    public ?array $category_ids = null;

    public ?string $type = null;

    public ?string $date_from = null;

    public ?string $date_to = null;

    public array $reportData = [];

    public function mount(): void
    {
        $this->date_from = now()->startOfMonth()->toDateString();
        $this->date_to = now()->endOfMonth()->toDateString();

        $this->form->fill([
            'safe_ids' => $this->safe_ids,
            'currency_id' => $this->currency_id,
            'category_ids' => $this->category_ids,
            'type' => $this->type,
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
        ]);

        $this->loadReport();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Filtreler')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('safe_ids')
                                    ->label('Kasalar')
                                    ->placeholder('Tüm kasalar')
                                    ->multiple()
                                    ->options(fn () => Safe::where('company_id', session('active_company_id'))
                                        ->where('is_active', true)
                                        ->orderBy('name')
                                        ->pluck('name', 'id')),

                                Select::make('currency_id')
                                    ->label('Para Birimi')
                                    ->placeholder('Tüm para birimleri')
                                    ->options(fn () => Currency::where('is_active', true)
                                        ->orderBy('name')
                                        ->pluck('name', 'id')),

                                Select::make('type')
                                    ->label('İşlem Tipi')
                                    ->placeholder('Gelir & Gider')
                                    ->options([
                                        'income' => 'Gelir',
                                        'expense' => 'Gider',
                                    ]),
                            ]),

                        Grid::make(3)
                            ->schema([
                                Select::make('category_ids')
                                    ->label('Kategoriler')
                                    ->placeholder('Tüm kategoriler')
                                    ->multiple()
                                    ->options(fn () => SafeTransactionCategory::where(function ($q): void {
                                        $q->whereNull('company_id')
                                            ->orWhere('company_id', session('active_company_id'));
                                    })
                                        ->where('is_active', true)
                                        ->where('is_disable_in_report', false)
                                        ->orderBy('name')
                                        ->pluck('name', 'id'))
                                    ->columnSpan(1),

                                DatePicker::make('date_from')
                                    ->label('Başlangıç Tarihi')
                                    ->default(now()->startOfMonth())
                                    ->displayFormat('d.m.Y')
                                    ->native(false),

                                DatePicker::make('date_to')
                                    ->label('Bitiş Tarihi')
                                    ->default(now()->endOfMonth())
                                    ->displayFormat('d.m.Y')
                                    ->native(false),
                            ]),
                    ]),
            ]);
    }

    public function loadReport(): void
    {
        $data = $this->form->getState();

        $this->safe_ids = $data['safe_ids'] ?? null;
        $this->currency_id = $data['currency_id'] ?? null;
        $this->category_ids = $data['category_ids'] ?? null;
        $this->type = $data['type'] ?? null;
        $this->date_from = $data['date_from'] ?? null;
        $this->date_to = $data['date_to'] ?? null;

        $service = new SafeReportService();
        $this->reportData = $service->getReportData($this->getFilters());
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('filter')
                ->label('Filtrele')
                ->icon('heroicon-o-funnel')
                ->action('loadReport'),

            Action::make('exportExcel')
                ->label('Excel İndir')
                ->color('success')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn () => route('reports.safe.excel', $this->getFilters()))
                ->openUrlInNewTab(),

            Action::make('exportPdf')
                ->label('PDF İndir')
                ->color('success')
                ->icon('heroicon-o-document-arrow-down')
                ->url(fn () => route('reports.safe.pdf', $this->getFilters()))
                ->openUrlInNewTab(),
        ];
    }

    private function getFilters(): array
    {
        return [
            'safe_ids' => $this->safe_ids,
            'currency_id' => $this->currency_id,
            'category_ids' => $this->category_ids,
            'type' => $this->type,
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
        ];
    }
}
