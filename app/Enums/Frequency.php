<?php

namespace App\Enums;

use Carbon\CarbonInterface;

enum Frequency: string
{
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Biweekly = 'biweekly';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Yearly = 'yearly';

    public function addToDate(CarbonInterface $date): CarbonInterface
    {
        return match ($this) {
            self::Daily => $date->addDay(),
            self::Weekly => $date->addWeek(),
            self::Biweekly => $date->addWeeks(2),
            self::Monthly => $date->addMonth(),
            self::Quarterly => $date->addMonths(3),
            self::Yearly => $date->addYear(),
        };
    }

    public function startOfPeriod(?CarbonInterface $date = null): CarbonInterface
    {
        $date = $date ?? now();

        return match ($this) {
            self::Daily => $date->copy()->startOfDay(),
            self::Weekly => $date->copy()->startOfWeek(),
            self::Biweekly => $date->copy()->startOfWeek()->subWeek(),
            self::Monthly => $date->copy()->startOfMonth(),
            self::Quarterly => $date->copy()->startOfQuarter(),
            self::Yearly => $date->copy()->startOfYear(),
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Daily => 'Daily',
            self::Weekly => 'Weekly',
            self::Biweekly => 'Biweekly',
            self::Monthly => 'Monthly',
            self::Quarterly => 'Quarterly',
            self::Yearly => 'Yearly',
        };
    }
}
