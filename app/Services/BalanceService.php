<?php

namespace App\Services;

use App\Models\LedgerEntry;
use App\Support\Enums\Currency;
use Illuminate\Support\Collection;

class BalanceService
{
    public function grouped(string $archive = 'all', string $through = ''): Collection
    {
        return LedgerEntry::whereNull('voided_at')
            ->when($archive !== 'all', fn ($query) => $query->whereHas('creditor', fn ($query) => $archive === 'archived' ? $query->whereNotNull('archived_at') : $query->whereNull('archived_at')))
            ->when($through !== '', fn ($query) => $query->where('occurred_at', '<=', $through))
            ->selectRaw('creditor_id, currency, SUM('.LedgerEntry::BALANCE_SQL.') AS balance')->groupBy('creditor_id', 'currency')->get();
    }

    public function balance(int $creditorId, Currency $currency): int
    {
        return (int) LedgerEntry::where('creditor_id', $creditorId)->where('currency', $currency->value)->whereNull('voided_at')->selectRaw('COALESCE(SUM('.LedgerEntry::BALANCE_SQL.'), 0) AS balance')->value('balance');
    }

    public function totals(string $archive = 'all', string $through = ''): array
    {
        $totals = ['YER' => ['debts' => 0, 'credits' => 0], 'SAR' => ['debts' => 0, 'credits' => 0]];
        foreach ($this->grouped($archive, $through) as $row) {
            $balance = (int) $row->balance;
            $totals[$row->currency->value]['debts'] += max($balance, 0);
            $totals[$row->currency->value]['credits'] += max(-$balance, 0);
        }

        return $totals;
    }
}
