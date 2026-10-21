<?php

namespace App\Services;

use App\Models\LedgerEntry;
use App\Support\Enums\Currency;
use Illuminate\Support\Collection;

class BalanceService
{
    public function grouped(): Collection
    {
        return LedgerEntry::whereNull('voided_at')->selectRaw("creditor_id, currency, SUM(CASE WHEN kind = 'debt' THEN amount_minor ELSE -amount_minor END) AS balance")->groupBy('creditor_id', 'currency')->get();
    }

    public function balance(int $creditorId, Currency $currency): int
    {
        return (int) LedgerEntry::where('creditor_id', $creditorId)->where('currency', $currency->value)->whereNull('voided_at')->selectRaw("COALESCE(SUM(CASE WHEN kind = 'debt' THEN amount_minor ELSE -amount_minor END), 0) AS balance")->value('balance');
    }

    public function totals(): array
    {
        $totals = ['YER' => ['debts' => 0, 'credits' => 0], 'SAR' => ['debts' => 0, 'credits' => 0]];
        foreach ($this->grouped() as $row) {
            $balance = (int) $row->balance;
            $totals[$row->currency->value]['debts'] += max($balance, 0);
            $totals[$row->currency->value]['credits'] += max(-$balance, 0);
        }

        return $totals;
    }
}
