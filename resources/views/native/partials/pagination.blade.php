@if($items->hasPages())
<native:row class="gap-2 items-center">
    <native:button label="السابق" variant="ghost" @press="previousPage" :disabled="$items->onFirstPage()" class="w-full" />
    <native:text class="text-theme-on-surface leading-relaxed">صفحة {{ $items->currentPage() }}</native:text>
    <native:button label="التالي" variant="ghost" @press="nextPage" :disabled="!$items->hasMorePages()" class="w-full" />
</native:row>
@endif
