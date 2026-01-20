<?php

use App\Models\Account;
use App\Models\Expense;
use App\Models\Person;
use App\Models\User;
use Database\Factories\TagFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use SourcedOpen\Tags\Models\Tag;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->account = Account::factory()->forUser($this->user)->create();
});

test('guest cannot access expenses index', function () {
    $this->get(route('expenses.index'))
        ->assertRedirect(route('login'));
});

test('authenticated user can view expenses index', function () {
    Expense::factory()->count(3)->forUser($this->user)->forAccount($this->account)->create();

    $this->actingAs($this->user)
        ->get(route('expenses.index'))
        ->assertSuccessful()
        ->assertViewIs('expenses.index')
        ->assertViewHas('expenses');
});

test('user can only see their own expenses', function () {
    $ownExpense = Expense::factory()->forUser($this->user)->forAccount($this->account)->create([
        'transacted_at' => now(),
    ]);
    $otherExpense = Expense::factory()->create([
        'transacted_at' => now(),
    ]);

    $this->actingAs($this->user)
        ->get(route('expenses.index'))
        ->assertSuccessful()
        ->assertSee($ownExpense->description)
        ->assertDontSee($otherExpense->description);
});

test('authenticated user can view create expense form', function () {
    $this->actingAs($this->user)
        ->get(route('expenses.create'))
        ->assertSuccessful()
        ->assertViewIs('expenses.create')
        ->assertViewHas('accounts')
        ->assertViewHas('people');
});

test('authenticated user can create an expense', function () {
    $expenseData = [
        'account_id' => $this->account->id,
        'description' => 'Test Expense',
        'transacted_at' => now()->format('Y-m-d H:i:s'),
        'amount' => 500.50,
    ];

    $this->actingAs($this->user)
        ->post(route('expenses.store'), $expenseData)
        ->assertRedirect(route('expenses.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('expenses', [
        'description' => 'Test Expense',
        'user_id' => $this->user->id,
        'account_id' => $this->account->id,
    ]);
});

test('expense can be associated with a person', function () {
    $person = Person::factory()->create();

    $expenseData = [
        'account_id' => $this->account->id,
        'person_id' => $person->id,
        'description' => 'Payment to person',
        'transacted_at' => now()->format('Y-m-d H:i:s'),
        'amount' => 250.00,
    ];

    $this->actingAs($this->user)
        ->post(route('expenses.store'), $expenseData)
        ->assertRedirect(route('expenses.index'));

    $this->assertDatabaseHas('expenses', [
        'description' => 'Payment to person',
        'person_id' => $person->id,
    ]);
});

test('creating an expense requires a description', function () {
    $this->actingAs($this->user)
        ->post(route('expenses.store'), [
            'account_id' => $this->account->id,
            'description' => '',
            'transacted_at' => now()->format('Y-m-d H:i:s'),
            'amount' => 100,
        ])
        ->assertSessionHasErrors('description');
});

test('creating an expense requires a valid amount', function () {
    $this->actingAs($this->user)
        ->post(route('expenses.store'), [
            'account_id' => $this->account->id,
            'description' => 'Test',
            'transacted_at' => now()->format('Y-m-d H:i:s'),
            'amount' => 0,
        ])
        ->assertSessionHasErrors('amount');
});

test('creating an expense requires users own account', function () {
    $otherAccount = Account::factory()->create();

    $this->actingAs($this->user)
        ->post(route('expenses.store'), [
            'account_id' => $otherAccount->id,
            'description' => 'Test',
            'transacted_at' => now()->format('Y-m-d H:i:s'),
            'amount' => 100,
        ])
        ->assertSessionHasErrors('account_id');
});

test('authenticated user can view their own expense', function () {
    $expense = Expense::factory()->forUser($this->user)->forAccount($this->account)->create();

    $this->actingAs($this->user)
        ->get(route('expenses.show', $expense))
        ->assertSuccessful()
        ->assertViewIs('expenses.show')
        ->assertViewHas('expense');
});

test('user cannot view another users expense', function () {
    $otherExpense = Expense::factory()->create();

    $this->actingAs($this->user)
        ->get(route('expenses.show', $otherExpense))
        ->assertForbidden();
});

test('authenticated user can view edit form for their own expense', function () {
    $expense = Expense::factory()->forUser($this->user)->forAccount($this->account)->create();

    $this->actingAs($this->user)
        ->get(route('expenses.edit', $expense))
        ->assertSuccessful()
        ->assertViewIs('expenses.edit')
        ->assertViewHas('expense');
});

test('authenticated user can update their own expense', function () {
    $expense = Expense::factory()->forUser($this->user)->forAccount($this->account)->create();

    $updatedData = [
        'account_id' => $this->account->id,
        'description' => 'Updated Expense',
        'transacted_at' => now()->format('Y-m-d H:i:s'),
        'amount' => 1500.00,
    ];

    $this->actingAs($this->user)
        ->put(route('expenses.update', $expense), $updatedData)
        ->assertRedirect(route('expenses.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('expenses', [
        'id' => $expense->id,
        'description' => 'Updated Expense',
    ]);
});

test('authenticated user cannot view edit form for another users expense', function () {
    $otherExpense = Expense::factory()->create();

    $this->actingAs($this->user)
        ->get(route('expenses.edit', $otherExpense))
        ->assertForbidden();
});

test('user cannot update another users expense', function () {
    $otherExpense = Expense::factory()->create();

    $this->actingAs($this->user)
        ->put(route('expenses.update', $otherExpense), [
            'account_id' => $this->account->id,
            'description' => 'Hacked',
            'transacted_at' => now()->format('Y-m-d H:i:s'),
            'amount' => 100,
        ])
        ->assertForbidden();
});

test('authenticated user can delete their own expense', function () {
    $expense = Expense::factory()->forUser($this->user)->forAccount($this->account)->create();

    $this->actingAs($this->user)
        ->delete(route('expenses.destroy', $expense))
        ->assertRedirect(route('expenses.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
});

test('user cannot delete another users expense', function () {
    $otherExpense = Expense::factory()->create();

    $this->actingAs($this->user)
        ->delete(route('expenses.destroy', $otherExpense))
        ->assertForbidden();

    $this->assertDatabaseHas('expenses', ['id' => $otherExpense->id]);
});

// Filter tests using Spatie Query Builder

test('expense index defaults to current month date range', function () {
    $this->actingAs($this->user)
        ->get(route('expenses.index'))
        ->assertSuccessful()
        ->assertViewHas('filters', function ($filters) {
            return $filters['from_date'] === now()->startOfMonth()->format('Y-m-d')
                && $filters['to_date'] === now()->endOfMonth()->format('Y-m-d');
        });
});

test('can filter expenses by from_date', function () {
    $oldExpense = Expense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['transacted_at' => now()->subMonth()]);

    $recentExpense = Expense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['transacted_at' => now()]);

    $this->actingAs($this->user)
        ->get(route('expenses.index', [
            'filter' => [
                'from_date' => now()->subDays(7)->format('Y-m-d'),
                'to_date' => now()->addDay()->format('Y-m-d'),
            ],
        ]))
        ->assertSuccessful()
        ->assertSee($recentExpense->description)
        ->assertDontSee($oldExpense->description);
});

test('can filter expenses by to_date', function () {
    $oldExpense = Expense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['transacted_at' => now()->subMonth()]);

    $recentExpense = Expense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['transacted_at' => now()]);

    $this->actingAs($this->user)
        ->get(route('expenses.index', [
            'filter' => [
                'from_date' => now()->subMonths(2)->format('Y-m-d'),
                'to_date' => now()->subWeeks(2)->format('Y-m-d'),
            ],
        ]))
        ->assertSuccessful()
        ->assertSee($oldExpense->description)
        ->assertDontSee($recentExpense->description);
});

test('can filter expenses by date range', function () {
    $beforeRange = Expense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['transacted_at' => now()->subMonths(2), 'description' => 'Before range']);

    $inRange = Expense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['transacted_at' => now()->subMonth(), 'description' => 'In range']);

    $afterRange = Expense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['transacted_at' => now(), 'description' => 'After range']);

    $this->actingAs($this->user)
        ->get(route('expenses.index', [
            'filter' => [
                'from_date' => now()->subMonths(2)->addDay()->format('Y-m-d'),
                'to_date' => now()->subDay()->format('Y-m-d'),
            ],
        ]))
        ->assertSuccessful()
        ->assertSee('In range')
        ->assertDontSee('Before range')
        ->assertDontSee('After range');
});

test('can filter expenses by search term', function () {
    $matchingExpense = Expense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['description' => 'Grocery shopping', 'transacted_at' => now()]);

    $nonMatchingExpense = Expense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['description' => 'Electricity bill', 'transacted_at' => now()]);

    $this->actingAs($this->user)
        ->get(route('expenses.index', ['filter' => ['search' => 'Grocery']]))
        ->assertSuccessful()
        ->assertSee('Grocery shopping')
        ->assertDontSee('Electricity bill');
});

test('can filter expenses by account', function () {
    $secondAccount = Account::factory()->forUser($this->user)->create();

    $firstAccountExpense = Expense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['description' => 'First account expense', 'transacted_at' => now()]);

    $secondAccountExpense = Expense::factory()
        ->forUser($this->user)
        ->forAccount($secondAccount)
        ->create(['description' => 'Second account expense', 'transacted_at' => now()]);

    $this->actingAs($this->user)
        ->get(route('expenses.index', ['filter' => ['account_id' => $this->account->id]]))
        ->assertSuccessful()
        ->assertSee('First account expense')
        ->assertDontSee('Second account expense');
});

test('can filter expenses by person', function () {
    $person = Person::factory()->create();
    $anotherPerson = Person::factory()->create();

    $expenseWithPerson = Expense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['description' => 'Expense with person', 'person_id' => $person->id, 'transacted_at' => now()]);

    $expenseWithAnotherPerson = Expense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['description' => 'Expense with another', 'person_id' => $anotherPerson->id, 'transacted_at' => now()]);

    $this->actingAs($this->user)
        ->get(route('expenses.index', ['filter' => ['person_id' => $person->id]]))
        ->assertSuccessful()
        ->assertSee('Expense with person')
        ->assertDontSee('Expense with another');
});

test('can combine multiple expense filters', function () {
    $person = Person::factory()->create();

    $matchingExpense = Expense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create([
            'description' => 'Matching expense',
            'person_id' => $person->id,
            'transacted_at' => now(),
        ]);

    $wrongAccount = Account::factory()->forUser($this->user)->create();
    $wrongAccountExpense = Expense::factory()
        ->forUser($this->user)
        ->forAccount($wrongAccount)
        ->create([
            'description' => 'Wrong account',
            'person_id' => $person->id,
            'transacted_at' => now(),
        ]);

    $wrongPersonExpense = Expense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create([
            'description' => 'Wrong person',
            'person_id' => null,
            'transacted_at' => now(),
        ]);

    $this->actingAs($this->user)
        ->get(route('expenses.index', [
            'filter' => [
                'account_id' => $this->account->id,
                'person_id' => $person->id,
                'search' => 'Matching',
            ],
        ]))
        ->assertSuccessful()
        ->assertSee('Matching expense')
        ->assertDontSee('Wrong account')
        ->assertDontSee('Wrong person');
});

test('expense filters are passed to the view', function () {
    $person = Person::factory()->create();

    $this->actingAs($this->user)
        ->get(route('expenses.index', [
            'filter' => [
                'from_date' => '2024-01-01',
                'to_date' => '2024-12-31',
                'search' => 'test search',
                'account_id' => $this->account->id,
                'person_id' => $person->id,
            ],
        ]))
        ->assertSuccessful()
        ->assertViewHas('filters', function ($filters) use ($person) {
            return $filters['from_date'] === '2024-01-01'
                && $filters['to_date'] === '2024-12-31'
                && $filters['search'] === 'test search'
                && $filters['account_id'] == $this->account->id
                && $filters['person_id'] == $person->id;
        });
});

test('accounts and people are passed to expense index view for filter dropdowns', function () {
    Person::factory()->create();

    $this->actingAs($this->user)
        ->get(route('expenses.index'))
        ->assertSuccessful()
        ->assertViewHas('accounts')
        ->assertViewHas('people');
});

// Tag tests

test('expense create form includes tags', function () {
    TagFactory::new()->count(3)->create();

    $this->actingAs($this->user)
        ->get(route('expenses.create'))
        ->assertSuccessful()
        ->assertViewHas('tags');
});

test('expense edit form includes tags', function () {
    $expense = Expense::factory()->forUser($this->user)->forAccount($this->account)->create();
    TagFactory::new()->count(3)->create();

    $this->actingAs($this->user)
        ->get(route('expenses.edit', $expense))
        ->assertSuccessful()
        ->assertViewHas('tags');
});

test('expense can be created with tags', function () {
    $tags = TagFactory::new()->count(2)->create();

    $expenseData = [
        'account_id' => $this->account->id,
        'description' => 'Expense with tags',
        'transacted_at' => now()->format('Y-m-d H:i:s'),
        'amount' => 500.00,
        'tags' => $tags->pluck('id')->toArray(),
    ];

    $this->actingAs($this->user)
        ->post(route('expenses.store'), $expenseData)
        ->assertRedirect(route('expenses.index'));

    $expense = Expense::where('description', 'Expense with tags')->first();
    expect($expense->tags)->toHaveCount(2);
});

test('expense can be updated with tags', function () {
    $expense = Expense::factory()->forUser($this->user)->forAccount($this->account)->create();
    $tags = TagFactory::new()->count(3)->create();

    $updatedData = [
        'account_id' => $this->account->id,
        'description' => 'Updated with tags',
        'transacted_at' => now()->format('Y-m-d H:i:s'),
        'amount' => 800.00,
        'tags' => $tags->pluck('id')->toArray(),
    ];

    $this->actingAs($this->user)
        ->put(route('expenses.update', $expense), $updatedData)
        ->assertRedirect(route('expenses.index'));

    $expense->refresh();
    expect($expense->tags)->toHaveCount(3);
});

test('expense tags can be removed on update', function () {
    $expense = Expense::factory()->forUser($this->user)->forAccount($this->account)->create();
    $tags = TagFactory::new()->count(2)->create();
    $expense->tags()->attach($tags);

    $updatedData = [
        'account_id' => $this->account->id,
        'description' => 'Tags removed',
        'transacted_at' => now()->format('Y-m-d H:i:s'),
        'amount' => 800.00,
        'tags' => [],
    ];

    $this->actingAs($this->user)
        ->put(route('expenses.update', $expense), $updatedData)
        ->assertRedirect(route('expenses.index'));

    $expense->refresh();
    expect($expense->tags)->toHaveCount(0);
});

test('can filter expenses by tag', function () {
    $tag = TagFactory::new()->create();
    $anotherTag = TagFactory::new()->create();

    $expenseWithTag = Expense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['description' => 'Expense with tag', 'transacted_at' => now()]);
    $expenseWithTag->tags()->attach($tag);

    $expenseWithAnotherTag = Expense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['description' => 'Expense with another tag', 'transacted_at' => now()]);
    $expenseWithAnotherTag->tags()->attach($anotherTag);

    $this->actingAs($this->user)
        ->get(route('expenses.index', ['filter' => ['tag_id' => $tag->id]]))
        ->assertSuccessful()
        ->assertSee('Expense with tag')
        ->assertDontSee('Expense with another tag');
});

test('tags are passed to expense index view for filter dropdown', function () {
    TagFactory::new()->count(3)->create();

    $this->actingAs($this->user)
        ->get(route('expenses.index'))
        ->assertSuccessful()
        ->assertViewHas('tags');
});

test('tag filter is passed to expense view', function () {
    $tag = TagFactory::new()->create();

    $this->actingAs($this->user)
        ->get(route('expenses.index', ['filter' => ['tag_id' => $tag->id]]))
        ->assertSuccessful()
        ->assertViewHas('filters', function ($filters) use ($tag) {
            return $filters['tag_id'] == $tag->id;
        });
});

// Account balance tests

test('creating an expense decrements account balance', function () {
    $initialBalance = 1000.00;
    $this->account->update(['current_balance' => $initialBalance]);

    $expenseAmount = 300.00;
    $expenseData = [
        'account_id' => $this->account->id,
        'description' => 'Test Expense',
        'transacted_at' => now()->format('Y-m-d H:i:s'),
        'amount' => $expenseAmount,
    ];

    $this->actingAs($this->user)
        ->post(route('expenses.store'), $expenseData)
        ->assertRedirect(route('expenses.index'));

    $this->account->refresh();
    expect($this->account->current_balance)->toBe(number_format($initialBalance - $expenseAmount, 2, '.', ''));
});

test('updating an expense adjusts account balance for amount change', function () {
    $initialBalance = 1000.00;
    $this->account->update(['current_balance' => $initialBalance]);

    $expense = Expense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['amount' => 200.00]);

    $this->account->update(['current_balance' => $initialBalance - 200.00]);

    $newAmount = 350.00;
    $updatedData = [
        'account_id' => $this->account->id,
        'description' => 'Updated Expense',
        'transacted_at' => now()->format('Y-m-d H:i:s'),
        'amount' => $newAmount,
    ];

    $this->actingAs($this->user)
        ->put(route('expenses.update', $expense), $updatedData)
        ->assertRedirect(route('expenses.index'));

    $this->account->refresh();
    expect($this->account->current_balance)->toBe(number_format($initialBalance - $newAmount, 2, '.', ''));
});

test('updating an expense to different account adjusts both account balances', function () {
    $secondAccount = Account::factory()->forUser($this->user)->create(['current_balance' => 500.00]);

    $initialBalance = 1000.00;
    $this->account->update(['current_balance' => $initialBalance]);

    $expenseAmount = 200.00;
    $expense = Expense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['amount' => $expenseAmount]);

    $this->account->update(['current_balance' => $initialBalance - $expenseAmount]);

    $updatedData = [
        'account_id' => $secondAccount->id,
        'description' => 'Moved to different account',
        'transacted_at' => now()->format('Y-m-d H:i:s'),
        'amount' => $expenseAmount,
    ];

    $this->actingAs($this->user)
        ->put(route('expenses.update', $expense), $updatedData)
        ->assertRedirect(route('expenses.index'));

    $this->account->refresh();
    $secondAccount->refresh();

    expect($this->account->current_balance)->toBe(number_format($initialBalance, 2, '.', ''));
    expect($secondAccount->current_balance)->toBe(number_format(500.00 - $expenseAmount, 2, '.', ''));
});

test('deleting an expense increments account balance', function () {
    $initialBalance = 1000.00;
    $this->account->update(['current_balance' => $initialBalance]);

    $expenseAmount = 300.00;
    $expense = Expense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['amount' => $expenseAmount]);

    $this->account->update(['current_balance' => $initialBalance - $expenseAmount]);

    $this->actingAs($this->user)
        ->delete(route('expenses.destroy', $expense))
        ->assertRedirect(route('expenses.index'));

    $this->account->refresh();
    expect($this->account->current_balance)->toBe(number_format($initialBalance, 2, '.', ''));
});

// Export tests

test('guest cannot export expenses', function () {
    $this->post(route('expenses.export'))
        ->assertRedirect(route('login'));
});

test('authenticated user can export expenses to csv', function () {
    Expense::factory()->count(3)->forUser($this->user)->forAccount($this->account)->create([
        'transacted_at' => now(),
    ]);

    $this->actingAs($this->user)
        ->post(route('expenses.export'))
        ->assertSuccessful()
        ->assertDownload('expenses.csv');
});

test('csv export includes filtered expense data', function () {
    $inRangeExpense = Expense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['transacted_at' => now(), 'description' => 'In range expense']);

    $outOfRangeExpense = Expense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['transacted_at' => now()->subMonths(2), 'description' => 'Out of range expense']);

    $response = $this->actingAs($this->user)
        ->post(route('expenses.export'), [
            'filter' => [
                'from_date' => now()->subDays(7)->format('Y-m-d'),
                'to_date' => now()->addDay()->format('Y-m-d'),
            ],
        ]);

    $response->assertSuccessful()
        ->assertDownload('expenses.csv');
});

test('csv export only includes user own expenses', function () {
    $ownExpense = Expense::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['transacted_at' => now()]);

    $otherExpense = Expense::factory()->create(['transacted_at' => now()]);

    $this->actingAs($this->user)
        ->post(route('expenses.export'))
        ->assertSuccessful()
        ->assertDownload('expenses.csv');
});
