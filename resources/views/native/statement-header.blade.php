@use('App\Support\Money\Money')
@use('App\Support\Enums\Currency')
<style>
body { font-family: jannalt; color: #152b35; line-height: 1.6; }
h1 { font-size: 24pt; margin: 0 0 4mm; color: #0f766e; }
h2 { font-size: 14pt; margin: 5mm 0 2mm; }
.meta { color: #526571; font-size: 9pt; }
.brand { font-size: 12pt; color: #0f766e; }
.summary { width:100%; border-collapse: collapse; margin: 5mm 0; }
.summary th { background:#edf3f5; padding:3mm; font-weight:normal; }
.summary td { padding:3mm; border-bottom:1px solid #dce4e8; text-align:center; }
.ledger { width:100%; border-collapse:collapse; margin:3mm 0; table-layout:fixed; }
.ledger th { background:#152b35; color:white; padding:2.5mm 1.5mm; font-size:9pt; }
.ledger td { padding:2.5mm 1.5mm; border-bottom:1px solid #dce4e8; font-size:8pt; vertical-align:top; }
.note { color:#526571; font-size:8pt; }
</style>
<div dir="rtl">
    <div class="brand">ديوني / الحسابات</div>
    <h1>كشف حساب</h1>
    <div class="meta">تاريخ الإصدار: <span dir="ltr">{{ $issuedAt->format('Y/m/d H:i') }}</span></div>
    <h2>{{ $creditor->name }}</h2>
    <div class="meta">الفترة: {{ $from ?: 'البداية' }} إلى {{ $to ?: 'آخر حركة' }}</div>
    @if($creditor->phone)<div class="meta">الهاتف: <span dir="ltr">{{ $creditor->phone }}</span></div>@endif
    @if($creditor->notes)<div class="meta">{{ $creditor->notes }}</div>@endif
    @if($creditor->archived_at)<div class="meta">جهة مؤرشفة</div>@endif
    <table class="summary" dir="rtl">
        <thead><tr><th>العملة</th><th>الرصيد الافتتاحي</th><th>ديون الفترة (لك وعليك)</th><th>سداد الفترة (دفع واستلام)</th><th>الرصيد الختامي</th></tr></thead>
        <tbody>
        @foreach(Currency::cases() as $currency)
            @php($total = $totals[$currency->value])
            <tr>
                <td>{{ $currency->label() }}</td>
                <td>{{ $total['opening'] > 0 ? 'عليك' : ($total['opening'] < 0 ? 'لك' : 'مسدد') }}<br>{{ Money::format($total['opening'], $currency) }}</td>
                <td>{{ Money::format($total['debts'], $currency) }}</td>
                <td>{{ Money::format($total['payments'], $currency) }}</td>
                <td>{{ $total['balance'] > 0 ? 'عليك' : ($total['balance'] < 0 ? 'لك' : 'مسدد') }}<br>{{ Money::format($total['balance'], $currency) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <div class="note">كل عملة مستقلة. الرصيد الموجب عليك، والسالب لك. العمليات الملغاة ظاهرة للتدقيق ومستبعدة من الأرصدة.</div>
    <h2>تفاصيل الحركات</h2>
    <div class="meta">جميع العمليات مرتبة من الأقدم إلى الأحدث؛ الرصيد التراكمي خاص بعملة كل حركة.</div>
</div>
