<?php

namespace App\Models;

use App\Support\Enums\Currency;
use Database\Factories\PaymentPromiseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentPromise extends Model
{
    /** @use HasFactory<PaymentPromiseFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function dueAt(): Attribute
    {
        return Attribute::make(set: fn (string $value): string => substr($value, 0, 10));
    }

    protected function casts(): array
    {
        return ['due_at' => 'date', 'currency' => Currency::class, 'amount_minor' => 'integer'];
    }

    public function creditor(): BelongsTo
    {
        return $this->belongsTo(Creditor::class);
    }

    public function ledgerEntry(): BelongsTo
    {
        return $this->belongsTo(LedgerEntry::class);
    }
}
