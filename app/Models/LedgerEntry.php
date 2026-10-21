<?php

namespace App\Models;

use App\Support\Enums\Currency;
use App\Support\Enums\EntryKind;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LedgerEntry extends Model
{
    protected $guarded = ['id'];

    protected function occurredAt(): Attribute
    {
        return Attribute::make(set: fn (string $value): string => substr($value, 0, 10));
    }

    protected function casts(): array
    {
        return ['kind' => EntryKind::class, 'currency' => Currency::class, 'amount_minor' => 'integer', 'occurred_at' => 'date', 'voided_at' => 'datetime'];
    }

    public function creditor(): BelongsTo
    {
        return $this->belongsTo(Creditor::class);
    }

    public function replaces(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaces_entry_id');
    }
}
