<?php

namespace App\NativeComponents;

use App\Models\AppSetting;
use App\Services\AppearanceService;
use App\Services\DashboardService;
use App\Support\Enums\Currency;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class Home extends NativeComponent
{
    use InteractsWithCreditorSheet;

    public string $currency = 'ريال يمني';

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
            ...app(DashboardService::class)->snapshot($currency),
            'selectedCurrency' => $currency,
        ]);
    }
}
