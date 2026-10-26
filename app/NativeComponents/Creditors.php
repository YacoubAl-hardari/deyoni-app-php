<?php

namespace App\NativeComponents;

use App\Models\Creditor;
use App\Services\AppearanceService;
use App\Services\BalanceService;
use App\Support\Enums\Currency;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class Creditors extends NativeComponent
{
    use InteractsWithCreditorSheet;

    public string $search = '';

    public string $filter = 'جميع الجهات';

    public string $sort = 'آخر نشاط';

    public string $currency = 'ريال يمني';

    public int $page = 1;

    public bool $showFilters = false;

    public function toggleFilters(): void
    {
        $this->showFilters = ! $this->showFilters;
    }

    public function mount(): void
    {
        app(AppearanceService::class)->apply();
    }

    public function newDebt(): void
    {
        $this->navigate('/entries/create', ['kind' => 'debt']);
    }

    public function newPayment(): void
    {
        $this->navigate('/entries/create', ['kind' => 'payment']);
    }

    public function updatedSearch(): void
    {
        $this->page = 1;
    }

    public function updatedFilter(): void
    {
        $this->page = 1;
    }

    public function updatedSort(): void
    {
        $this->page = 1;
    }

    public function updatedCurrency(): void
    {
        $this->page = 1;
    }

    public function nextPage(): void
    {
        $this->page++;
    }

    public function previousPage(): void
    {
        $this->page = max(1, $this->page - 1);
    }

    public function render(): View
    {
        $service = app(BalanceService::class);
        $code = $this->currency === 'ريال سعودي' ? Currency::Sar : Currency::Yer;
        $groupedBalances = $service->grouped();
        $balances = $groupedBalances->groupBy('creditor_id');
        $query = Creditor::query()->withMax('entries', 'occurred_at');
        $this->filter === 'الأرشيف' ? $query->whereNotNull('archived_at') : $query->whereNull('archived_at');
        if ($this->search !== '') {
            $query->where('name', 'like', '%'.$this->search.'%');
        }
        $currencyBalances = $groupedBalances->filter(fn ($r) => $r->currency === $code);
        if (in_array($this->filter, ['عليها ديون', 'رصيد لك', 'مسددة'], true)) {
            $ids = $currencyBalances->filter(fn ($r) => $this->filter === 'عليها ديون' ? $r->balance > 0 : $r->balance < 0)->pluck('creditor_id');
            $this->filter === 'مسددة' ? $query->whereNotIn('id', $currencyBalances->where('balance', '!=', 0)->pluck('creditor_id')) : $query->whereIn('id', $ids);
        }
        if ($this->sort === 'الاسم') {
            $query->orderBy('name');
        } elseif ($this->sort === 'أعلى دين') {
            $query->orderByRaw("(SELECT COALESCE(SUM(CASE WHEN (kind = 'debt' AND direction = 'payable') OR (kind = 'payment' AND direction = 'receivable') THEN amount_minor ELSE -amount_minor END), 0) FROM ledger_entries WHERE creditor_id = creditors.id AND currency = ? AND voided_at IS NULL) DESC", [$code->value]);
        } else {
            $query->orderByDesc('entries_max_occurred_at');
        }

        return view('native.creditors', [
            'creditors' => $query->orderBy('id')->paginate(30, ['*'], 'page', $this->page),
            'balances' => $balances,
            'count' => Creditor::whereNull('archived_at')->count(),
            'selectedCurrency' => $code,
        ]);
    }
}
