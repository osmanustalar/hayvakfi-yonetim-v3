<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Enums\TransactionType;
use App\Models\KurbanEntry;
use App\Models\KurbanSeason;
use App\Models\Safe;
use App\Models\SafeTransaction;
use App\Models\SafeTransactionItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getSafeBalances(): Collection
    {
        $companyId = session('active_company_id');

        return Safe::where('company_id', $companyId)
            ->where('is_active', true)
            ->with(['currency', 'safeGroup'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function getMonthlyStats(): array
    {
        $companyId = session('active_company_id');

        $thisMonthStart = Carbon::now()->startOfMonth();
        $thisMonthEnd = Carbon::now()->endOfMonth();
        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        $thisMonthIncome = $this->getTransactionSum($companyId, TransactionType::INCOME, $thisMonthStart, $thisMonthEnd);
        $thisMonthExpense = $this->getTransactionSum($companyId, TransactionType::EXPENSE, $thisMonthStart, $thisMonthEnd);
        $lastMonthIncome = $this->getTransactionSum($companyId, TransactionType::INCOME, $lastMonthStart, $lastMonthEnd);
        $lastMonthExpense = $this->getTransactionSum($companyId, TransactionType::EXPENSE, $lastMonthStart, $lastMonthEnd);

        return [
            'this_month_income' => $thisMonthIncome,
            'this_month_expense' => $thisMonthExpense,
            'last_month_income' => $lastMonthIncome,
            'last_month_expense' => $lastMonthExpense,
        ];
    }

    public function getCashFlowData(): array
    {
        $companyId = session('active_company_id');
        $monthNames = ['Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara'];

        $labels = [];
        $incomeData = [];
        $expenseData = [];

        for ($i = 11; $i >= 0; $i--) {
            $start = Carbon::now()->subMonths($i)->startOfMonth();
            $end = Carbon::now()->subMonths($i)->endOfMonth();

            $monthLabel = $monthNames[$start->month - 1] . ' ' . $start->year;
            $labels[] = $monthLabel;

            $incomeData[] = (float) $this->getTransactionSum($companyId, TransactionType::INCOME, $start, $end);
            $expenseData[] = (float) $this->getTransactionSum($companyId, TransactionType::EXPENSE, $start, $end);
        }

        return [
            'labels' => $labels,
            'income' => $incomeData,
            'expense' => $expenseData,
        ];
    }

    public function getIncomeCategoryData(): array
    {
        $companyId = session('active_company_id');
        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        return $this->getCategoryData($companyId, TransactionType::INCOME, $start, $end);
    }

    public function getExpenseCategoryData(): array
    {
        $companyId = session('active_company_id');
        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        return $this->getCategoryData($companyId, TransactionType::EXPENSE, $start, $end);
    }

    public function getKurbanSummary(): ?array
    {
        $companyId = session('active_company_id');

        $activeSeason = KurbanSeason::where('company_id', $companyId)
            ->where('is_active', true)
            ->first();

        if (!$activeSeason) {
            return null;
        }

        $total = KurbanEntry::where('company_id', $companyId)
            ->whereHas('list', fn ($q) => $q->where('kurban_season_id', $activeSeason->id))
            ->count();

        $paid = KurbanEntry::where('company_id', $companyId)
            ->whereHas('list', fn ($q) => $q->where('kurban_season_id', $activeSeason->id))
            ->where('is_paid', true)
            ->count();

        $pending = $total - $paid;

        return [
            'total' => $total,
            'paid' => $paid,
            'pending' => $pending,
            'season_name' => $activeSeason->year . ' - ' . $activeSeason->code,
        ];
    }

    public function getRecentTransactions(int $limit = 10): Collection
    {
        $companyId = session('active_company_id');

        return SafeTransaction::where('company_id', $companyId)
            ->with(['safe', 'items.category'])
            ->orderByDesc('process_date')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    private function getTransactionSum(int $companyId, TransactionType $type, Carbon $start, Carbon $end): float
    {
        return (float) SafeTransaction::where('company_id', $companyId)
            ->where('type', $type)
            ->whereBetween('process_date', [$start->toDateString(), $end->toDateString()])
            ->whereHas('items.category', fn ($q) => $q->where('is_disable_in_report', false))
            ->sum('total_amount');
    }

    private function getCategoryData(int $companyId, TransactionType $type, Carbon $start, Carbon $end): array
    {
        $results = SafeTransactionItem::where('safe_transaction_items.company_id', $companyId)
            ->join('safe_transactions', 'safe_transaction_items.transaction_id', '=', 'safe_transactions.id')
            ->join('safe_transaction_categories', 'safe_transaction_items.transaction_category_id', '=', 'safe_transaction_categories.id')
            ->where('safe_transactions.type', $type)
            ->whereBetween('safe_transactions.process_date', [$start->toDateString(), $end->toDateString()])
            ->where('safe_transaction_categories.is_disable_in_report', false)
            ->select(
                'safe_transaction_categories.name',
                'safe_transaction_categories.color',
                DB::raw('SUM(safe_transaction_items.amount) as total')
            )
            ->groupBy('safe_transaction_categories.id', 'safe_transaction_categories.name', 'safe_transaction_categories.color')
            ->orderByDesc('total')
            ->get();

        $labels = [];
        $data = [];
        $colors = [];

        foreach ($results as $row) {
            $labels[] = $row->name;
            $data[] = (float) $row->total;
            $colors[] = $row->color ?? ($type === TransactionType::INCOME ? '#22c55e' : '#ef4444');
        }

        return [
            'labels' => $labels,
            'data' => $data,
            'colors' => $colors,
        ];
    }
}
