<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Database\Seeder;

class TransferSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            $accounts = Account::where('user_id', $user->id)->get();

            if ($accounts->count() >= 2) {
                Transfer::factory()
                    ->count(5)
                    ->forUser($user)
                    ->sequence(fn () => [
                        'creditor_id' => $accounts->random()->id,
                        'debtor_id' => $accounts->random()->id,
                    ])
                    ->create();
            }
        }
    }
}
