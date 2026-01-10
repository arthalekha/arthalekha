<?php

use App\Models\Expense;
use App\Models\Income;
use App\Models\Tag;
use App\Models\Transfer;
use App\Services\TagService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('tag can have incomes through MorphToMany', function () {
    $tag = Tag::factory()->create();
    $incomes = Income::factory()->count(2)->create();

    $tag->incomes()->attach($incomes);

    expect($tag->incomes)->toHaveCount(2);
    expect($tag->incomes->first())->toBeInstanceOf(Income::class);
});

test('tag can have expenses through MorphToMany', function () {
    $tag = Tag::factory()->create();
    $expenses = Expense::factory()->count(3)->create();

    $tag->expenses()->attach($expenses);

    expect($tag->expenses)->toHaveCount(3);
    expect($tag->expenses->first())->toBeInstanceOf(Expense::class);
});

test('tag can have transfers through MorphToMany', function () {
    $tag = Tag::factory()->create();
    $transfers = Transfer::factory()->count(2)->create();

    $tag->transfers()->attach($transfers);

    expect($tag->transfers)->toHaveCount(2);
    expect($tag->transfers->first())->toBeInstanceOf(Transfer::class);
});

test('tag can attach to income', function () {
    $tag = Tag::factory()->create();
    $income = Income::factory()->create();

    $tag->incomes()->attach($income);

    expect($tag->incomes)->toHaveCount(1);
    expect($tag->incomes->first()->id)->toBe($income->id);
});

test('tag can attach to expense', function () {
    $tag = Tag::factory()->create();
    $expense = Expense::factory()->create();

    $tag->expenses()->attach($expense);

    expect($tag->expenses)->toHaveCount(1);
    expect($tag->expenses->first()->id)->toBe($expense->id);
});

test('tag can attach to transfer', function () {
    $tag = Tag::factory()->create();
    $transfer = Transfer::factory()->create();

    $tag->transfers()->attach($transfer);

    expect($tag->transfers)->toHaveCount(1);
    expect($tag->transfers->first()->id)->toBe($transfer->id);
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
    $tag = Tag::factory()->create();
    $income = Income::factory()->create();

    $initialTags = Tag::factory()->count(2)->create();
    $income->tags()->attach($initialTags);
    expect($income->tags)->toHaveCount(2);

    $newTags = Tag::factory()->count(3)->create();
    $income->tags()->sync($newTags->pluck('id'));
    $income->refresh();

    expect($income->tags)->toHaveCount(3);
});

test('tags can be synced on expense', function () {
    $expense = Expense::factory()->create();

    $initialTags = Tag::factory()->count(2)->create();
    $expense->tags()->attach($initialTags);

    $newTags = Tag::factory()->count(1)->create();
    $expense->tags()->sync($newTags->pluck('id'));
    $expense->refresh();

    expect($expense->tags)->toHaveCount(1);
    expect($expense->tags->first()->id)->toBe($newTags->first()->id);
});

test('tags can be detached from models', function () {
    $tag = Tag::factory()->create();
    $expense = Expense::factory()->create();

    $expense->tags()->attach($tag);
    expect($expense->tags)->toHaveCount(1);

    $expense->tags()->detach($tag);
    $expense->refresh();

    expect($expense->tags)->toHaveCount(0);
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

test('mass assignment works for name and color', function () {
    $tag = Tag::create([
        'name' => 'Groceries',
        'color' => '#FF5733',
    ]);

    expect($tag->name)->toBe('Groceries');
    expect($tag->color)->toBe('#FF5733');
});

test('creating tag calls TagService clearCache', function () {
    $tagService = Mockery::mock(TagService::class);
    $tagService->shouldReceive('clearCache')
        ->once()
        ->withNoArgs();

    app()->instance(TagService::class, $tagService);

    Tag::factory()->create();
});

test('updating tag calls TagService clearCache', function () {
    $tag = Tag::factory()->create();

    $tagService = Mockery::mock(TagService::class);
    $tagService->shouldReceive('clearCache')
        ->once()
        ->withNoArgs();

    app()->instance(TagService::class, $tagService);

    $tag->update(['name' => 'Updated Name']);
});

test('deleting tag calls TagService clearCache', function () {
    $tag = Tag::factory()->create();

    $tagService = Mockery::mock(TagService::class);
    $tagService->shouldReceive('clearCache')
        ->once()
        ->withNoArgs();

    app()->instance(TagService::class, $tagService);

    $tag->delete();
});

test('multiple tags can be attached to same income', function () {
    $income = Income::factory()->create();
    $tags = Tag::factory()->count(5)->create();

    $income->tags()->attach($tags);

    expect($income->tags)->toHaveCount(5);
});

test('tag relationships return collections', function () {
    $tag = Tag::factory()->create();
    Income::factory()->create()->tags()->attach($tag);
    Expense::factory()->create()->tags()->attach($tag);
    Transfer::factory()->create()->tags()->attach($tag);

    expect($tag->incomes)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
    expect($tag->expenses)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
    expect($tag->transfers)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
});
