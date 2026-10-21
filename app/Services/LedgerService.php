<?php

namespace App\Services;

use App\Models\AuditEvent;
use App\Models\Creditor;
use App\Models\LedgerEntry;
use App\Support\Enums\Currency;
use App\Support\Money\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LedgerService
{
    public function validated(array $input): array
    {
        $data = Validator::make($input, [
            'creditor_id' => 'required|integer|exists:creditors,id', 'kind' => 'required|in:debt,payment',
            'currency' => 'required|in:YER,SAR', 'amount' => 'required|string',
            'occurred_at' => 'required|date_format:Y-m-d', 'notes' => 'nullable|string|max:2000',
            'request_id' => 'required|uuid',
        ], ['occurred_at.date_format' => 'أدخل التاريخ بصيغة سنة-شهر-يوم.', 'creditor_id.required' => 'اختر الجهة.'])->validate();
        $data['amount_minor'] = Money::parse($data['amount'], Currency::from($data['currency']));
        unset($data['amount']);

        return $data;
    }

    public function record(array $input): LedgerEntry
    {
        $data = $this->validated($input);

        return DB::transaction(function () use ($data): LedgerEntry {
            $existing = LedgerEntry::where('request_id', $data['request_id'])->first();
            if ($existing) {
                $this->assertSameRequest($existing, $data);

                return $existing;
            }
            if (Creditor::findOrFail($data['creditor_id'])->archived_at) {
                throw ValidationException::withMessages(['creditor_id' => 'استرجع الجهة من الأرشيف أولًا.']);
            }

            return LedgerEntry::create($data);
        }, 3);
    }

    public function correct(int $entryId, array $input, string $reason): LedgerEntry
    {
        $data = $this->validated($input);
        $this->validateReason($reason);

        return DB::transaction(function () use ($entryId, $data, $reason): LedgerEntry {
            $old = LedgerEntry::findOrFail($entryId);
            $existing = LedgerEntry::where('request_id', $data['request_id'])->first();
            if ($existing && $existing->replaces_entry_id === $entryId) {
                $this->assertSameRequest($existing, $data);

                return $existing;
            }
            if ($old->voided_at || $existing || $old->creditor_id !== (int) $data['creditor_id']) {
                throw ValidationException::withMessages(['entry' => 'لا يمكن تصحيح هذه الحركة.']);
            }
            $before = $old->toArray();
            $old->update(['voided_at' => now()]);
            $new = LedgerEntry::create([...$data, 'replaces_entry_id' => $old->id]);
            $this->audit($old, 'corrected', $before, $new->toArray(), $reason);

            return $new;
        }, 3);
    }

    public function void(int $entryId, string $reason): void
    {
        $this->validateReason($reason);
        DB::transaction(function () use ($entryId, $reason): void {
            $entry = LedgerEntry::findOrFail($entryId);
            if ($entry->voided_at) {
                return;
            }
            $before = $entry->toArray();
            $entry->update(['voided_at' => now()]);
            $this->audit($entry, 'voided', $before, $entry->toArray(), $reason);
        }, 3);
    }

    private function assertSameRequest(LedgerEntry $entry, array $data): void
    {
        foreach (['creditor_id', 'kind', 'currency', 'amount_minor', 'occurred_at', 'notes'] as $key) {
            $actual = $key === 'occurred_at' ? $entry->occurred_at->format('Y-m-d') : $entry->getRawOriginal($key);
            if ((string) $actual !== (string) ($data[$key] ?? null)) {
                throw ValidationException::withMessages(['entry' => 'معرّف الحفظ مستخدم لعملية مختلفة.']);
            }
        }
    }

    private function validateReason(string $reason): void
    {
        Validator::make(['reason' => trim($reason)], ['reason' => 'required|string|max:1000'], ['reason.required' => 'أدخل سبب التصحيح أو الإلغاء.'])->validate();
    }

    private function audit(LedgerEntry $entry, string $action, array $before, array $after, string $reason): void
    {
        AuditEvent::create(['entity_type' => 'ledger_entry', 'entity_id' => $entry->id, 'action' => $action,
            'before_data' => $before, 'after_data' => $after, 'reason' => trim($reason), 'created_at' => now()]);
    }
}
