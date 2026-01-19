<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Person;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DevSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::factory(2)->createMany([
            ['email' => 'user1@example.com'],
            ['email' => 'user2@example.com'],
        ]);

        $a1 = Account::factory(5)
            ->ofType(AccountType::Savings)
            ->recycle($users)
            ->create();

        $a2 = Account::factory(5)
            ->ofType(AccountType::CreditCard)
            ->recycle($users)
            ->create();

        $a3 = Account::factory(5)
            ->recycle($users)
            ->create();

        $accounts = $a1->merge($a2)->merge($a3);

        $persons = Person::factory(7)->create();

        $accounts->each(function (Account $account) use ($persons, $accounts) {
            Income::factory(100)
                ->for($account)
                ->recycle($persons)
                ->create([
                    'user_id' => $account->user_id,
                ]);

            Expense::factory(100)
                ->for($account)
                ->recycle($persons)
                ->create([
                    'user_id' => $account->user_id,
                ]);

            $otherAccounts = $accounts->keyBy('id')->except($account->id);
            Transfer::factory(2)
                ->recycle($otherAccounts)
                ->forCreditor($account)
                ->create([
                    'user_id' => $account->user_id,
                ]);

            Transfer::factory(2)
                ->recycle($otherAccounts)
                ->forDebtor($account)
                ->create([
                    'user_id' => $account->user_id,
                ]);
        });

        Artisan::call('accounts:backfill-balances');
    }
}
