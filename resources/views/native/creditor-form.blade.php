<native:column class="w-full h-full bg-theme-background text-theme-on-background">
    <native:top-bar :title="$creditorId ? 'تعديل الجهة' : 'إضافة جهة'" :back="true" />
    <native:scroll-view class="w-full flex-1">
        <native:column class="w-full p-5 gap-5">
            <native:column class="w-full p-5 gap-4 rounded-2xl bg-theme-surface border border-theme-outline-variant">
            <native:text font="heading" class="text-theme-on-surface leading-relaxed text-2xl font-bold">شخص أو محل، حساب مستقل</native:text>
            <native:text class="text-theme-on-surface-variant">الاسم يكفي للبدء. أضف بقية التفاصيل متى احتجت.</native:text>
            <native:outlined-text-input placeholder="اسم الجهة" native:model="name" class="w-full" />
            @include('native.partials.field-error', ['field' => 'name'])
            @if($duplicate)
                <native:text class="text-theme-accent">توجد جهة بهذا الاسم. تأكد أنها جهة أخرى قبل الحفظ.</native:text>
            @endif
            <native:outlined-text-input placeholder="رقم الهاتف (اختياري)" native:model="phone" keyboard="phone" class="w-full" />
            @include('native.partials.field-error', ['field' => 'phone'])
            <native:select label="نوع الجهة" :options="array_keys(\App\NativeComponents\CreditorForm::icons())" native:model="iconLabel" class="w-full" />
            @include('native.partials.field-error', ['field' => 'icon'])
            <native:outlined-text-input placeholder="ملاحظة (اختياري)" native:model="notes" class="w-full" />
            @include('native.partials.field-error', ['field' => 'notes'])
            @include('native.partials.field-error', ['field' => 'database'])
            <native:button label="حفظ الجهة" @press="save" :disabled="$saved" class="w-full" />
            <native:button label="رجوع" variant="ghost" @press="onBackPressed" class="w-full" />
            </native:column>
        </native:column>
    </native:scroll-view>
    @include('native.partials.exit')
</native:column>
