<?php

namespace App\Services;

use App\Models\Creditor;
use App\Models\LedgerEntry;
use App\Models\PaymentPromise;
use App\Support\Enums\Currency;
use App\Support\Money\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentPromiseService
{
    public function save(array $input, ?int $id = null): PaymentPromise
    {
        $data = Validator::make($input, [
            'creditor_id' => 'required|integer|exists:creditors,id',
            'direction' => 'required|in:payable,receivable', 'currency' => 'required|in:YER,SAR',
            'amount' => 'required|string', 'due_at' => 'required|date_format:Y-m-d', 'notes' => 'nullable|string|max:2000',
        ])->validate();
        $data['amount_minor'] = Money::parse($data['amount'], Currency::from($data['currency']));
        unset($data['amount']);

        return DB::transaction(function () use ($data, $id): PaymentPromise {
            $promise = $id ? PaymentPromise::lockForUpdate()->findOrFail($id) : new PaymentPromise;
            if (($promise->exists && $promise->status !== 'pending') || Creditor::findOrFail($data['creditor_id'])->archived_at) {
                throw ValidationException::withMessages(['promise' => 'الموعد مغلق أو الجهة مؤرشفة.']);
            }
            $promise->fill($data)->save();

            return $promise;
        });
    }

    public function settle(int $id, string $date): LedgerEntry
    {
        Validator::make(['date' => $date], ['date' => 'required|date_format:Y-m-d'])->validate();

        return DB::transaction(function () use ($id, $date): LedgerEntry {
            $promise = PaymentPromise::lockForUpdate()->findOrFail($id);
            if ($promise->status === 'fulfilled') {
                return $promise->ledgerEntry()->firstOrFail();
            }
            if ($promise->status !== 'pending') {
                throw ValidationException::withMessages(['promise' => 'هذا الموعد ملغى.']);
            }
            $entry = app(LedgerService::class)->record([
                'creditor_id' => $promise->creditor_id, 'kind' => 'payment', 'direction' => $promise->direction,
                'currency' => $promise->currency->value, 'amount' => Money::input($promise->amount_minor, $promise->currency),
                'occurred_at' => $date, 'notes' => $promise->notes, 'request_id' => (string) Str::uuid(),
            ]);
            $promise->update(['status' => 'fulfilled', 'ledger_entry_id' => $entry->id]);

            return $entry;
        }, 3);
    }

    public function cancel(int $id): void
    {
        PaymentPromise::whereKey($id)->where('status', 'pending')->update(['status' => 'cancelled']);
    }
}
