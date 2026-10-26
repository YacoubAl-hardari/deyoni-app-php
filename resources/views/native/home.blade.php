@use('App\Support\Money\Money')
<native:column class="w-full h-full bg-theme-background">
    <native:top-bar title="ديوني" subtitle="نظرة على حساباتك">
        <native:top-bar-action icon="person_add" label="إضافة جهة" @press="openCreation" />
    </native:top-bar>
    <native:scroll-view class="w-full flex-1">
        <native:column class="w-full p-4 gap-4">
            <native:select label="عملة الإحصائيات" :options="['ريال يمني','ريال سعودي']" native:model="currency" class="w-full" />
            <native:button label="فرز لوحة التحكم" icon="filter_list" variant="secondary" @press="toggleFilters" class="w-full" />
            @if($showFilters)
                <native:column class="w-full p-4 gap-3 rounded-2xl bg-theme-surface border border-theme-outline-variant">
                    <native:select label="الجهات المشمولة" :options="['بدون الأرشيف','الأرشيف فقط','يشمل الأرشيف']" native:model="archiveFilter" class="w-full" />
                    <native:select label="الفترة" :options="['كل الفترات','اليوم','هذا الشهر','آخر 3 أشهر','فترة مخصصة']" native:model="period" class="w-full" />
                    <native:date-picker label="من تاريخ" mode="date" locale="ar" native:model="fromDate" class="w-full" />
                    <native:date-picker label="إلى تاريخ" mode="date" locale="ar" native:model="toDate" class="w-full" />
                    <native:button label="تطبيق الفترة" @press="applyFilters" class="w-full" />
                    <native:button label="إعادة تعيين" variant="ghost" @press="resetFilters" class="w-full" />
                    @if($filterError)<native:text class="text-sm text-theme-error">{{ $filterError }}</native:text>@endif
                </native:column>
            @endif
            <native:button :label="'مواعيد السداد · '.$dueCount.' مستحق أو متأخر'" icon="event" variant="secondary" @navigate="'/payment-promises'" class="w-full" />
            <native:column class="w-full p-5 gap-2 rounded-2xl bg-theme-primary">
                <native:text class="text-sm text-theme-on-primary">المتبقي عليك</native:text>
                <native:text font="heading" class="text-2xl font-bold text-theme-on-primary">{{ Money::format($totals['debts'], $selectedCurrency) }}</native:text>
                <native:text class="text-sm text-theme-on-primary">{{ $archiveFilter }} · الرصيد حتى {{ $appliedTo ?: 'آخر حركة' }}</native:text>
                <native:text class="text-xs text-theme-on-primary">رصيد تراكمي يشمل ما قبل بداية الفترة</native:text>
            </native:column>
            <native:row class="w-full gap-3">
                <native:column class="flex-1 h-32 p-3 gap-2 rounded-xl bg-theme-surface border border-theme-outline-variant">
                    <native:icon name="account_balance_wallet" :size="22" class="text-theme-primary" />
                    <native:text class="text-xs text-theme-on-surface-variant">رصيد لك</native:text>
                    <native:text font="heading" class="text-base font-bold text-theme-on-surface">{{ Money::format($totals['credits'], $selectedCurrency) }}</native:text>
                </native:column>
                <native:column class="flex-1 h-32 p-3 gap-2 rounded-xl bg-theme-surface border border-theme-outline-variant">
                    <native:icon name="people" :size="22" class="text-theme-primary" />
                    <native:text class="text-xs text-theme-on-surface-variant">الجهات المشمولة</native:text>
                    <native:text font="heading" class="text-xl font-bold text-theme-on-surface">{{ $activeCount }}</native:text>
                </native:column>
            </native:row>
            <native:row class="w-full gap-2">
                <native:button label="مبلغ لك" @press="newReceivable" class="flex-1" />
                <native:button label="سداد استلمته" variant="secondary" @press="receivePayment" class="flex-1" />
            </native:row>
            <native:row class="w-full gap-2">
                <native:button label="إضافة دين" icon="add" @press="newDebt" class="flex-1" />
                <native:button label="تسجيل سداد" variant="secondary" @press="newPayment" class="flex-1" />
            </native:row>
            <native:column class="w-full p-4 gap-3 rounded-2xl bg-theme-surface border border-theme-outline-variant">
                <native:text font="heading" class="text-lg font-bold text-theme-on-surface">{{ $appliedFrom || $appliedTo ? 'حركة الفترة المحددة' : 'حركة هذا الشهر' }}</native:text>
                <native:text class="text-xs text-theme-on-surface-variant">{{ $monthCount }} عملية فعالة · {{ $archiveFilter }}</native:text>
                <native:row class="w-full gap-3">
                    <native:column class="flex-1 gap-1">
                        <native:text class="text-sm text-theme-on-surface-variant">ديون أُضيفت عليك</native:text>
                        <native:text font="heading" class="text-base font-bold text-theme-on-surface">{{ Money::format($payableDebts, $selectedCurrency) }}</native:text>
                        <native:text class="text-sm text-theme-on-surface-variant">مبالغ أُضيفت لك</native:text>
                        <native:text font="heading" class="text-base font-bold text-theme-on-surface">{{ Money::format($receivableDebts, $selectedCurrency) }}</native:text>
                    </native:column>
                    <native:column class="flex-1 gap-1">
                        <native:text class="text-sm text-theme-on-surface-variant">سداد دفعته</native:text>
                        <native:text font="heading" class="text-base font-bold text-theme-on-surface">{{ Money::format($paidPayments, $selectedCurrency) }}</native:text>
                        <native:text class="text-sm text-theme-on-surface-variant">سداد استلمته</native:text>
                        <native:text font="heading" class="text-base font-bold text-theme-on-surface">{{ Money::format($receivedPayments, $selectedCurrency) }}</native:text>
                    </native:column>
                </native:row>
            </native:column>
            <native:column class="w-full p-4 gap-3 rounded-2xl bg-theme-surface border border-theme-outline-variant">
                <native:text font="heading" class="text-lg font-bold text-theme-on-surface">الديون والسداد</native:text>
                <native:text class="text-xs text-theme-on-surface-variant">{{ $appliedFrom ?: 'آخر 6 أشهر' }} إلى {{ $appliedTo ?: 'الشهر الحالي' }} · {{ $selectedCurrency->label() }} · {{ $archiveFilter }}</native:text>
                <native:text class="text-xs text-theme-on-surface-variant">الديون تشمل لك وعليك، والسداد يشمل المدفوع والمستلم. الفترات الطويلة تجمع في 6 مجموعات كحد أقصى.</native:text>
                <native:row class="w-full gap-4">
                    <native:text class="text-xs text-theme-primary">● ديون</native:text>
                    <native:text class="text-xs text-theme-on-surface-variant">● سداد</native:text>
                </native:row>
                @if($hasActivity)
                    <native:row class="w-full gap-2 items-end" a11y-label="مقارنة شهرية للديون والسداد">
                        @foreach($chart as $point)
                            <native:column class="flex-1 gap-2 items-center">
                                <native:row class="w-full h-28 gap-1 items-end" a11y-label="{{ $point['rangeLabel'] }}، ديون {{ Money::format($point['debts'], $selectedCurrency) }}، سداد {{ Money::format($point['payments'], $selectedCurrency) }}">
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
                                <native:text class="text-xs text-theme-on-surface-variant">{{ $point['rangeLabel'] }} · ديون {{ Money::format($point['debts'], $selectedCurrency) }}</native:text>
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
