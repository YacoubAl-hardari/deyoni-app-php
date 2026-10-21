<native:column class="w-full h-full bg-theme-background">
    <native:top-bar title="الجهات" subtitle="الأشخاص والمحلات، في مكان واحد">
        <native:top-bar-action icon="person_add" label="إضافة جهة" @press="openCreation" />
    </native:top-bar>
    <native:scroll-view class="w-full flex-1">
        <native:column class="w-full p-4 gap-3">
            <native:text class="text-sm text-theme-on-surface-variant">{{ $count }} جهة نشطة · الحسابات منفصلة لكل عملة</native:text>
            <native:outlined-text-input placeholder="ابحث باسم الشخص أو المحل" native:model.debounce.300ms="search" class="w-full" />
            <native:row class="w-full gap-2">
                <native:button label="إضافة جهة" icon="person_add" size="sm" @press="openCreation" class="flex-1" />
                <native:button :label="$showFilters ? 'إخفاء الفلاتر' : 'تصفية وترتيب'" size="sm" variant="ghost" @press="toggleFilters" class="flex-1" />
            </native:row>
            @if($showFilters)
                <native:column class="w-full p-3 gap-2 rounded-xl bg-theme-surface border border-theme-outline-variant">
                    <native:select label="عرض الجهات" :options="['جميع الجهات','عليها ديون','رصيد لك','مسددة','الأرشيف']" native:model="filter" class="w-full" />
                    <native:row class="w-full gap-3">
                        <native:select label="العملة" :options="['ريال يمني','ريال سعودي']" native:model="currency" class="flex-1" />
                        <native:select label="الترتيب" :options="['آخر نشاط','الاسم','أعلى دين']" native:model="sort" class="flex-1" />
                    </native:row>
                </native:column>
            @endif
            @forelse($creditors as $creditor)
                <native:pressable @navigate="'/creditors/'.$creditor->id" class="w-full">
                    <native:column class="w-full p-3 gap-2 rounded-xl bg-theme-surface border border-theme-outline-variant">
                        <native:row class="gap-3 items-center">
                            <native:icon :name="$creditor->icon" class="text-theme-primary" />
                            <native:text font="heading" class="text-base font-bold text-theme-on-surface">{{ $creditor->name }}</native:text>
                        </native:row>
                        @forelse($balances->get($creditor->id, collect()) as $balance)
                            <native:balance-line :amount="(int) $balance->balance" :currency="$balance->currency->value" key="balance-{{ $creditor->id }}-{{ $balance->currency->value }}" />
                        @empty
                            <native:text class="text-theme-success">الحساب مسدد بالكامل</native:text>
                        @endforelse
                        <native:text class="text-xs text-theme-on-surface-variant">{{ $creditor->entries_max_occurred_at ? 'آخر عملية: '.$creditor->entries_max_occurred_at : 'لا توجد عمليات بعد' }}</native:text>
                    </native:column>
                </native:pressable>
            @empty
                <native:column class="w-full p-6 gap-4 items-center">
                    <native:icon name="account_balance_wallet" class="text-theme-primary" :size="48" />
                    <native:text font="heading" class="text-theme-on-surface leading-relaxed text-lg font-bold">كل حساب يبدأ بخطوة</native:text>
                    <native:text class="text-center text-theme-on-surface-variant">ابدأ بإضافة أول شخص أو محل لتسجيل ديونك ومتابعتها بسهولة.</native:text>
                    <native:button label="إضافة أول جهة" @press="openCreation" class="w-full" />
                </native:column>
            @endforelse
            @include('native.partials.pagination', ['items' => $creditors])
        </native:column>
    </native:scroll-view>
    @include('native.partials.main-navigation', ['activeTab' => 'creditors'])
    @include('native.partials.creditor-sheet')
</native:column>
