@use('App\Support\Money\Money')
<native:column class="w-full h-full bg-theme-background text-theme-on-background">
    <native:top-bar :title="$creditor->name" :back="true">
        <native:top-bar-action icon="edit" label="تعديل" @navigate="'/creditors/'.$creditor->id.'/edit'" />
    </native:top-bar>
    <native:list on-end-reached="loadMore" on-refresh="refreshEntries" :end-reached-buffer="5" class="w-full flex-1 bg-theme-background">
        <native:column class="w-full p-4 gap-2">
            <native:column class="w-full p-4 gap-2 rounded-2xl bg-theme-surface border border-theme-outline-variant">
                <native:row class="w-full gap-3 items-center">
                    <native:icon :name="$creditor->icon" :size="28" class="text-theme-primary" />
                    <native:text font="heading" class="flex-1 text-xl font-bold text-theme-on-surface">{{ $creditor->name }}</native:text>
                </native:row>
                @if($creditor->phone)<native:text class="text-sm text-theme-on-surface-variant">{{ $creditor->phone }}</native:text>@endif
                @if($creditor->notes)<native:text class="text-sm text-theme-on-surface-variant">{{ $creditor->notes }}</native:text>@endif
                @forelse($balances as $balance)
                    <native:balance-line :amount="(int) $balance->balance" :currency="$balance->currency->value" key="balance-{{ $balance->currency->value }}" />
                @empty
                    <native:text class="text-sm text-theme-primary">الحساب مسدد بالكامل</native:text>
                @endforelse
                @if($creditor->archived_at)<native:text class="text-xs text-theme-on-surface-variant">هذه الجهة مؤرشفة</native:text>@endif
                <native:row class="w-full gap-2">
                    <native:button label="حفظ PDF" icon="description" size="sm" @press="exportStatement" :disabled="$exporting" :loading="$exporting" class="flex-1" />
                    <native:button label="مشاركة" icon="share" size="sm" variant="secondary" @press="shareStatement" :disabled="$exporting" :loading="$exporting" class="flex-1" />
                </native:row>
                @if($exportStatus)<native:text class="text-xs text-theme-on-surface-variant">{{ $exportStatus }}</native:text>@endif
            </native:column>
            <native:button :label="$creditor->archived_at ? 'استرجاع الجهة' : 'أرشفة الجهة'" size="sm" variant="ghost" @press="archive" class="w-full" />
            <native:button label="جدولة موعد سداد" icon="event" variant="secondary" @press="schedulePayment" :disabled="(bool) $creditor->archived_at" class="w-full" />
            <native:button label="فرز السجل وفترة التقرير" icon="filter_list" variant="ghost" @press="toggleFilters" class="w-full" />
            @if($showFilters)
                <native:column class="w-full p-4 gap-3 rounded-xl bg-theme-surface">
                    <native:select label="الفترة" :options="['كل الفترات','اليوم','هذا الشهر','آخر 3 أشهر','فترة مخصصة']" native:model="period" class="w-full" />
                    <native:date-picker label="من تاريخ" mode="date" locale="ar" native:model="fromDate" class="w-full" />
                    <native:date-picker label="إلى تاريخ" mode="date" locale="ar" native:model="toDate" class="w-full" />
                    <native:select label="نوع الحركة" :options="['كل العمليات', ...array_keys(\App\NativeComponents\EntryForm::OPERATIONS), 'الملغاة']" native:model="entryFilter" class="w-full" />
                    <native:button label="تطبيق الفرز" @press="applyFilters" class="w-full" />
                    <native:button label="إعادة تعيين" variant="ghost" @press="resetFilters" class="w-full" />
                </native:column>
            @endif
            <native:text class="text-xs text-theme-on-surface-variant">الفترة المعروضة: {{ $appliedFrom ?: 'البداية' }} إلى {{ $appliedTo ?: 'آخر حركة' }} · PDF يشمل كل أنواع الحركات في هذه الفترة مع الرصيد الافتتاحي.</native:text>
            @if($filterError)<native:text class="text-sm text-theme-error">{{ $filterError }}</native:text>@endif
            <native:row class="w-full gap-2 items-center">
                <native:text font="heading" class="flex-1 text-lg font-bold text-theme-on-surface">سجل الحساب</native:text>
                <native:text class="text-xs text-theme-on-surface-variant">{{ $entryCount }} عملية · عُرض {{ $entries->count() }}</native:text>
            </native:row>
        </native:column>
            @php($lastDate = null)
            @forelse($entries as $entry)
                @if($lastDate !== $entry->occurred_at->format('Y-m-d'))
                    <native:text font="heading" class="text-xs font-bold text-theme-on-surface-variant">{{ $entry->occurred_at->format('Y/m/d') }}</native:text>
                    @php($lastDate = $entry->occurred_at->format('Y-m-d'))
                @endif
                <native:pressable @navigate="'/entries/'.$entry->id.'/edit'" key="entry-{{ $entry->id }}" class="w-full" a11y-label="{{ $entry->operationLabel() }} {{ Money::format($entry->amount_minor, $entry->currency) }} {{ $entry->voided_at ? 'ملغاة' : '' }}">
                    <native:row class="w-full gap-3 items-start">
                        <native:column class="w-3 h-20 gap-1 items-center">
                            <native:column :class="$entry->voided_at ? 'w-2 h-2 rounded-full bg-theme-outline' : 'w-2 h-2 rounded-full bg-theme-primary'" />
                            <native:column class="w-px flex-1 bg-theme-outline-variant" />
                        </native:column>
                        <native:column class="flex-1 p-3 gap-1 rounded-xl bg-theme-surface border border-theme-outline-variant">
                            <native:row class="w-full gap-2 items-center">
                                <native:text font="heading" class="flex-1 text-sm font-bold text-theme-on-surface">{{ $entry->operationLabel() }}</native:text>
                                <native:text font="heading" :class="$entry->voided_at ? 'text-sm text-theme-on-surface-variant line-through' : 'text-sm font-bold text-theme-primary'">{{ Money::format($entry->amount_minor, $entry->currency) }}</native:text>
                            </native:row>
                            @if($entry->notes)<native:text class="text-xs text-theme-on-surface-variant">{{ \Illuminate\Support\Str::limit($entry->notes, 90) }}</native:text>@endif
                            <native:text class="text-xs text-theme-on-surface-variant">#{{ $entry->id }} · {{ $entry->voided_at ? 'ملغاة، لا تؤثر على الرصيد' : 'فعالة' }}{{ $entry->replaces_entry_id ? ' · تصحيح #'.$entry->replaces_entry_id : '' }}</native:text>
                        </native:column>
                    </native:row>
                </native:pressable>
            @empty
                <native:column class="w-full p-5 gap-2 items-center bg-theme-surface rounded-xl">
                    <native:icon name="history" :size="28" class="text-theme-on-surface-variant" />
                    <native:text class="text-sm text-theme-on-surface-variant">لا توجد حركات بعد. أضف دينًا أو سجّل سدادًا.</native:text>
                </native:column>
            @endforelse
            @if($hasMore)
                <native:button label="تحميل المزيد" variant="ghost" @press="loadMore" class="w-full" />
            @elseif($entries->isNotEmpty())
                <native:text class="text-xs text-theme-on-surface-variant">وصلت إلى نهاية السجل</native:text>
            @endif
            <native:button label="حذف قيود فترة محددة" variant="ghost" @press="openRemoval" class="w-full" />
    </native:list>
    <native:modal :visible="$showRemoval" :dismissible="false">
        <native:column class="w-full p-5 gap-3 bg-theme-surface">
            <native:text font="heading" class="text-xl font-bold text-theme-on-surface">حذف قيود من السجل الفعال</native:text>
            <native:text class="text-sm text-theme-on-surface-variant">ستُلغى القيود المحددة ويتغير الرصيد وفقًا لها. تبقى نسخة في سجل الملغاة للتدقيق.</native:text>
            @if(!$deleteCount)
                <native:date-picker label="الحذف من" mode="date" locale="ar" native:model="deleteFrom" class="w-full" />
                <native:date-picker label="الحذف إلى" mode="date" locale="ar" native:model="deleteTo" class="w-full" />
                <native:outlined-text-input placeholder="سبب الحذف" native:model="deleteReason" class="w-full" />
                <native:button label="معاينة القيود" @press="previewRemoval" class="w-full" />
            @else
                <native:text class="text-sm text-theme-on-surface">{{ $deleteCount }} قيدًا من {{ $deleteFrom }} إلى {{ $deleteTo }}. هل تؤكد الحذف؟</native:text>
                <native:button label="تأكيد حذف القيود المحددة" variant="destructive" @press="confirmRemoval" class="w-full" />
            @endif
            @if($filterError)<native:text class="text-sm text-theme-error">{{ $filterError }}</native:text>@endif
            <native:button label="إلغاء" variant="ghost" @press="cancelRemoval" class="w-full" />
        </native:column>
    </native:modal>
    @if(!$creditor->archived_at)
        <native:bottom-bar>
            <native:column class="w-full p-3 gap-2 bg-theme-surface">
                <native:row class="w-full gap-2">
                    <native:button label="دين عليك" @press="newDebt" class="flex-1" />
                    <native:button label="سداد دفعته" variant="secondary" @press="newPayment" class="flex-1" />
                </native:row>
                <native:row class="w-full gap-2">
                    <native:button label="مبلغ لك" @press="newReceivable" class="flex-1" />
                    <native:button label="سداد استلمته" variant="secondary" @press="receivePayment" class="flex-1" />
                </native:row>
            </native:column>
        </native:bottom-bar>
    @endif
</native:column>
