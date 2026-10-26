<?php

namespace App\NativeComponents;

use App\Models\AppSetting;
use App\Models\PaymentPromise;
use App\Services\AppearanceService;
use App\Services\DashboardService;
use App\Services\PeriodFilter;
use App\Support\Enums\Currency;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class Home extends NativeComponent
{
    use InteractsWithCreditorSheet;

    public string $currency = 'ريال يمني';

    public string $archiveFilter = 'بدون الأرشيف';

    public string $fromDate = '';

    public string $toDate = '';

    public string $appliedFrom = '';

    public string $appliedTo = '';

    public string $filterError = '';

    public bool $showFilters = false;

    public string $period = 'كل الفترات';

    public function updatedPeriod(): void
    {
        if ($this->period !== 'فترة مخصصة') {
            [$this->fromDate, $this->toDate] = PeriodFilter::preset($this->period);
            $this->applyFilters();
        }
    }

    public function toggleFilters(): void
    {
        $this->showFilters = ! $this->showFilters;
    }

    public function applyFilters(): void
    {
        try {
            PeriodFilter::dates($this->fromDate, $this->toDate);
            $this->appliedFrom = $this->fromDate;
            $this->appliedTo = $this->toDate;
            $this->filterError = '';
        } catch (ValidationException $exception) {
            $this->filterError = $exception->validator->errors()->first();
        }
    }

    public function resetFilters(): void
    {
        $this->fromDate = $this->toDate = $this->appliedFrom = $this->appliedTo = $this->filterError = '';
        $this->archiveFilter = 'بدون الأرشيف';
        $this->period = 'كل الفترات';
    }

    public function newReceivable(): void
    {
        $this->navigate('/entries/create', ['kind' => 'debt', 'direction' => 'receivable']);
    }

    public function receivePayment(): void
    {
        $this->navigate('/entries/create', ['kind' => 'payment', 'direction' => 'receivable']);
    }

    public function mount(): void
    {
        app(AppearanceService::class)->apply();
        $this->currency = AppSetting::read('currency', 'YER') === 'SAR' ? 'ريال سعودي' : 'ريال يمني';
    }

    public function newDebt(): void
    {
        $this->navigate('/entries/create', ['kind' => 'debt']);
    }

    public function newPayment(): void
    {
        $this->navigate('/entries/create', ['kind' => 'payment']);
    }

    public function render(): View
    {
        $currency = $this->currency === 'ريال سعودي' ? Currency::Sar : Currency::Yer;

        return view('native.home', [
            ...app(DashboardService::class)->snapshot($currency, ['بدون الأرشيف' => 'active', 'الأرشيف فقط' => 'archived', 'يشمل الأرشيف' => 'all'][$this->archiveFilter] ?? 'active', $this->appliedFrom, $this->appliedTo),
            'selectedCurrency' => $currency,
            'dueCount' => PaymentPromise::where('status', 'pending')->whereHas('creditor', fn ($query) => $query->whereNull('archived_at'))->whereDate('due_at', '<=', today())->count(),
        ]);
    }
}
