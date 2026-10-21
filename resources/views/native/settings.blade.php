<native:column class="w-full h-full bg-theme-background text-theme-on-background">
    <native:top-bar title="الإعدادات" :back="true" />
    <native:scroll-view class="w-full flex-1">
        <native:column class="w-full p-5 gap-5">
            <native:text font="heading" class="text-theme-on-surface leading-relaxed text-2xl font-bold">تجربة تناسبك</native:text>
            <native:column class="w-full p-5 gap-4 rounded-2xl bg-theme-surface border border-theme-outline-variant">
            <native:select label="المظهر" :options="['حسب النظام','فاتح','داكن']" native:model="appearance" class="w-full" />
            <native:select label="العملة الافتراضية" :options="['ريال يمني','ريال سعودي']" native:model="currency" class="w-full" />
            <native:button label="حفظ الإعدادات" @press="save" class="w-full" />
            </native:column>
            <native:column class="w-full p-5 gap-4 rounded-2xl bg-theme-surface border border-theme-outline-variant">
            <native:text font="heading" class="text-theme-on-surface leading-relaxed text-xl font-bold">نسخة تحفظ حساباتك</native:text>
            <native:text class="text-theme-on-surface-variant">احفظ نسخة محلية مشفرة، واحتفظ بكلمة مرورها. تحتاج الكلمة نفسها عند الاستعادة.</native:text>
            <native:outlined-text-input placeholder="كلمة مرور النسخة (8 أحرف على الأقل)" :secure="true" native:model="password" class="w-full" />
            @include('native.partials.field-error', ['field'=>'password'])
            @include('native.partials.field-error', ['field'=>'backup'])
            @include('native.partials.field-error', ['field'=>'database'])
            <native:button label="إنشاء نسخة احتياطية" @press="export" :disabled="$busy" class="w-full" />
            <native:button label="استعادة نسخة احتياطية" variant="secondary" @press="import" :disabled="$busy" class="w-full" />
            @if($status)<native:text class="text-theme-primary">{{ $status }}</native:text>@endif
            </native:column>
            <native:column class="w-full p-5 gap-4 rounded-2xl bg-theme-surface border border-theme-outline-variant">
            <native:text font="heading" class="text-theme-on-surface leading-relaxed font-bold">ديوني · الإصدار {{ config('nativephp.version') }}</native:text>
            <native:text class="text-sm text-theme-on-surface-variant">حساباتك محفوظة محليًا. لا حسابات مستخدمين ولا مزامنة سحابية.</native:text>
            </native:column>
        </native:column>
    </native:scroll-view>
    <native:modal :visible="$summary !== ''" :dismissible="false">
        <native:column class="w-full p-6 gap-4 bg-theme-surface">
            <native:text font="heading" class="text-theme-on-surface leading-relaxed text-xl font-bold">تأكيد استعادة البيانات</native:text>
            <native:text class="text-theme-on-surface leading-relaxed">{{ $summary }}</native:text>
            @include('native.partials.field-error', ['field'=>'backup'])
            <native:button label="استبدال البيانات واستعادة النسخة" variant="destructive" @press="restore" class="w-full" />
            <native:button label="إلغاء" variant="ghost" @press="dismissRestore" class="w-full" />
        </native:column>
    </native:modal>
    @include('native.partials.exit')
</native:column>
