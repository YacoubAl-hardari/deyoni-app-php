<?php

namespace App\Services;

use App\Models\Creditor;
use App\Models\LedgerEntry;
use App\Support\Enums\Currency;
use Carbon\CarbonImmutable;

class DashboardService
{
    /** @return array{totals: array, activeCount: int, monthCount: int, monthDebts: int, monthPayments: int, chart: array, hasActivity: bool} */
    public function snapshot(Currency $currency, string $archive = 'active', string $from = '', string $to = ''): array
    {
        PeriodFilter::dates($from, $to);
        $month = CarbonImmutable::now()->startOfMonth();
        $end = $to !== '' ? CarbonImmutable::parse($to)->startOfMonth() : $month;
        $start = $from !== '' ? CarbonImmutable::parse($from)->startOfMonth() : $end->subMonths(5);
        if ($start > $end) {
            $end = $start;
        }
        $query = PeriodFilter::apply(LedgerEntry::query()->whereNull('voided_at')->where('currency', $currency->value), $from, $to)
            ->when($archive !== 'all', fn ($query) => $query->whereHas('creditor', fn ($query) => $archive === 'archived' ? $query->whereNotNull('archived_at') : $query->whereNull('archived_at')));
        $monthly = (clone $query)->whereBetween('occurred_at', [$start->toDateString(), $end->endOfMonth()->toDateString()])
            ->selectRaw("substr(occurred_at, 1, 7) AS month, SUM(CASE WHEN kind = 'debt' THEN amount_minor ELSE 0 END) AS debts, SUM(CASE WHEN kind = 'payment' THEN amount_minor ELSE 0 END) AS payments, COUNT(*) AS entries_count")
            ->groupByRaw('substr(occurred_at, 1, 7)')->get()->keyBy('month');
        $chart = [];
        $step = max(1, (int) ceil(($start->diffInMonths($end) + 1) / 6));
        for ($date = $start; $date <= $end; $date = $date->addMonths($step)) {
            $bucket = $monthly->filter(fn ($row, $key) => $key >= $date->format('Y-m') && $key < $date->addMonths($step)->format('Y-m'));
            $debts = (int) $bucket->sum('debts');
            $payments = (int) $bucket->sum('payments');
            $chart[] = [
                'label' => $date->format('m/y'),
                'rangeLabel' => $date->format('m/y').($step > 1 ? ' - '.$date->addMonths($step - 1)->min($end)->format('m/y') : ''), 'debts' => $debts, 'payments' => $payments,
            ];
        }
        $maximum = max(1, ...array_column($chart, 'debts'), ...array_column($chart, 'payments'));
        foreach ($chart as &$point) {
            $point['debtHeight'] = $point['debts'] ? max(3, (int) round($point['debts'] / $maximum * 96)) : 0;
            $point['paymentHeight'] = $point['payments'] ? max(3, (int) round($point['payments'] / $maximum * 96)) : 0;
        }
        unset($point);
        $activity = ($from !== '' || $to !== '') ? clone $query : (clone $query)->whereBetween('occurred_at', [$month->toDateString(), $month->endOfMonth()->toDateString()]);

        return [
            'totals' => app(BalanceService::class)->totals($archive, $to)[$currency->value],
            'activeCount' => Creditor::query()->when($archive !== 'all', fn ($query) => $archive === 'archived' ? $query->whereNotNull('archived_at') : $query->whereNull('archived_at'))->count(),
            'monthCount' => (clone $activity)->count(),
            'monthDebts' => (int) (clone $activity)->where('kind', 'debt')->sum('amount_minor'),
            'monthPayments' => (int) (clone $activity)->where('kind', 'payment')->sum('amount_minor'),
            'payableDebts' => (int) (clone $activity)->where('kind', 'debt')->where('direction', 'payable')->sum('amount_minor'),
            'receivableDebts' => (int) (clone $activity)->where('kind', 'debt')->where('direction', 'receivable')->sum('amount_minor'),
            'paidPayments' => (int) (clone $activity)->where('kind', 'payment')->where('direction', 'payable')->sum('amount_minor'),
            'receivedPayments' => (int) (clone $activity)->where('kind', 'payment')->where('direction', 'receivable')->sum('amount_minor'),
            'chart' => $chart,
            'hasActivity' => $monthly->sum('entries_count') > 0,
        ];
    }
}
