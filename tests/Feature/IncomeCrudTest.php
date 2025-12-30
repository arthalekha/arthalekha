<?php

use App\Models\Account;
use App\Models\Income;
use App\Models\Person;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->account = Account::factory()->forUser($this->user)->create();
});

test('guest cannot access incomes index', function () {
    $this->get(route('incomes.index'))
        ->assertRedirect(route('login'));
});

test('authenticated user can view incomes index', function () {
    Income::factory()->count(3)->forUser($this->user)->forAccount($this->account)->create();

    $this->actingAs($this->user)
        ->get(route('incomes.index'))
        ->assertSuccessful()
        ->assertViewIs('incomes.index')
        ->assertViewHas('incomes');
});

test('user can only see their own incomes', function () {
    $ownIncome = Income::factory()->forUser($this->user)->forAccount($this->account)->create([
        'transacted_at' => now(),
    ]);
    $otherIncome = Income::factory()->create([
        'transacted_at' => now(),
    ]);

    $this->actingAs($this->user)
        ->get(route('incomes.index'))
        ->assertSuccessful()
        ->assertSee($ownIncome->description)
        ->assertDontSee($otherIncome->description);
});

test('authenticated user can view create income form', function () {
    $this->actingAs($this->user)
        ->get(route('incomes.create'))
        ->assertSuccessful()
        ->assertViewIs('incomes.create')
        ->assertViewHas('accounts')
        ->assertViewHas('people');
});

test('authenticated user can create an income', function () {
    $incomeData = [
        'account_id' => $this->account->id,
        'description' => 'Test Income',
        'transacted_at' => now()->format('Y-m-d H:i:s'),
        'amount' => 1000.50,
    ];

    $this->actingAs($this->user)
        ->post(route('incomes.store'), $incomeData)
        ->assertRedirect(route('incomes.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('incomes', [
        'description' => 'Test Income',
        'user_id' => $this->user->id,
        'account_id' => $this->account->id,
    ]);
});

test('income can be associated with a person', function () {
    $person = Person::factory()->create();

    $incomeData = [
        'account_id' => $this->account->id,
        'person_id' => $person->id,
        'description' => 'Payment from person',
        'transacted_at' => now()->format('Y-m-d H:i:s'),
        'amount' => 500.00,
    ];

    $this->actingAs($this->user)
        ->post(route('incomes.store'), $incomeData)
        ->assertRedirect(route('incomes.index'));

    $this->assertDatabaseHas('incomes', [
        'description' => 'Payment from person',
        'person_id' => $person->id,
    ]);
});

test('creating an income requires a description', function () {
    $this->actingAs($this->user)
        ->post(route('incomes.store'), [
            'account_id' => $this->account->id,
            'description' => '',
            'transacted_at' => now()->format('Y-m-d H:i:s'),
            'amount' => 100,
        ])
        ->assertSessionHasErrors('description');
});

test('creating an income requires a valid amount', function () {
    $this->actingAs($this->user)
        ->post(route('incomes.store'), [
            'account_id' => $this->account->id,
            'description' => 'Test',
            'transacted_at' => now()->format('Y-m-d H:i:s'),
            'amount' => 0,
        ])
        ->assertSessionHasErrors('amount');
});

test('creating an income requires users own account', function () {
    $otherAccount = Account::factory()->create();

    $this->actingAs($this->user)
        ->post(route('incomes.store'), [
            'account_id' => $otherAccount->id,
            'description' => 'Test',
            'transacted_at' => now()->format('Y-m-d H:i:s'),
            'amount' => 100,
        ])
        ->assertSessionHasErrors('account_id');
});

test('authenticated user can view their own income', function () {
    $income = Income::factory()->forUser($this->user)->forAccount($this->account)->create();

    $this->actingAs($this->user)
        ->get(route('incomes.show', $income))
        ->assertSuccessful()
        ->assertViewIs('incomes.show')
        ->assertViewHas('income');
});

test('user cannot view another users income', function () {
    $otherIncome = Income::factory()->create();

    $this->actingAs($this->user)
        ->get(route('incomes.show', $otherIncome))
        ->assertForbidden();
});

test('authenticated user can view edit form for their own income', function () {
    $income = Income::factory()->forUser($this->user)->forAccount($this->account)->create();

    $this->actingAs($this->user)
        ->get(route('incomes.edit', $income))
        ->assertSuccessful()
        ->assertViewIs('incomes.edit')
        ->assertViewHas('income');
});

test('authenticated user can update their own income', function () {
    $income = Income::factory()->forUser($this->user)->forAccount($this->account)->create();

    $updatedData = [
        'account_id' => $this->account->id,
        'description' => 'Updated Income',
        'transacted_at' => now()->format('Y-m-d H:i:s'),
        'amount' => 2000.00,
    ];

    $this->actingAs($this->user)
        ->put(route('incomes.update', $income), $updatedData)
        ->assertRedirect(route('incomes.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('incomes', [
        'id' => $income->id,
        'description' => 'Updated Income',
    ]);
});

test('user cannot update another users income', function () {
    $otherIncome = Income::factory()->create();

    $this->actingAs($this->user)
        ->put(route('incomes.update', $otherIncome), [
            'account_id' => $this->account->id,
            'description' => 'Hacked',
            'transacted_at' => now()->format('Y-m-d H:i:s'),
            'amount' => 100,
        ])
        ->assertForbidden();
});

test('authenticated user can delete their own income', function () {
    $income = Income::factory()->forUser($this->user)->forAccount($this->account)->create();

    $this->actingAs($this->user)
        ->delete(route('incomes.destroy', $income))
        ->assertRedirect(route('incomes.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('incomes', ['id' => $income->id]);
});

test('user cannot delete another users income', function () {
    $otherIncome = Income::factory()->create();

    $this->actingAs($this->user)
        ->delete(route('incomes.destroy', $otherIncome))
        ->assertForbidden();

    $this->assertDatabaseHas('incomes', ['id' => $otherIncome->id]);
});

// Filter tests using Spatie Query Builder

test('index defaults to current month date range', function () {
    $this->actingAs($this->user)
        ->get(route('incomes.index'))
        ->assertSuccessful()
        ->assertViewHas('filters', function ($filters) {
            return $filters['from_date'] === now()->startOfMonth()->format('Y-m-d')
                && $filters['to_date'] === now()->endOfMonth()->format('Y-m-d');
        });
});

test('can filter incomes by from_date', function () {
    $oldIncome = Income::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['transacted_at' => now()->subMonth()]);

    $recentIncome = Income::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['transacted_at' => now()]);

    $this->actingAs($this->user)
        ->get(route('incomes.index', [
            'filter' => [
                'from_date' => now()->subDays(7)->format('Y-m-d'),
                'to_date' => now()->addDay()->format('Y-m-d'),
            ],
        ]))
        ->assertSuccessful()
        ->assertSee($recentIncome->description)
        ->assertDontSee($oldIncome->description);
});

test('can filter incomes by to_date', function () {
    $oldIncome = Income::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['transacted_at' => now()->subMonth()]);

    $recentIncome = Income::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['transacted_at' => now()]);

    $this->actingAs($this->user)
        ->get(route('incomes.index', [
            'filter' => [
                'from_date' => now()->subMonths(2)->format('Y-m-d'),
                'to_date' => now()->subWeeks(2)->format('Y-m-d'),
            ],
        ]))
        ->assertSuccessful()
        ->assertSee($oldIncome->description)
        ->assertDontSee($recentIncome->description);
});

test('can filter incomes by date range', function () {
    $beforeRange = Income::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['transacted_at' => now()->subMonths(2), 'description' => 'Before range']);

    $inRange = Income::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['transacted_at' => now()->subMonth(), 'description' => 'In range']);

    $afterRange = Income::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['transacted_at' => now(), 'description' => 'After range']);

    $this->actingAs($this->user)
        ->get(route('incomes.index', [
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

test('can filter incomes by search term', function () {
    $matchingIncome = Income::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['description' => 'Salary payment', 'transacted_at' => now()]);

    $nonMatchingIncome = Income::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['description' => 'Freelance work', 'transacted_at' => now()]);

    $this->actingAs($this->user)
        ->get(route('incomes.index', ['filter' => ['search' => 'Salary']]))
        ->assertSuccessful()
        ->assertSee('Salary payment')
        ->assertDontSee('Freelance work');
});

test('can filter incomes by account', function () {
    $secondAccount = Account::factory()->forUser($this->user)->create();

    $firstAccountIncome = Income::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['description' => 'First account income', 'transacted_at' => now()]);

    $secondAccountIncome = Income::factory()
        ->forUser($this->user)
        ->forAccount($secondAccount)
        ->create(['description' => 'Second account income', 'transacted_at' => now()]);

    $this->actingAs($this->user)
        ->get(route('incomes.index', ['filter' => ['account_id' => $this->account->id]]))
        ->assertSuccessful()
        ->assertSee('First account income')
        ->assertDontSee('Second account income');
});

test('can filter incomes by person', function () {
    $person = Person::factory()->create();
    $anotherPerson = Person::factory()->create();

    $incomeWithPerson = Income::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['description' => 'Income with person', 'person_id' => $person->id, 'transacted_at' => now()]);

    $incomeWithAnotherPerson = Income::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['description' => 'Income with another', 'person_id' => $anotherPerson->id, 'transacted_at' => now()]);

    $this->actingAs($this->user)
        ->get(route('incomes.index', ['filter' => ['person_id' => $person->id]]))
        ->assertSuccessful()
        ->assertSee('Income with person')
        ->assertDontSee('Income with another');
});

test('can combine multiple filters', function () {
    $person = Person::factory()->create();

    $matchingIncome = Income::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create([
            'description' => 'Matching income',
            'person_id' => $person->id,
            'transacted_at' => now(),
        ]);

    $wrongAccount = Account::factory()->forUser($this->user)->create();
    $wrongAccountIncome = Income::factory()
        ->forUser($this->user)
        ->forAccount($wrongAccount)
        ->create([
            'description' => 'Wrong account',
            'person_id' => $person->id,
            'transacted_at' => now(),
        ]);

    $wrongPersonIncome = Income::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create([
            'description' => 'Wrong person',
            'person_id' => null,
            'transacted_at' => now(),
        ]);

    $this->actingAs($this->user)
        ->get(route('incomes.index', [
            'filter' => [
                'account_id' => $this->account->id,
                'person_id' => $person->id,
                'search' => 'Matching',
            ],
        ]))
        ->assertSuccessful()
        ->assertSee('Matching income')
        ->assertDontSee('Wrong account')
        ->assertDontSee('Wrong person');
});

test('filters are passed to the view', function () {
    $person = Person::factory()->create();

    $this->actingAs($this->user)
        ->get(route('incomes.index', [
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

test('accounts and people are passed to index view for filter dropdowns', function () {
    Person::factory()->create();

    $this->actingAs($this->user)
        ->get(route('incomes.index'))
        ->assertSuccessful()
        ->assertViewHas('accounts')
        ->assertViewHas('people');
});

// Tag tests

test('create form includes tags', function () {
    Tag::factory()->count(3)->create();

    $this->actingAs($this->user)
        ->get(route('incomes.create'))
        ->assertSuccessful()
        ->assertViewHas('tags');
});

test('edit form includes tags', function () {
    $income = Income::factory()->forUser($this->user)->forAccount($this->account)->create();
    Tag::factory()->count(3)->create();

    $this->actingAs($this->user)
        ->get(route('incomes.edit', $income))
        ->assertSuccessful()
        ->assertViewHas('tags');
});

test('income can be created with tags', function () {
    $tags = Tag::factory()->count(2)->create();

    $incomeData = [
        'account_id' => $this->account->id,
        'description' => 'Income with tags',
        'transacted_at' => now()->format('Y-m-d H:i:s'),
        'amount' => 1000.00,
        'tags' => $tags->pluck('id')->toArray(),
    ];

    $this->actingAs($this->user)
        ->post(route('incomes.store'), $incomeData)
        ->assertRedirect(route('incomes.index'));

    $income = Income::where('description', 'Income with tags')->first();
    expect($income->tags)->toHaveCount(2);
});

test('income can be updated with tags', function () {
    $income = Income::factory()->forUser($this->user)->forAccount($this->account)->create();
    $tags = Tag::factory()->count(3)->create();

    $updatedData = [
        'account_id' => $this->account->id,
        'description' => 'Updated with tags',
        'transacted_at' => now()->format('Y-m-d H:i:s'),
        'amount' => 2000.00,
        'tags' => $tags->pluck('id')->toArray(),
    ];

    $this->actingAs($this->user)
        ->put(route('incomes.update', $income), $updatedData)
        ->assertRedirect(route('incomes.index'));

    $income->refresh();
    expect($income->tags)->toHaveCount(3);
});

test('income tags can be removed on update', function () {
    $income = Income::factory()->forUser($this->user)->forAccount($this->account)->create();
    $tags = Tag::factory()->count(2)->create();
    $income->tags()->attach($tags);

    $updatedData = [
        'account_id' => $this->account->id,
        'description' => 'Tags removed',
        'transacted_at' => now()->format('Y-m-d H:i:s'),
        'amount' => 2000.00,
        'tags' => [],
    ];

    $this->actingAs($this->user)
        ->put(route('incomes.update', $income), $updatedData)
        ->assertRedirect(route('incomes.index'));

    $income->refresh();
    expect($income->tags)->toHaveCount(0);
});

test('can filter incomes by tag', function () {
    $tag = Tag::factory()->create();
    $anotherTag = Tag::factory()->create();

    $incomeWithTag = Income::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['description' => 'Income with tag', 'transacted_at' => now()]);
    $incomeWithTag->tags()->attach($tag);

    $incomeWithAnotherTag = Income::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['description' => 'Income with another tag', 'transacted_at' => now()]);
    $incomeWithAnotherTag->tags()->attach($anotherTag);

    $this->actingAs($this->user)
        ->get(route('incomes.index', ['filter' => ['tag_id' => $tag->id]]))
        ->assertSuccessful()
        ->assertSee('Income with tag')
        ->assertDontSee('Income with another tag');
});

test('tags are passed to income index view for filter dropdown', function () {
    Tag::factory()->count(3)->create();

    $this->actingAs($this->user)
        ->get(route('incomes.index'))
        ->assertSuccessful()
        ->assertViewHas('tags');
});

test('tag filter is passed to income view', function () {
    $tag = Tag::factory()->create();

    $this->actingAs($this->user)
        ->get(route('incomes.index', ['filter' => ['tag_id' => $tag->id]]))
        ->assertSuccessful()
        ->assertViewHas('filters', function ($filters) use ($tag) {
            return $filters['tag_id'] == $tag->id;
        });
});

// Account balance tests

test('creating an income increments account balance', function () {
    $initialBalance = 1000.00;
    $this->account->update(['current_balance' => $initialBalance]);

    $incomeAmount = 500.00;
    $incomeData = [
        'account_id' => $this->account->id,
        'description' => 'Test Income',
        'transacted_at' => now()->format('Y-m-d H:i:s'),
        'amount' => $incomeAmount,
    ];

    $this->actingAs($this->user)
        ->post(route('incomes.store'), $incomeData)
        ->assertRedirect(route('incomes.index'));

    $this->account->refresh();
    expect($this->account->current_balance)->toBe(number_format($initialBalance + $incomeAmount, 2, '.', ''));
});

test('updating an income adjusts account balance for amount change', function () {
    $initialBalance = 1000.00;
    $this->account->update(['current_balance' => $initialBalance]);

    $income = Income::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['amount' => 200.00]);

    $this->account->update(['current_balance' => $initialBalance + 200.00]);

    $newAmount = 350.00;
    $updatedData = [
        'account_id' => $this->account->id,
        'description' => 'Updated Income',
        'transacted_at' => now()->format('Y-m-d H:i:s'),
        'amount' => $newAmount,
    ];

    $this->actingAs($this->user)
        ->put(route('incomes.update', $income), $updatedData)
        ->assertRedirect(route('incomes.index'));

    $this->account->refresh();
    expect($this->account->current_balance)->toBe(number_format($initialBalance + $newAmount, 2, '.', ''));
});

test('updating an income to different account adjusts both account balances', function () {
    $secondAccount = Account::factory()->forUser($this->user)->create(['current_balance' => 500.00]);

    $initialBalance = 1000.00;
    $this->account->update(['current_balance' => $initialBalance]);

    $incomeAmount = 200.00;
    $income = Income::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['amount' => $incomeAmount]);

    $this->account->update(['current_balance' => $initialBalance + $incomeAmount]);

    $updatedData = [
        'account_id' => $secondAccount->id,
        'description' => 'Moved to different account',
        'transacted_at' => now()->format('Y-m-d H:i:s'),
        'amount' => $incomeAmount,
    ];

    $this->actingAs($this->user)
        ->put(route('incomes.update', $income), $updatedData)
        ->assertRedirect(route('incomes.index'));

    $this->account->refresh();
    $secondAccount->refresh();

    expect($this->account->current_balance)->toBe(number_format($initialBalance, 2, '.', ''));
    expect($secondAccount->current_balance)->toBe(number_format(500.00 + $incomeAmount, 2, '.', ''));
});

test('deleting an income decrements account balance', function () {
    $initialBalance = 1000.00;
    $this->account->update(['current_balance' => $initialBalance]);

    $incomeAmount = 300.00;
    $income = Income::factory()
        ->forUser($this->user)
        ->forAccount($this->account)
        ->create(['amount' => $incomeAmount]);

    $this->account->update(['current_balance' => $initialBalance + $incomeAmount]);

    $this->actingAs($this->user)
        ->delete(route('incomes.destroy', $income))
        ->assertRedirect(route('incomes.index'));

    $this->account->refresh();
    expect($this->account->current_balance)->toBe(number_format($initialBalance, 2, '.', ''));
});
