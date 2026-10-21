<?php

namespace App\Support\Money;

use App\Support\Enums\Currency;
use Illuminate\Validation\ValidationException;

final class Money
{
    public const MAX_MINOR = 1_000_000_000_000;

    public static function parse(string $amount, Currency $currency): int
    {
        $amount = strtr(trim($amount), ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9', '٫' => '.']);
        $pattern = $currency === Currency::Yer ? '/^[0-9]{1,13}$/' : '/^[0-9]{1,13}(?:\.[0-9]{1,2})?$/';
        if (! preg_match($pattern, $amount)) {
            throw ValidationException::withMessages(['amount' => 'أدخل مبلغًا صحيحًا موجبًا؛ الهللات متاحة للريال السعودي فقط.']);
        }
        [$whole, $fraction] = array_pad(explode('.', $amount), 2, '');
        $minor = (int) $whole * $currency->scale() + ($currency === Currency::Sar ? (int) str_pad($fraction, 2, '0') : 0);
        if ($minor < 1 || $minor > self::MAX_MINOR) {
            throw ValidationException::withMessages(['amount' => 'المبلغ يجب أن يكون أكبر من صفر وضمن الحد المسموح.']);
        }

        return $minor;
    }

    public static function format(int $minor, Currency $currency): string
    {
        $minor = abs($minor);
        $whole = (string) intdiv($minor, $currency->scale());
        $whole = preg_replace('/\B(?=(\d{3})+(?!\d))/', ',', $whole);

        return $whole.($currency === Currency::Sar ? '.'.str_pad((string) ($minor % 100), 2, '0', STR_PAD_LEFT) : '').' '.$currency->label();
    }

    public static function input(int $minor, Currency $currency): string
    {
        return (string) intdiv($minor, $currency->scale()).($currency === Currency::Sar ? '.'.str_pad((string) ($minor % 100), 2, '0', STR_PAD_LEFT) : '');
    }
}
