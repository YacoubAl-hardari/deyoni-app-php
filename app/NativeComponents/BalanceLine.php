<?php

namespace App\NativeComponents;

use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class BalanceLine extends NativeComponent
{
    public int $amount = 0;

    public string $currency = 'YER';

    public function render(): View
    {
        return view('native.balance-line');
    }
}
