<?php

namespace App\Services;

use App\Models\Creditor;
use App\Models\LedgerEntry;
use App\Support\Enums\Currency;
use Carbon\CarbonImmutable;

class DashboardService
{
    /** @return array{totals: array, activeCount: int, monthCount: int, monthDebts: int, monthPayments: int, chart: array, hasActivity: bool} */
    public function snapshot(Currency $currency): array
    {
        $month = CarbonImmutable::now()->startOfMonth();
        $start = $month->subMonths(5);
        $query = LedgerEntry::query()->whereNull('voided_at')->where('currency', $currency->value);
        $monthly = (clone $query)->whereBetween('occurred_at', [$start->toDateString(), $month->endOfMonth()->toDateString()])
            ->selectRaw("substr(occurred_at, 1, 7) AS month, SUM(CASE WHEN kind = 'debt' THEN amount_minor ELSE 0 END) AS debts, SUM(CASE WHEN kind = 'payment' THEN amount_minor ELSE 0 END) AS payments, COUNT(*) AS entries_count")
            ->groupByRaw('substr(occurred_at, 1, 7)')->get()->keyBy('month');
        $maximum = max(1, (int) $monthly->max('debts'), (int) $monthly->max('payments'));
        $chart = [];
        for ($index = 0; $index < 6; $index++) {
            $date = $start->addMonths($index);
            $row = $monthly->get($date->format('Y-m'));
            $debts = (int) ($row?->debts ?? 0);
            $payments = (int) ($row?->payments ?? 0);
            $chart[] = [
                'label' => $date->format('m/y'), 'debts' => $debts, 'payments' => $payments,
                'debtHeight' => $debts > 0 ? max(3, (int) round($debts / $maximum * 96)) : 0,
                'paymentHeight' => $payments > 0 ? max(3, (int) round($payments / $maximum * 96)) : 0,
            ];
        }
        $current = $monthly->get($month->format('Y-m'));

        return [
            'totals' => app(BalanceService::class)->totals()[$currency->value],
            'activeCount' => Creditor::query()->whereNull('archived_at')->count(),
            'monthCount' => (int) ($current?->entries_count ?? 0),
            'monthDebts' => (int) ($current?->debts ?? 0),
            'monthPayments' => (int) ($current?->payments ?? 0),
            'chart' => $chart,
            'hasActivity' => $monthly->sum('entries_count') > 0,
        ];
    }
}
