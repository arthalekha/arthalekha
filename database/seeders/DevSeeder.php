<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Enums\Frequency;
use App\Models\Account;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Person;
use App\Models\RecurringExpense;
use App\Models\RecurringIncome;
use App\Models\RecurringTransfer;
use App\Models\Transfer;
use App\Models\User;
use Database\Factories\TagFactory;
use Illuminate\Database\Seeder;
use SourcedOpen\Tags\Models\Tag;

class DevSeeder extends Seeder
{
    public function run(): void
    {
        $user1 = User::factory()->create([
            'name' => 'Aarav Sharma',
            'email' => 'user1@example.com',
        ]);

        $user2 = User::factory()->create([
            'name' => 'Priya Patel',
            'email' => 'user2@example.com',
        ]);

        $tags = $this->createTags();

        $this->seedUserData($user1, $tags);
        $this->seedUserData($user2, $tags);
    }

    /**
     * @return array<string, Tag>
     */
    private function createTags(): array
    {
        $tagDefinitions = [
            'Groceries' => '#22c55e',
            'Rent' => '#ef4444',
            'Salary' => '#3b82f6',
            'Utilities' => '#f59e0b',
            'Transport' => '#8b5cf6',
            'Dining' => '#ec4899',
            'Entertainment' => '#06b6d4',
            'Medical' => '#f43f5e',
            'Insurance' => '#64748b',
            'Freelance' => '#10b981',
            'Subscription' => '#a855f7',
            'Investment' => '#0ea5e9',
        ];

        $tags = [];
        foreach ($tagDefinitions as $name => $color) {
            $tags[$name] = TagFactory::new()->create([
                'name' => $name,
                'color' => $color,
            ]);
        }

        return $tags;
    }

    /**
     * @param  array<string, Tag>  $tags
     */
    private function seedUserData(User $user, array $tags): void
    {
        $accounts = $this->createAccounts($user);
        $persons = $this->createPersons();

        $this->createIncomes($user, $accounts, $persons, $tags);
        $this->createExpenses($user, $accounts, $persons, $tags);
        $this->createTransfers($user, $accounts, $tags);
        $this->createRecurringIncomes($user, $accounts, $persons, $tags);
        $this->createRecurringExpenses($user, $accounts, $persons, $tags);
        $this->createRecurringTransfers($user, $accounts);
    }

    /**
     * @return array<string, Account>
     */
    private function createAccounts(User $user): array
    {
        return [
            'hdfc_savings' => Account::factory()->forUser($user)->create([
                'name' => 'HDFC',
                'identifier' => '501001234567',
                'account_type' => AccountType::Savings,
                'initial_balance' => 150000,
                'current_balance' => 150000,
            ]),
            'icici_savings' => Account::factory()->forUser($user)->create([
                'name' => 'ICICI',
                'identifier' => '601009876543',
                'account_type' => AccountType::Savings,
                'initial_balance' => 85000,
                'current_balance' => 85000,
            ]),
            'axis_cc' => Account::factory()->forUser($user)->create([
                'name' => 'Axis Ace',
                'identifier' => '4567XXXXXXXX1234',
                'account_type' => AccountType::CreditCard,
                'initial_balance' => -12500,
                'current_balance' => -12500,
            ]),
            'hdfc_cc' => Account::factory()->forUser($user)->create([
                'name' => 'HDFC Millennia',
                'identifier' => '5234XXXXXXXX5678',
                'account_type' => AccountType::CreditCard,
                'initial_balance' => -8200,
                'current_balance' => -8200,
            ]),
            'cash' => Account::factory()->forUser($user)->create([
                'name' => 'Cash',
                'identifier' => null,
                'account_type' => AccountType::Cash,
                'initial_balance' => 5000,
                'current_balance' => 5000,
            ]),
            'paytm' => Account::factory()->forUser($user)->create([
                'name' => 'Paytm',
                'identifier' => null,
                'account_type' => AccountType::Wallet,
                'initial_balance' => 2500,
                'current_balance' => 2500,
            ]),
            'mf' => Account::factory()->forUser($user)->create([
                'name' => 'Groww MF',
                'identifier' => null,
                'account_type' => AccountType::Investment,
                'initial_balance' => 200000,
                'current_balance' => 200000,
            ]),
        ];
    }

    /**
     * @return array<int, Person>
     */
    private function createPersons(): array
    {
        return [
            Person::factory()->create(['name' => 'TCS', 'nick_name' => 'Employer']),
            Person::factory()->create(['name' => 'Rahul Verma', 'nick_name' => 'Landlord']),
            Person::factory()->create(['name' => 'BigBasket', 'nick_name' => null]),
            Person::factory()->create(['name' => 'Swiggy', 'nick_name' => null]),
            Person::factory()->create(['name' => 'Apollo Pharmacy', 'nick_name' => null]),
            Person::factory()->create(['name' => 'Jio', 'nick_name' => 'Internet']),
            Person::factory()->create(['name' => 'Netflix', 'nick_name' => null]),
        ];
    }

    /**
     * @param  array<string, Account>  $accounts
     * @param  array<int, Person>  $persons
     * @param  array<string, Tag>  $tags
     */
    private function createIncomes(User $user, array $accounts, array $persons, array $tags): void
    {
        $incomeData = [
            ['description' => 'Monthly Salary', 'amount' => 85000, 'person' => 0, 'account' => 'hdfc_savings', 'tags' => ['Salary']],
            ['description' => 'Freelance Web Project', 'amount' => 25000, 'person' => null, 'account' => 'icici_savings', 'tags' => ['Freelance']],
            ['description' => 'Freelance API Integration', 'amount' => 15000, 'person' => null, 'account' => 'hdfc_savings', 'tags' => ['Freelance']],
            ['description' => 'Monthly Salary', 'amount' => 85000, 'person' => 0, 'account' => 'hdfc_savings', 'tags' => ['Salary']],
            ['description' => 'Fixed Deposit Interest', 'amount' => 3200, 'person' => null, 'account' => 'icici_savings', 'tags' => ['Investment']],
            ['description' => 'Monthly Salary', 'amount' => 85000, 'person' => 0, 'account' => 'hdfc_savings', 'tags' => ['Salary']],
            ['description' => 'Cashback Reward', 'amount' => 450, 'person' => null, 'account' => 'paytm', 'tags' => []],
            ['description' => 'Freelance Mobile App', 'amount' => 40000, 'person' => null, 'account' => 'icici_savings', 'tags' => ['Freelance']],
        ];

        foreach ($incomeData as $data) {
            $income = Income::factory()
                ->for($accounts[$data['account']])
                ->create([
                    'user_id' => $user->id,
                    'person_id' => $data['person'] !== null ? $persons[$data['person']]->id : null,
                    'description' => $data['description'],
                    'amount' => $data['amount'],
                    'transacted_at' => now()->subDays(rand(1, 90)),
                ]);

            $tagIds = collect($data['tags'])->map(fn ($name) => $tags[$name]->id)->toArray();
            if ($tagIds) {
                $income->tags()->sync($tagIds);
            }
        }

        Income::factory(40)
            ->recycle(collect($accounts)->values()->all())
            ->recycle($persons)
            ->create(['user_id' => $user->id]);
    }

    /**
     * @param  array<string, Account>  $accounts
     * @param  array<int, Person>  $persons
     * @param  array<string, Tag>  $tags
     */
    private function createExpenses(User $user, array $accounts, array $persons, array $tags): void
    {
        $expenseData = [
            ['description' => 'Rent Payment', 'amount' => 18000, 'person' => 1, 'account' => 'hdfc_savings', 'tags' => ['Rent']],
            ['description' => 'Monthly Groceries', 'amount' => 4500, 'person' => 2, 'account' => 'axis_cc', 'tags' => ['Groceries']],
            ['description' => 'Electricity Bill', 'amount' => 1800, 'person' => null, 'account' => 'hdfc_savings', 'tags' => ['Utilities']],
            ['description' => 'Jio Broadband', 'amount' => 999, 'person' => 5, 'account' => 'hdfc_savings', 'tags' => ['Utilities', 'Subscription']],
            ['description' => 'Dinner at Barbeque Nation', 'amount' => 3200, 'person' => null, 'account' => 'hdfc_cc', 'tags' => ['Dining', 'Entertainment']],
            ['description' => 'Netflix Subscription', 'amount' => 649, 'person' => 6, 'account' => 'axis_cc', 'tags' => ['Entertainment', 'Subscription']],
            ['description' => 'Metro Card Recharge', 'amount' => 500, 'person' => null, 'account' => 'paytm', 'tags' => ['Transport']],
            ['description' => 'Medicines', 'amount' => 1250, 'person' => 4, 'account' => 'cash', 'tags' => ['Medical']],
            ['description' => 'Petrol', 'amount' => 3000, 'person' => null, 'account' => 'hdfc_cc', 'tags' => ['Transport']],
            ['description' => 'Health Insurance Premium', 'amount' => 15000, 'person' => null, 'account' => 'hdfc_savings', 'tags' => ['Insurance', 'Medical']],
            ['description' => 'Rent Payment', 'amount' => 18000, 'person' => 1, 'account' => 'hdfc_savings', 'tags' => ['Rent']],
            ['description' => 'Weekly Groceries', 'amount' => 2200, 'person' => 2, 'account' => 'axis_cc', 'tags' => ['Groceries']],
            ['description' => 'Water Bill', 'amount' => 350, 'person' => null, 'account' => 'hdfc_savings', 'tags' => ['Utilities']],
            ['description' => 'Swiggy Food Order', 'amount' => 680, 'person' => 3, 'account' => 'paytm', 'tags' => ['Dining']],
            ['description' => 'Uber Rides', 'amount' => 1100, 'person' => null, 'account' => 'axis_cc', 'tags' => ['Transport']],
        ];

        foreach ($expenseData as $data) {
            $expense = Expense::factory()
                ->for($accounts[$data['account']])
                ->create([
                    'user_id' => $user->id,
                    'person_id' => $data['person'] !== null ? $persons[$data['person']]->id : null,
                    'description' => $data['description'],
                    'amount' => $data['amount'],
                    'transacted_at' => now()->subDays(rand(1, 90)),
                ]);

            $tagIds = collect($data['tags'])->map(fn ($name) => $tags[$name]->id)->toArray();
            if ($tagIds) {
                $expense->tags()->sync($tagIds);
            }
        }

        Expense::factory(50)
            ->recycle(collect($accounts)->values()->all())
            ->recycle($persons)
            ->create(['user_id' => $user->id]);
    }

    /**
     * @param  array<string, Account>  $accounts
     * @param  array<string, Tag>  $tags
     */
    private function createTransfers(User $user, array $accounts, array $tags): void
    {
        $transferData = [
            ['description' => 'Credit Card Bill Payment', 'amount' => 12500, 'from' => 'hdfc_savings', 'to' => 'axis_cc', 'tags' => []],
            ['description' => 'SIP Investment', 'amount' => 10000, 'from' => 'hdfc_savings', 'to' => 'mf', 'tags' => ['Investment']],
            ['description' => 'Wallet Top-up', 'amount' => 2000, 'from' => 'icici_savings', 'to' => 'paytm', 'tags' => []],
            ['description' => 'HDFC CC Bill Payment', 'amount' => 8200, 'from' => 'icici_savings', 'to' => 'hdfc_cc', 'tags' => []],
            ['description' => 'Savings Transfer', 'amount' => 20000, 'from' => 'hdfc_savings', 'to' => 'icici_savings', 'tags' => []],
            ['description' => 'Cash Withdrawal', 'amount' => 5000, 'from' => 'hdfc_savings', 'to' => 'cash', 'tags' => []],
            ['description' => 'SIP Investment', 'amount' => 10000, 'from' => 'hdfc_savings', 'to' => 'mf', 'tags' => ['Investment']],
        ];

        foreach ($transferData as $data) {
            $transfer = Transfer::factory()
                ->fromAccount($accounts[$data['from']])
                ->toAccount($accounts[$data['to']])
                ->create([
                    'user_id' => $user->id,
                    'description' => $data['description'],
                    'amount' => $data['amount'],
                    'transacted_at' => now()->subDays(rand(1, 60)),
                ]);

            $tagIds = collect($data['tags'])->map(fn ($name) => $tags[$name]->id)->toArray();
            if ($tagIds) {
                $transfer->tags()->sync($tagIds);
            }
        }
    }

    /**
     * @param  array<string, Account>  $accounts
     * @param  array<int, Person>  $persons
     * @param  array<string, Tag>  $tags
     */
    private function createRecurringIncomes(User $user, array $accounts, array $persons, array $tags): void
    {
        $recurringIncomeData = [
            ['description' => 'Monthly Salary', 'amount' => 85000, 'person' => 0, 'account' => 'hdfc_savings', 'frequency' => Frequency::Monthly, 'tags' => ['Salary'], 'next' => now()->addDays(5)],
            ['description' => 'Freelance Retainer', 'amount' => 20000, 'person' => null, 'account' => 'icici_savings', 'frequency' => Frequency::Monthly, 'tags' => ['Freelance'], 'next' => now()->addDays(12)],
            ['description' => 'FD Interest', 'amount' => 3200, 'person' => null, 'account' => 'icici_savings', 'frequency' => Frequency::Quarterly, 'tags' => ['Investment'], 'next' => now()->addMonth()],
            // Without account - should appear on dashboard
            ['description' => 'Side Project Payment', 'amount' => 8000, 'person' => null, 'account' => null, 'frequency' => Frequency::Monthly, 'tags' => ['Freelance'], 'next' => now()->subDays(2)],
            ['description' => 'Dividend Income', 'amount' => 1500, 'person' => null, 'account' => null, 'frequency' => Frequency::Quarterly, 'tags' => ['Investment'], 'next' => now()->subDay()],
        ];

        foreach ($recurringIncomeData as $data) {
            $recurringIncome = RecurringIncome::factory()
                ->forUser($user)
                ->create([
                    'account_id' => $data['account'] ? $accounts[$data['account']]->id : null,
                    'person_id' => $data['person'] !== null ? $persons[$data['person']]->id : null,
                    'description' => $data['description'],
                    'amount' => $data['amount'],
                    'frequency' => $data['frequency'],
                    'next_transaction_at' => $data['next'],
                    'remaining_recurrences' => null,
                ]);

            $tagIds = collect($data['tags'])->map(fn ($name) => $tags[$name]->id)->toArray();
            if ($tagIds) {
                $recurringIncome->tags()->sync($tagIds);
            }
        }
    }

    /**
     * @param  array<string, Account>  $accounts
     * @param  array<int, Person>  $persons
     * @param  array<string, Tag>  $tags
     */
    private function createRecurringExpenses(User $user, array $accounts, array $persons, array $tags): void
    {
        $recurringExpenseData = [
            ['description' => 'Rent', 'amount' => 18000, 'person' => 1, 'account' => 'hdfc_savings', 'frequency' => Frequency::Monthly, 'tags' => ['Rent'], 'next' => now()->addDays(1), 'recurrences' => null],
            ['description' => 'Netflix Subscription', 'amount' => 649, 'person' => 6, 'account' => 'axis_cc', 'frequency' => Frequency::Monthly, 'tags' => ['Entertainment', 'Subscription'], 'next' => now()->addDays(8), 'recurrences' => null],
            ['description' => 'Jio Broadband', 'amount' => 999, 'person' => 5, 'account' => 'hdfc_savings', 'frequency' => Frequency::Monthly, 'tags' => ['Utilities', 'Subscription'], 'next' => now()->addDays(15), 'recurrences' => null],
            ['description' => 'Health Insurance Premium', 'amount' => 15000, 'person' => null, 'account' => 'hdfc_savings', 'frequency' => Frequency::Yearly, 'tags' => ['Insurance', 'Medical'], 'next' => now()->addMonths(3), 'recurrences' => null],
            ['description' => 'Term Insurance', 'amount' => 12000, 'person' => null, 'account' => 'icici_savings', 'frequency' => Frequency::Yearly, 'tags' => ['Insurance'], 'next' => now()->addMonths(6), 'recurrences' => null],
            // Without account - should appear on dashboard
            ['description' => 'Magazine Subscription', 'amount' => 300, 'person' => null, 'account' => null, 'frequency' => Frequency::Monthly, 'tags' => ['Subscription'], 'next' => now()->subDays(3), 'recurrences' => 10],
            ['description' => 'Society Maintenance', 'amount' => 3500, 'person' => null, 'account' => null, 'frequency' => Frequency::Monthly, 'tags' => ['Utilities'], 'next' => now()->subDay(), 'recurrences' => null],
            ['description' => 'Gym Membership', 'amount' => 2000, 'person' => null, 'account' => null, 'frequency' => Frequency::Monthly, 'tags' => ['Medical'], 'next' => now()->subDays(5), 'recurrences' => 6],
        ];

        foreach ($recurringExpenseData as $data) {
            $recurringExpense = RecurringExpense::factory()
                ->forUser($user)
                ->create([
                    'account_id' => $data['account'] ? $accounts[$data['account']]->id : null,
                    'person_id' => $data['person'] !== null ? $persons[$data['person']]->id : null,
                    'description' => $data['description'],
                    'amount' => $data['amount'],
                    'frequency' => $data['frequency'],
                    'next_transaction_at' => $data['next'],
                    'remaining_recurrences' => $data['recurrences'],
                ]);

            $tagIds = collect($data['tags'])->map(fn ($name) => $tags[$name]->id)->toArray();
            if ($tagIds) {
                $recurringExpense->tags()->sync($tagIds);
            }
        }
    }

    /**
     * @param  array<string, Account>  $accounts
     */
    private function createRecurringTransfers(User $user, array $accounts): void
    {
        RecurringTransfer::factory()->forUser($user)->create([
            'creditor_id' => $accounts['mf']->id,
            'debtor_id' => $accounts['hdfc_savings']->id,
            'description' => 'Monthly SIP',
            'amount' => 10000,
            'frequency' => Frequency::Monthly,
            'next_transaction_at' => now()->addDays(3),
            'remaining_recurrences' => null,
        ]);

        RecurringTransfer::factory()->forUser($user)->create([
            'creditor_id' => $accounts['axis_cc']->id,
            'debtor_id' => $accounts['hdfc_savings']->id,
            'description' => 'Credit Card Auto-Pay',
            'amount' => 15000,
            'frequency' => Frequency::Monthly,
            'next_transaction_at' => now()->addDays(10),
            'remaining_recurrences' => null,
        ]);
    }
}
