<?php

namespace App\Enums;

enum AccountType: string
{
    case Cash = 'cash';
    case Bank = 'bank';
    case CreditCard = 'credit_card';
    case Wallet = 'wallet';
    case Investment = 'investment';
    case Loan = 'loan';
    case Other = 'other';
}
