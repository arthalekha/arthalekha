<?php

use App\Data\AccountData\CreditCardAccountData;
use App\Data\AccountData\SavingsAccountData;
use App\Enums\AccountType;
use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('SavingsAccountData', function () {
    it('can be created from array', function () {
        $data = SavingsAccountData::fromArray([
            'rate_of_interest' => 5.5,
            'interest_frequency' => 'monthly',
            'average_balance_frequency' => 'quarterly',
            'average_balance_amount' => 10000.00,
        ]);

        expect($data->rateOfInterest)->toBe(5.5)
            ->and($data->interestFrequency)->toBe('monthly')
            ->and($data->averageBalanceFrequency)->toBe('quarterly')
            ->and($data->averageBalanceAmount)->toBe(10000.00);
    });

    it('can be converted to array', function () {
        $data = new SavingsAccountData(
            rateOfInterest: 5.5,
            interestFrequency: 'monthly',
            averageBalanceFrequency: 'quarterly',
            averageBalanceAmount: 10000.00,
        );

        expect($data->toArray())->toBe([
            'rate_of_interest' => 5.5,
            'interest_frequency' => 'monthly',
            'average_balance_frequency' => 'quarterly',
            'average_balance_amount' => 10000.00,
        ]);
    });

    it('handles null values', function () {
        $data = SavingsAccountData::fromArray([]);

        expect($data->rateOfInterest)->toBeNull()
            ->and($data->interestFrequency)->toBeNull()
            ->and($data->averageBalanceFrequency)->toBeNull()
            ->and($data->averageBalanceAmount)->toBeNull();
    });
});

describe('CreditCardAccountData', function () {
    it('can be created from array', function () {
        $data = CreditCardAccountData::fromArray([
            'rate_of_interest' => 24.0,
            'interest_frequency' => 'monthly',
            'bill_generated_on' => 15,
            'repayment_of_bill_after_days' => 20,
        ]);

        expect($data->rateOfInterest)->toBe(24.0)
            ->and($data->interestFrequency)->toBe('monthly')
            ->and($data->billGeneratedOn)->toBe(15)
            ->and($data->repaymentOfBillAfterDays)->toBe(20);
    });

    it('can be converted to array', function () {
        $data = new CreditCardAccountData(
            rateOfInterest: 24.0,
            interestFrequency: 'monthly',
            billGeneratedOn: 15,
            repaymentOfBillAfterDays: 20,
        );

        expect($data->toArray())->toBe([
            'rate_of_interest' => 24.0,
            'interest_frequency' => 'monthly',
            'bill_generated_on' => 15,
            'repayment_of_bill_after_days' => 20,
        ]);
    });

    it('handles null values', function () {
        $data = CreditCardAccountData::fromArray([]);

        expect($data->rateOfInterest)->toBeNull()
            ->and($data->interestFrequency)->toBeNull()
            ->and($data->billGeneratedOn)->toBeNull()
            ->and($data->repaymentOfBillAfterDays)->toBeNull();
    });
});

describe('Account typed data', function () {
    it('returns SavingsAccountData for savings account', function () {
        $account = Account::factory()->ofType(AccountType::Savings)->create([
            'data' => [
                'rate_of_interest' => 5.5,
                'interest_frequency' => 'monthly',
            ],
        ]);

        $typedData = $account->getTypedData();

        expect($typedData)->toBeInstanceOf(SavingsAccountData::class)
            ->and($typedData->rateOfInterest)->toBe(5.5)
            ->and($typedData->interestFrequency)->toBe('monthly');
    });

    it('returns CreditCardAccountData for credit card account', function () {
        $account = Account::factory()->ofType(AccountType::CreditCard)->create([
            'data' => [
                'rate_of_interest' => 24.0,
                'bill_generated_on' => 15,
            ],
        ]);

        $typedData = $account->getTypedData();

        expect($typedData)->toBeInstanceOf(CreditCardAccountData::class)
            ->and($typedData->rateOfInterest)->toBe(24.0)
            ->and($typedData->billGeneratedOn)->toBe(15);
    });

    it('returns null for account types without typed data', function () {
        $account = Account::factory()->ofType(AccountType::Cash)->create();

        expect($account->getTypedData())->toBeNull();
    });

    it('can set typed data on account', function () {
        $account = Account::factory()->ofType(AccountType::Savings)->create();

        $data = new SavingsAccountData(
            rateOfInterest: 6.0,
            interestFrequency: 'quarterly',
            averageBalanceFrequency: 'monthly',
            averageBalanceAmount: 5000.00,
        );

        $account->setTypedData($data);
        $account->save();

        $account->refresh();

        expect($account->data)->toEqual([
            'rate_of_interest' => 6.0,
            'interest_frequency' => 'quarterly',
            'average_balance_frequency' => 'monthly',
            'average_balance_amount' => 5000.00,
        ]);
    });
});
