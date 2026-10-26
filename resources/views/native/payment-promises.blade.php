@use('App\Support\Money\Money')
<native:column class="w-full h-full bg-theme-background">
    <native:top-bar title="مواعيد السداد" subtitle="المتأخر واليوم والقادم" :back="true" />
    <native:scroll-view class="w-full flex-1">
        <native:column class="w-full p-4 gap-4">
            <native:column class="w-full p-4 gap-2 rounded-2xl bg-theme-primary">
                <native:text font="heading" class="text-xl font-bold text-theme-on-primary">رتّب مواعيدك المالية</native:text>
                <native:text class="text-sm text-theme-on-primary">الموعد تذكير داخل التطبيق. لا يغير الرصيد حتى تسجل السداد الفعلي.</native:text>
            </native:column>
            <native:button label="إضافة موعد سداد" icon="add" @press="newPromise" class="w-full" />
            @if($showForm)
                <native:column class="w-full p-4 gap-3 rounded-2xl bg-theme-surface border border-theme-outline-variant">
                    <native:text font="heading" class="text-lg font-bold text-theme-on-surface">{{ $promiseId ? 'تعديل الموعد' : 'موعد جديد' }}</native:text>
                    <native:select label="الجهة" :options="$choices" native:model="creditorChoice" class="w-full" />
                    <native:select label="اتجاه السداد" :options="['سداد أستلمه','سداد أدفعه']" native:model="directionLabel" class="w-full" />
                    <native:outlined-text-input placeholder="المبلغ المتفق عليه" keyboard="decimal" native:model="amount" class="w-full" />
                    <native:select label="العملة" :options="['ريال يمني','ريال سعودي']" native:model="currencyLabel" class="w-full" />
                    <native:date-picker label="موعد السداد" mode="date" locale="ar" native:model="dueAt" class="w-full" />
                    <native:outlined-text-input placeholder="ملاحظات الاتفاق" native:model="notes" class="w-full" />
                    @foreach($errors as $messages)
                        @foreach($messages as $message)<native:text class="text-sm text-theme-error">{{ $message }}</native:text>@endforeach
                    @endforeach
                    <native:button label="حفظ الموعد" @press="save" class="w-full" />
                    @if($promiseId)<native:button label="إلغاء هذا الموعد" variant="destructive" @press="cancelPromise" class="w-full" />@endif
                </native:column>
            @endif
            <native:select label="عرض المواعيد" :options="['المستحقة والمتأخرة','القادمة','كل المواعيد','المكتملة','الملغاة']" native:model="filter" class="w-full" />
            @forelse($promises as $promise)
                <native:column class="w-full p-4 gap-3 rounded-2xl bg-theme-surface border border-theme-outline-variant" key="promise-{{ $promise->id }}">
                    <native:text font="heading" class="text-lg font-bold text-theme-on-surface">{{ $promise->creditor->name }}</native:text>
                    <native:text class="text-sm text-theme-primary">{{ $promise->direction === 'receivable' ? 'تستلم' : 'تدفع' }} {{ Money::format($promise->amount_minor, $promise->currency) }}</native:text>
                    <native:text class="text-sm text-theme-on-surface-variant">{{ $promise->due_at->format('Y/m/d') }} · {{ $promise->status === 'fulfilled' ? 'مكتمل' : ($promise->status === 'cancelled' ? 'ملغى' : ($promise->due_at->isToday() ? 'مستحق اليوم' : ($promise->due_at->isPast() ? 'متأخر' : 'قادم'))) }}</native:text>
                    @if($promise->notes)<native:text class="text-sm text-theme-on-surface-variant">{{ $promise->notes }}</native:text>@endif
                    @if($promise->status === 'pending')
                        <native:button label="تسجيل سداد الموعد" @press="requestSettlement({{ $promise->id }})" class="w-full" />
                        <native:button label="تعديل أو إلغاء الموعد" variant="ghost" @press="editPromise({{ $promise->id }})" class="w-full" />
                    @endif
                </native:column>
            @empty
                <native:text class="text-sm text-theme-on-surface-variant">لا توجد مواعيد في هذا التصنيف.</native:text>
            @endforelse
            @include('native.partials.pagination', ['items' => $promises])
        </native:column>
    </native:scroll-view>
    <native:modal :visible="(bool) $settleId" :dismissible="false">
        <native:column class="w-full p-5 gap-3 bg-theme-surface">
            @if($settling)
                <native:text font="heading" class="text-xl font-bold text-theme-on-surface">تأكيد السداد الفعلي</native:text>
                <native:text class="text-sm text-theme-on-surface">{{ $settling->creditor->name }} · {{ Money::format($settling->amount_minor, $settling->currency) }}</native:text>
                <native:text class="text-sm text-theme-on-surface-variant">سيُسجل كامل مبلغ الموعد في الحساب. أي مبلغ يتجاوز الرصيد يتحول تلقائيًا إلى رصيد مقابل. للسداد الجزئي عدّل مبلغ الموعد أو سجّل حركة من حساب الجهة.</native:text>
                <native:date-picker label="تاريخ السداد الفعلي" mode="date" locale="ar" native:model="settledAt" class="w-full" />
                @foreach($errors as $messages)
                    @foreach($messages as $message)<native:text class="text-sm text-theme-error">{{ $message }}</native:text>@endforeach
                @endforeach
                <native:button label="تأكيد تسجيل السداد" @press="confirmSettlement" class="w-full" />
            @endif
            <native:button label="رجوع" variant="ghost" @press="dismissSettlement" class="w-full" />
        </native:column>
    </native:modal>
    @include('native.partials.exit')
</native:column>
