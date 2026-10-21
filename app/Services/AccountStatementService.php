<?php

namespace App\Services;

use App\Models\Creditor;
use App\Models\LedgerEntry;
use App\Support\Enums\Currency;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Mpdf\Config\ConfigVariables;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class AccountStatementService
{
    /** @return array<string, array{debts: int, payments: int, balance: int}> */
    public function totals(Creditor $creditor): array
    {
        $result = [];
        foreach (Currency::cases() as $currency) {
            $row = $creditor->entries()->whereNull('voided_at')->where('currency', $currency->value)
                ->selectRaw("COALESCE(SUM(CASE WHEN kind = 'debt' THEN amount_minor ELSE 0 END), 0) AS debts, COALESCE(SUM(CASE WHEN kind = 'payment' THEN amount_minor ELSE 0 END), 0) AS payments")->first();
            $debts = (int) $row->debts;
            $payments = (int) $row->payments;
            $result[$currency->value] = ['debts' => $debts, 'payments' => $payments, 'balance' => $debts - $payments];
        }

        return $result;
    }

    public function create(Creditor $creditor): string
    {
        $directory = storage_path('app/private/statements');
        $temp = storage_path('app/private/pdf-cache');
        File::ensureDirectoryExists($directory);
        File::ensureDirectoryExists($temp);
        foreach (File::glob($directory.'/statement-*.pdf') as $old) {
            if (filemtime($old) < now()->subDay()->timestamp) {
                File::delete($old);
            }
        }
        $defaults = (new ConfigVariables)->getDefaults();
        $pdf = new Mpdf([
            'mode' => 'utf-8', 'format' => 'A4', 'default_font' => 'jannalt',
            'fontDir' => [...$defaults['fontDir'], resource_path('fonts')],
            'fontdata' => ['jannalt' => ['R' => 'JannaLT-Regular.ttf', 'B' => 'JannaLT-Bold.ttf', 'useOTL' => 0xFF, 'useKashida' => 50]],
            'margin_left' => 14, 'margin_right' => 14, 'margin_top' => 14, 'margin_bottom' => 20,
            'tempDir' => $temp, 'default_font_size' => 10,
        ]);
        $pdf->SetDirectionality('rtl');
        $pdf->SetTitle('كشف حساب - '.$creditor->name);
        $pdf->SetAuthor('ديوني');
        $pdf->SetHTMLFooter('<div dir="rtl" style="font-size:8px;color:#64748b;border-top:1px solid #dce4e8;padding-top:6px">ديوني · كشف حساب محلي <span style="float:left">صفحة {PAGENO} من {nbpg}</span></div>');
        $pdf->WriteHTML(view('native.statement-header', [
            'creditor' => $creditor, 'totals' => $this->totals($creditor), 'issuedAt' => now(),
        ])->render());
        $entries = LedgerEntry::where('creditor_id', $creditor->id)->orderBy('occurred_at')->orderBy('id');
        $balances = ['YER' => 0, 'SAR' => 0];
        $hasEntries = false;
        foreach ($entries->cursor()->chunk(150) as $chunk) {
            $rows = [];
            foreach ($chunk as $entry) {
                $hasEntries = true;
                if (! $entry->voided_at) {
                    $balances[$entry->currency->value] += $entry->kind->value === 'debt' ? $entry->amount_minor : -$entry->amount_minor;
                }
                $rows[] = ['entry' => $entry, 'balance' => $balances[$entry->currency->value]];
            }
            $pdf->WriteHTML(view('native.statement-rows', ['rows' => $rows])->render());
        }
        if (! $hasEntries) {
            $pdf->WriteHTML('<p style="padding:20px;text-align:center">لا توجد عمليات مسجلة لهذه الجهة.</p>');
        }
        $path = $directory.'/statement-'.Str::uuid().'.pdf';
        $pdf->Output($path, Destination::FILE);

        return $path;
    }
}
