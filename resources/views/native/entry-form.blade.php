<native:column class="w-full h-full bg-theme-background text-theme-on-background">
    <native:top-bar :title="$entryId ? 'تفاصيل وتصحيح العملية' : $kindLabel" :back="true" />
    <native:scroll-view class="w-full flex-1">
        <native:column class="w-full p-5 gap-5">
            <native:text font="heading" class="text-2xl font-bold text-theme-on-surface">تفاصيل العملية</native:text>
            <native:text class="text-sm text-theme-on-surface-variant">سجّل المبلغ والتاريخ لتبقى حساباتك دقيقة.</native:text>
            <native:column class="w-full p-5 gap-4 rounded-2xl bg-theme-surface border border-theme-outline-variant">
            @if($cancelledEntry)<native:text class="text-theme-accent">هذه الحركة ملغاة ومحفوظة للتدقيق.</native:text>@endif
            @if(empty($choices) && !$entryId)
                <native:text class="text-theme-on-surface leading-relaxed">أضف جهة أولًا لتسجيل عملية مالية.</native:text>
                <native:button label="إضافة جهة" @navigate="'/creditors/create'" class="w-full" />
            @endif
            <native:select label="الجهة" :options="$entryId ? [$creditorChoice] : $choices" native:model="creditorChoice" :disabled="(bool) $entryId" class="w-full" />
            @include('native.partials.field-error', ['field' => 'creditor_id'])
            <native:select label="نوع العملية" :options="['دين جديد','سداد']" native:model="kindLabel" :disabled="$cancelledEntry" class="w-full" />
            <native:outlined-text-input placeholder="المبلغ" native:model="amount" keyboard="decimal" :disabled="$cancelledEntry" class="w-full" />
            @include('native.partials.field-error', ['field' => 'amount'])
            <native:select label="العملة" :options="['ريال يمني','ريال سعودي']" native:model="currencyLabel" :disabled="$cancelledEntry" class="w-full" />
            <native:text class="text-sm text-theme-on-surface-variant">اليمني دون كسور، والسعودي حتى منزلتين عشريتين.</native:text>
            <native:date-picker label="تاريخ العملية" mode="date" locale="ar" native:model="occurredAt" :disabled="$cancelledEntry" class="w-full" />
            @include('native.partials.field-error', ['field' => 'occurred_at'])
            <native:outlined-text-input placeholder="ملاحظة (اختياري)" native:model="notes" :disabled="$cancelledEntry" class="w-full" />
            @include('native.partials.field-error', ['field' => 'notes'])
            @if($entryId && !$cancelledEntry)
                <native:outlined-text-input placeholder="سبب التصحيح أو الإلغاء" native:model="reason" class="w-full" />
                @include('native.partials.field-error', ['field' => 'reason'])
            @endif
            @include('native.partials.field-error', ['field' => 'entry'])
            @include('native.partials.field-error', ['field' => 'database'])
            @if(!$cancelledEntry)
                <native:button :label="$entryId ? 'حفظ التصحيح' : 'حفظ العملية'" @press="save" :disabled="$saved" class="w-full" />
                @if($entryId)<native:button label="إلغاء الحركة" variant="destructive" @press="requestVoid" class="w-full" />@endif
            @endif
            <native:button label="رجوع" variant="ghost" @press="onBackPressed" class="w-full" />
            </native:column>
        </native:column>
    </native:scroll-view>
    <native:modal :visible="$confirmation !== ''" :dismissible="false">
        <native:column class="w-full p-6 gap-4 bg-theme-surface">
            <native:text font="heading" class="text-theme-on-surface leading-relaxed text-xl font-bold">تأكيد العملية</native:text>
            <native:text class="text-theme-on-surface leading-relaxed">{{ $confirmation }}</native:text>
            @include('native.partials.field-error', ['field' => 'reason'])
            @include('native.partials.field-error', ['field' => 'entry'])
            <native:button label="تأكيد" @press="confirm" class="w-full" />
            <native:button label="رجوع للتعديل" variant="ghost" @press="dismiss" class="w-full" />
        </native:column>
    </native:modal>
    @include('native.partials.exit')
</native:column>
