<?php

namespace App\Enums;

use Carbon\Carbon;

enum Frequency: string
{
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Biweekly = 'biweekly';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Yearly = 'yearly';

    public function addToDate(Carbon $date): Carbon
    {
        return match ($this) {
            self::Daily => $date->copy()->addDay(),
            self::Weekly => $date->copy()->addWeek(),
            self::Biweekly => $date->copy()->addWeeks(2),
            self::Monthly => $date->copy()->addMonth(),
            self::Quarterly => $date->copy()->addMonths(3),
            self::Yearly => $date->copy()->addYear(),
        };
    }
}
