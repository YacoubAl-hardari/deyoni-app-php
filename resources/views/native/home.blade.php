@use('App\Support\Money\Money')
<native:column class="w-full h-full bg-theme-background">
    <native:top-bar title="ديوني" subtitle="نظرة على حساباتك">
        <native:top-bar-action icon="person_add" label="إضافة جهة" @press="openCreation" />
    </native:top-bar>
    <native:scroll-view class="w-full flex-1">
        <native:column class="w-full p-4 gap-4">
            <native:select label="عملة الإحصائيات" :options="['ريال يمني','ريال سعودي']" native:model="currency" class="w-full" />
            <native:column class="w-full p-5 gap-2 rounded-2xl bg-theme-primary">
                <native:text class="text-sm text-theme-on-primary">المتبقي عليك</native:text>
                <native:text font="heading" class="text-2xl font-bold text-theme-on-primary">{{ Money::format($totals['debts'], $selectedCurrency) }}</native:text>
                <native:text class="text-sm text-theme-on-primary">يشمل الأرشيف · {{ $selectedCurrency->label() }}</native:text>
            </native:column>
            <native:row class="w-full gap-3">
                <native:column class="flex-1 h-32 p-3 gap-2 rounded-xl bg-theme-surface border border-theme-outline-variant">
                    <native:icon name="account_balance_wallet" :size="22" class="text-theme-primary" />
                    <native:text class="text-xs text-theme-on-surface-variant">رصيد لك</native:text>
                    <native:text font="heading" class="text-base font-bold text-theme-on-surface">{{ Money::format($totals['credits'], $selectedCurrency) }}</native:text>
                </native:column>
                <native:column class="flex-1 h-32 p-3 gap-2 rounded-xl bg-theme-surface border border-theme-outline-variant">
                    <native:icon name="people" :size="22" class="text-theme-primary" />
                    <native:text class="text-xs text-theme-on-surface-variant">الجهات النشطة</native:text>
                    <native:text font="heading" class="text-xl font-bold text-theme-on-surface">{{ $activeCount }}</native:text>
                </native:column>
            </native:row>
            <native:row class="w-full gap-2">
                <native:button label="إضافة دين" icon="add" @press="newDebt" class="flex-1" />
                <native:button label="تسجيل سداد" variant="secondary" @press="newPayment" class="flex-1" />
            </native:row>
            <native:column class="w-full p-4 gap-3 rounded-2xl bg-theme-surface border border-theme-outline-variant">
                <native:text font="heading" class="text-lg font-bold text-theme-on-surface">حركة هذا الشهر</native:text>
                <native:text class="text-xs text-theme-on-surface-variant">{{ now()->format('Y/m') }} · {{ $monthCount }} عملية فعالة</native:text>
                <native:row class="w-full gap-3">
                    <native:column class="flex-1 gap-1">
                        <native:text class="text-sm text-theme-on-surface-variant">ديون مسجلة</native:text>
                        <native:text font="heading" class="text-base font-bold text-theme-on-surface">{{ Money::format($monthDebts, $selectedCurrency) }}</native:text>
                    </native:column>
                    <native:column class="flex-1 gap-1">
                        <native:text class="text-sm text-theme-on-surface-variant">مبالغ مسددة</native:text>
                        <native:text font="heading" class="text-base font-bold text-theme-on-surface">{{ Money::format($monthPayments, $selectedCurrency) }}</native:text>
                    </native:column>
                </native:row>
            </native:column>
            <native:column class="w-full p-4 gap-3 rounded-2xl bg-theme-surface border border-theme-outline-variant">
                <native:text font="heading" class="text-lg font-bold text-theme-on-surface">الديون والسداد</native:text>
                <native:text class="text-xs text-theme-on-surface-variant">آخر 6 أشهر · {{ $selectedCurrency->label() }} · الملغاة مستبعدة</native:text>
                <native:row class="w-full gap-4">
                    <native:text class="text-xs text-theme-primary">● ديون</native:text>
                    <native:text class="text-xs text-theme-on-surface-variant">● سداد</native:text>
                </native:row>
                @if($hasActivity)
                    <native:row class="w-full gap-2 items-end" a11y-label="مقارنة شهرية للديون والسداد">
                        @foreach($chart as $point)
                            <native:column class="flex-1 gap-2 items-center">
                                <native:row class="w-full h-28 gap-1 items-end" a11y-label="{{ $point['label'] }}، ديون {{ Money::format($point['debts'], $selectedCurrency) }}، سداد {{ Money::format($point['payments'], $selectedCurrency) }}">
                                    <native:column :class="'flex-1 rounded-md bg-theme-primary h-['.max(1, $point['debtHeight']).'px]'" />
                                    <native:column :class="'flex-1 rounded-md bg-theme-on-surface-variant/40 h-['.max(1, $point['paymentHeight']).'px]'" />
                                </native:row>
                                <native:text class="text-xs text-theme-on-surface-variant">{{ $point['label'] }}</native:text>
                            </native:column>
                        @endforeach
                    </native:row>
                    @foreach($chart as $point)
                        @if($point['debts'] || $point['payments'])
                            <native:column class="w-full gap-1">
                                <native:text class="text-xs text-theme-on-surface-variant">{{ $point['label'] }} · ديون {{ Money::format($point['debts'], $selectedCurrency) }}</native:text>
                                <native:text class="text-xs text-theme-on-surface-variant">سداد {{ Money::format($point['payments'], $selectedCurrency) }}</native:text>
                            </native:column>
                        @endif
                    @endforeach
                @else
                    <native:column class="w-full p-4 gap-2 items-center">
                        <native:icon name="bar_chart" :size="32" class="text-theme-on-surface-variant" />
                        <native:text class="text-sm text-theme-on-surface-variant">ستظهر حركتك هنا بعد تسجيل أول عملية بهذه العملة.</native:text>
                    </native:column>
                @endif
            </native:column>
            <native:button label="عرض الجهات والحسابات" variant="ghost" @press="showCreditors" class="w-full" />
        </native:column>
    </native:scroll-view>
    @include('native.partials.main-navigation', ['activeTab' => 'home'])
    @include('native.partials.creditor-sheet')
</native:column>
