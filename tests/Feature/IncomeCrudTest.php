<?php

use App\Models\Account;
use App\Models\Income;
use App\Models\Person;
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
    $ownIncome = Income::factory()->forUser($this->user)->forAccount($this->account)->create();
    $otherIncome = Income::factory()->create();

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
