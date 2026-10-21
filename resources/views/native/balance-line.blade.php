@use('App\Support\Money\Money')
@use('App\Support\Enums\Currency')
<native:text font="heading" :class="$amount > 0 ? 'text-sm font-bold text-theme-on-surface' : 'text-sm font-bold text-theme-on-surface'">{{ $amount > 0 ? 'عليك: ' : ($amount < 0 ? 'رصيد لك: ' : 'مسدد: ') }}{{ Money::format($amount, Currency::from($currency)) }}</native:text>
