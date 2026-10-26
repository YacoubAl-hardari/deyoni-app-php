<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;

class PeriodFilter
{
    public static function preset(string $period): array
    {
        return match ($period) {
            'اليوم' => [today()->toDateString(), today()->toDateString()],
            'هذا الشهر' => [today()->startOfMonth()->toDateString(), today()->toDateString()],
            'آخر 3 أشهر' => [today()->subMonthsNoOverflow(3)->toDateString(), today()->toDateString()],
            default => ['', ''],
        };
    }

    /** @return array{from: ?string, to: ?string} */
    public static function dates(string $from = '', string $to = ''): array
    {
        return Validator::make(['from' => $from ?: null, 'to' => $to ?: null], [
            'from' => 'nullable|date_format:Y-m-d',
            'to' => 'nullable|date_format:Y-m-d'.($from !== '' ? '|after_or_equal:from' : ''),
        ], ['to.after_or_equal' => 'تاريخ النهاية يجب أن يكون بعد تاريخ البداية.'])->validate();
    }

    public static function apply(Builder $query, string $from = '', string $to = ''): Builder
    {
        $dates = self::dates($from, $to);

        return $query->when($dates['from'], fn (Builder $query, string $date) => $query->where('occurred_at', '>=', $date))
            ->when($dates['to'], fn (Builder $query, string $date) => $query->where('occurred_at', '<=', $date));
    }
}
