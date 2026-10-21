<native:bottom-sheet :visible="$creationVisible" detents="large" @dismiss="requestCloseCreation" a11y-label="إضافة جهة جديدة">
    <native:scroll-view class="w-full">
        <native:column class="w-full p-5 gap-3 bg-theme-surface">
            <native:text font="heading" class="text-xl font-bold text-theme-on-surface">جهة جديدة</native:text>
            <native:text class="text-sm text-theme-on-surface-variant">ابدأ بالاسم، وأضف بقية التفاصيل إن رغبت.</native:text>
            <native:outlined-text-input placeholder="اسم الشخص أو المحل" native:model="creationName" class="w-full" />
            <native:outlined-text-input placeholder="رقم الهاتف (اختياري)" keyboard="phone" native:model="creationPhone" class="w-full" />
            <native:select label="نوع الجهة" :options="array_keys(\App\NativeComponents\CreditorForm::icons())" native:model="creationIcon" class="w-full" />
            <native:outlined-text-input placeholder="ملاحظة (اختياري)" native:model="creationNotes" class="w-full" />
            @foreach($creationErrors as $messages)
                @foreach($messages as $message)
                    <native:text class="text-sm text-theme-destructive">{{ $message }}</native:text>
                @endforeach
            @endforeach
            <native:button label="حفظ الجهة" @press="saveCreation" class="w-full" />
            <native:button label="إلغاء" variant="ghost" @press="requestCloseCreation" class="w-full" />
        </native:column>
    </native:scroll-view>
</native:bottom-sheet>

<native:modal :visible="$discardCreation" :dismissible="false">
    <native:column class="w-full p-5 gap-3 bg-theme-surface">
        <native:text font="heading" class="text-lg font-bold text-theme-on-surface">تفاصيل لم تُحفظ</native:text>
        <native:text class="text-sm text-theme-on-surface-variant">يمكنك متابعة الإضافة أو تجاهل المسودة.</native:text>
        <native:button label="متابعة الإضافة" @press="keepCreation" class="w-full" />
        <native:button label="تجاهل المسودة" variant="ghost" @press="closeCreation" class="w-full" />
    </native:column>
</native:modal>
