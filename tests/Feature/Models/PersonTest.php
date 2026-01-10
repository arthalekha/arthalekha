<?php

use App\Models\Expense;
use App\Models\Income;
use App\Models\Person;
use App\Services\PersonService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('mass assignment works for name and nick_name', function () {
    $person = Person::create([
        'name' => 'John Doe',
        'nick_name' => 'JD',
    ]);

    expect($person->name)->toBe('John Doe');
    expect($person->nick_name)->toBe('JD');
});

test('can create person without nick_name', function () {
    $person = Person::create([
        'name' => 'Jane Smith',
    ]);

    expect($person->name)->toBe('Jane Smith');
    expect($person->nick_name)->toBeNull();
});

test('can query incomes that reference person', function () {
    $person = Person::factory()->create();

    Income::factory()->count(3)->create(['person_id' => $person->id]);
    Income::factory()->count(2)->create(['person_id' => null]);

    $incomes = Income::where('person_id', $person->id)->get();

    expect($incomes)->toHaveCount(3);
    expect($incomes->first()->person_id)->toBe($person->id);
});

test('can query expenses that reference person', function () {
    $person = Person::factory()->create();

    Expense::factory()->count(4)->create(['person_id' => $person->id]);
    Expense::factory()->count(1)->create(['person_id' => null]);

    $expenses = Expense::where('person_id', $person->id)->get();

    expect($expenses)->toHaveCount(4);
    expect($expenses->first()->person_id)->toBe($person->id);
});

test('creating person calls PersonService clearCache', function () {
    $personService = Mockery::mock(PersonService::class);
    $personService->shouldReceive('clearCache')
        ->once()
        ->withNoArgs();

    app()->instance(PersonService::class, $personService);

    Person::factory()->create();
});

test('updating person calls PersonService clearCache', function () {
    $person = Person::factory()->create();

    $personService = Mockery::mock(PersonService::class);
    $personService->shouldReceive('clearCache')
        ->once()
        ->withNoArgs();

    app()->instance(PersonService::class, $personService);

    $person->update(['name' => 'Updated Name']);
});

test('deleting person calls PersonService clearCache', function () {
    $person = Person::factory()->create();

    $personService = Mockery::mock(PersonService::class);
    $personService->shouldReceive('clearCache')
        ->once()
        ->withNoArgs();

    app()->instance(PersonService::class, $personService);

    $person->delete();
});

test('person attributes are stored correctly', function () {
    $person = Person::factory()->create([
        'name' => 'Test Person',
        'nick_name' => 'Tester',
    ]);

    $person->refresh();

    expect($person->name)->toBe('Test Person');
    expect($person->nick_name)->toBe('Tester');
});

test('person can be updated', function () {
    $person = Person::factory()->create(['name' => 'Original Name']);

    $person->update([
        'name' => 'New Name',
        'nick_name' => 'NN',
    ]);

    expect($person->name)->toBe('New Name');
    expect($person->nick_name)->toBe('NN');
});
