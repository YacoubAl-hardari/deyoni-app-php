<?php

namespace App\Support\Enums;

enum Currency: string
{
    case Yer = 'YER';
    case Sar = 'SAR';

    public function label(): string
    {
        return $this === self::Yer ? 'ريال يمني' : 'ريال سعودي';
    }

    public function scale(): int
    {
        return $this === self::Yer ? 1 : 100;
    }
}
