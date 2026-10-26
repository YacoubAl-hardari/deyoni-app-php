@use('App\Support\Money\Money')
<table class="ledger" dir="rtl" repeat_header="1">
    <thead><tr><th width="17%">التاريخ</th><th width="19%">الحركة</th><th width="22%">المبلغ</th><th width="22%">الرصيد التراكمي</th><th width="20%">الحالة</th></tr></thead>
    <tbody>
    @foreach($rows as $row)
        @php($entry = $row['entry'])
        <tr>
            <td><span dir="ltr">{{ $entry->occurred_at->format('Y/m/d') }}</span><br><span class="note">#{{ $entry->id }}</span></td>
            <td>{{ $entry->operationLabel() }}@if($entry->notes)<br><span class="note">{{ $entry->notes }}</span>@endif</td>
            <td>{{ Money::format($entry->amount_minor, $entry->currency) }}</td>
            <td>{{ $row['balance'] > 0 ? 'عليك' : ($row['balance'] < 0 ? 'لك' : 'مسدد') }}<br>{{ Money::format($row['balance'], $entry->currency) }}</td>
            <td>{{ $entry->voided_at ? 'ملغاة' : 'فعالة' }}@if($entry->replaces_entry_id)<br><span class="note">تصحيح #{{ $entry->replaces_entry_id }}</span>@endif</td>
        </tr>
    @endforeach
    </tbody>
</table>
