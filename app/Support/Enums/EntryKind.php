<?php

namespace App\Support\Enums;

enum EntryKind: string
{
    case Debt = 'debt';
    case Payment = 'payment';

    public function label(): string
    {
        return $this === self::Debt ? 'دين جديد' : 'سداد';
    }
}
