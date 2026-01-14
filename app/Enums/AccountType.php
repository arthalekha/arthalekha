<?php

namespace App\Enums;

enum AccountType: string
{
    case Cash = 'cash';
    case Savings = 'savings';
    case CreditCard = 'credit_card';
    case Wallet = 'wallet';
    case Investment = 'investment';
    case Loan = 'loan';
    case Other = 'other';

    public function shortCode(): string
    {
        return match ($this) {
            self::Cash => 'CA',
            self::Savings => 'SB',
            self::CreditCard => 'CC',
            self::Wallet => 'WL',
            self::Investment => 'IN',
            self::Loan => 'LN',
            self::Other => 'OT',
        };
    }
}
