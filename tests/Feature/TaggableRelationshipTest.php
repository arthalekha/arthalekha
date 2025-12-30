<?php

use App\Models\Expense;
use App\Models\Income;
use App\Models\Tag;
use App\Models\Transfer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('income can have tags', function () {
    $income = Income::factory()->create();
    $tags = Tag::factory()->count(3)->create();

    $income->tags()->attach($tags);

    expect($income->tags)->toHaveCount(3);
    expect($income->tags->first())->toBeInstanceOf(Tag::class);
});

test('expense can have tags', function () {
    $expense = Expense::factory()->create();
    $tags = Tag::factory()->count(2)->create();

    $expense->tags()->attach($tags);

    expect($expense->tags)->toHaveCount(2);
    expect($expense->tags->first())->toBeInstanceOf(Tag::class);
});

test('transfer can have tags', function () {
    $transfer = Transfer::factory()->create();
    $tags = Tag::factory()->count(4)->create();

    $transfer->tags()->attach($tags);

    expect($transfer->tags)->toHaveCount(4);
    expect($transfer->tags->first())->toBeInstanceOf(Tag::class);
});

test('tag can have incomes', function () {
    $tag = Tag::factory()->create();
    $incomes = Income::factory()->count(2)->create();

    $tag->incomes()->attach($incomes);

    expect($tag->incomes)->toHaveCount(2);
    expect($tag->incomes->first())->toBeInstanceOf(Income::class);
});

test('tag can have expenses', function () {
    $tag = Tag::factory()->create();
    $expenses = Expense::factory()->count(3)->create();

    $tag->expenses()->attach($expenses);

    expect($tag->expenses)->toHaveCount(3);
    expect($tag->expenses->first())->toBeInstanceOf(Expense::class);
});

test('tag can have transfers', function () {
    $tag = Tag::factory()->create();
    $transfers = Transfer::factory()->count(2)->create();

    $tag->transfers()->attach($transfers);

    expect($tag->transfers)->toHaveCount(2);
    expect($tag->transfers->first())->toBeInstanceOf(Transfer::class);
});

test('same tag can be attached to multiple model types', function () {
    $tag = Tag::factory()->create();
    $income = Income::factory()->create();
    $expense = Expense::factory()->create();
    $transfer = Transfer::factory()->create();

    $tag->incomes()->attach($income);
    $tag->expenses()->attach($expense);
    $tag->transfers()->attach($transfer);

    expect($tag->incomes)->toHaveCount(1);
    expect($tag->expenses)->toHaveCount(1);
    expect($tag->transfers)->toHaveCount(1);
});

test('tags can be synced on income', function () {
    $income = Income::factory()->create();
    $initialTags = Tag::factory()->count(2)->create();
    $newTags = Tag::factory()->count(3)->create();

    $income->tags()->attach($initialTags);
    expect($income->tags)->toHaveCount(2);

    $income->tags()->sync($newTags->pluck('id'));
    $income->refresh();

    expect($income->tags)->toHaveCount(3);
});

test('tags can be detached from expense', function () {
    $expense = Expense::factory()->create();
    $tags = Tag::factory()->count(3)->create();

    $expense->tags()->attach($tags);
    expect($expense->tags)->toHaveCount(3);

    $expense->tags()->detach($tags->first());
    $expense->refresh();

    expect($expense->tags)->toHaveCount(2);
});

test('deleting tag removes pivot entries', function () {
    $tag = Tag::factory()->create();
    $income = Income::factory()->create();
    $expense = Expense::factory()->create();

    $income->tags()->attach($tag);
    $expense->tags()->attach($tag);

    $tag->delete();

    $income->refresh();
    $expense->refresh();

    expect($income->tags)->toHaveCount(0);
    expect($expense->tags)->toHaveCount(0);
});
