<native:modal :visible="$exitConfirmation" :dismissible="false">
    <native:column class="w-full p-6 gap-4 bg-theme-surface">
        <native:text font="heading" class="text-theme-on-surface leading-relaxed text-xl font-bold">توجد تعديلات لم تُحفظ</native:text>
        <native:text class="text-theme-on-surface leading-relaxed">هل تريد مغادرة النموذج دون حفظ؟</native:text>
        <native:button label="متابعة التعديل" @press="stay" class="w-full" />
        <native:button label="مغادرة دون حفظ" variant="ghost" @press="leave" class="w-full" />
    </native:column>
</native:modal>
