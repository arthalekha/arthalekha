<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Seeder;

class ExpenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            $accounts = Account::where('user_id', $user->id)->get();

            if ($accounts->isNotEmpty()) {
                Expense::factory()
                    ->count(15)
                    ->forUser($user)
                    ->sequence(fn () => ['account_id' => $accounts->random()->id])
                    ->create();
            }
        }
    }
}
