<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Enums\TransactionType;
use App\Models\Safe;
use App\Models\SafeTransaction;
use App\Models\SafeTransactionItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SafeReportService
{
    public function getReportData(array $filters): array
    {
        $companyId = session('active_company_id');

        $query = SafeTransaction::query()
            ->where('company_id', $companyId)
            ->whereHas('items.category', fn ($q) => $q->where('is_disable_in_report', false));

        // Apply filters
        if (!empty($filters['safe_ids'])) {
            $query->whereIn('safe_id', $filters['safe_ids']);
        }

        if (!empty($filters['currency_id'])) {
            $query->where('currency_id', $filters['currency_id']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('process_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('process_date', '<=', $filters['date_to']);
        }

        if (!empty($filters['category_ids'])) {
            $query->whereHas('items', fn ($q) => $q->whereIn('transaction_category_id', $filters['category_ids']));
        }

        $transactions = $query->with(['safe.currency', 'items.category', 'currency', 'contact'])
            ->orderBy('process_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $summary = $this->calculateSummary($transactions);
        $byCategory = $this->calculateByCategory($companyId, $filters);
        $bySafe = $this->calculateBySafe($companyId, $filters);
        $byMonth = $this->calculateByMonth($companyId, $filters);

        return [
            'transactions' => $transactions,
            'summary' => $summary,
            'by_category' => $byCategory,
            'by_safe' => $bySafe,
            'by_month' => $byMonth,
        ];
    }

    private function calculateSummary(Collection $transactions): array
    {
        $totalIncome = $transactions
            ->where('type', TransactionType::INCOME)
            ->sum('total_amount');

        $totalExpense = $transactions
            ->where('type', TransactionType::EXPENSE)
            ->sum('total_amount');

        return [
            'total_income' => (float) $totalIncome,
            'total_expense' => (float) $totalExpense,
            'net' => (float) ($totalIncome - $totalExpense),
            'count' => $transactions->count(),
        ];
    }

    private function calculateByCategory(int $companyId, array $filters): Collection
    {
        $query = SafeTransactionItem::query()
            ->where('safe_transaction_items.company_id', $companyId)
            ->join('safe_transactions', 'safe_transaction_items.transaction_id', '=', 'safe_transactions.id')
            ->join('safe_transaction_categories', 'safe_transaction_items.transaction_category_id', '=', 'safe_transaction_categories.id')
            ->where('safe_transaction_categories.is_disable_in_report', false);

        // Apply same filters
        if (!empty($filters['safe_ids'])) {
            $query->whereIn('safe_transactions.safe_id', $filters['safe_ids']);
        }

        if (!empty($filters['currency_id'])) {
            $query->where('safe_transactions.currency_id', $filters['currency_id']);
        }

        if (!empty($filters['type'])) {
            $query->where('safe_transactions.type', $filters['type']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('safe_transactions.process_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('safe_transactions.process_date', '<=', $filters['date_to']);
        }

        if (!empty($filters['category_ids'])) {
            $query->whereIn('safe_transaction_items.transaction_category_id', $filters['category_ids']);
        }

        $results = $query->select(
            'safe_transaction_categories.name',
            'safe_transaction_categories.color',
            'safe_transactions.type',
            DB::raw('SUM(safe_transaction_items.amount) as total')
        )
            ->groupBy('safe_transaction_categories.id', 'safe_transaction_categories.name', 'safe_transaction_categories.color', 'safe_transactions.type')
            ->orderByDesc('total')
            ->get();

        $totalAmount = $results->sum('total');

        return $results->map(function ($row) use ($totalAmount) {
            $percentage = $totalAmount > 0 ? ($row->total / $totalAmount) * 100 : 0;

            return [
                'name' => $row->name,
                'color' => $row->color ?? ($row->type === TransactionType::INCOME->value ? '#22c55e' : '#ef4444'),
                'total' => (float) $row->total,
                'percentage' => round($percentage, 2),
                'type' => $row->type,
            ];
        });
    }

    private function calculateBySafe(int $companyId, array $filters): Collection
    {
        $query = SafeTransaction::query()
            ->where('safe_transactions.company_id', $companyId)
            ->whereHas('items.category', fn ($q) => $q->where('is_disable_in_report', false));

        // Apply filters
        if (!empty($filters['safe_ids'])) {
            $query->whereIn('safe_transactions.safe_id', $filters['safe_ids']);
        }

        if (!empty($filters['currency_id'])) {
            $query->where('safe_transactions.currency_id', $filters['currency_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('safe_transactions.process_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('safe_transactions.process_date', '<=', $filters['date_to']);
        }

        if (!empty($filters['category_ids'])) {
            $query->whereHas('items', fn ($q) => $q->whereIn('transaction_category_id', $filters['category_ids']));
        }

        $results = $query->join('safes', 'safe_transactions.safe_id', '=', 'safes.id')
            ->join('currencies', 'safes.currency_id', '=', 'currencies.id')
            ->select(
                'safes.id as safe_id',
                'safes.name as safe_name',
                'currencies.symbol as currency_symbol',
                DB::raw('SUM(CASE WHEN safe_transactions.type = "income" THEN safe_transactions.total_amount ELSE 0 END) as income'),
                DB::raw('SUM(CASE WHEN safe_transactions.type = "expense" THEN safe_transactions.total_amount ELSE 0 END) as expense')
            )
            ->groupBy('safes.id', 'safes.name', 'currencies.symbol')
            ->orderBy('safes.name')
            ->get();

        return $results->map(function ($row) {
            return [
                'safe_name' => $row->safe_name,
                'currency_symbol' => $row->currency_symbol,
                'income' => (float) $row->income,
                'expense' => (float) $row->expense,
                'net' => (float) ($row->income - $row->expense),
            ];
        });
    }

    private function calculateByMonth(int $companyId, array $filters): array
    {
        $monthNames = ['Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara'];

        $query = SafeTransaction::query()
            ->where('safe_transactions.company_id', $companyId)
            ->whereHas('items.category', fn ($q) => $q->where('is_disable_in_report', false));

        // Apply filters
        if (!empty($filters['safe_ids'])) {
            $query->whereIn('safe_transactions.safe_id', $filters['safe_ids']);
        }

        if (!empty($filters['currency_id'])) {
            $query->where('safe_transactions.currency_id', $filters['currency_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('safe_transactions.process_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('safe_transactions.process_date', '<=', $filters['date_to']);
        }

        if (!empty($filters['category_ids'])) {
            $query->whereHas('items', fn ($q) => $q->whereIn('transaction_category_id', $filters['category_ids']));
        }

        $results = $query->select(
            DB::raw("DATE_FORMAT(safe_transactions.process_date, '%Y-%m') as month_key"),
            DB::raw('SUM(CASE WHEN safe_transactions.type = "income" THEN safe_transactions.total_amount ELSE 0 END) as income'),
            DB::raw('SUM(CASE WHEN safe_transactions.type = "expense" THEN safe_transactions.total_amount ELSE 0 END) as expense')
        )
            ->groupBy('month_key')
            ->orderBy('month_key')
            ->get();

        $labels = [];
        $incomeData = [];
        $expenseData = [];

        foreach ($results as $row) {
            // Parse year and month from month_key (format: YYYY-MM)
            [$year, $month] = explode('-', $row->month_key);
            $monthIndex = (int) $month - 1;

            $labels[] = $monthNames[$monthIndex] . ' ' . $year;
            $incomeData[] = (float) $row->income;
            $expenseData[] = (float) $row->expense;
        }

        return [
            'labels' => $labels,
            'income' => $incomeData,
            'expense' => $expenseData,
        ];
    }
}
