<?php

use App\Models\Expense;
use App\Models\Income;
use App\Models\Transfer;
use App\Services\TagService;
use Database\Factories\TagFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use SourcedOpen\Tags\Models\Tag;

uses(RefreshDatabase::class);

test('tag can have incomes through taggables', function () {
    $tag = TagFactory::new()->create();
    $incomes = Income::factory()->count(2)->create();

    foreach ($incomes as $income) {
        $income->tags()->attach($tag);
    }

    expect($tag->taggables(Income::class)->get())->toHaveCount(2);
    expect($tag->taggables(Income::class)->first())->toBeInstanceOf(Income::class);
});

test('tag can have expenses through taggables', function () {
    $tag = TagFactory::new()->create();
    $expenses = Expense::factory()->count(3)->create();

    foreach ($expenses as $expense) {
        $expense->tags()->attach($tag);
    }

    expect($tag->taggables(Expense::class)->get())->toHaveCount(3);
    expect($tag->taggables(Expense::class)->first())->toBeInstanceOf(Expense::class);
});

test('tag can have transfers through taggables', function () {
    $tag = TagFactory::new()->create();
    $transfers = Transfer::factory()->count(2)->create();

    foreach ($transfers as $transfer) {
        $transfer->tags()->attach($tag);
    }

    expect($tag->taggables(Transfer::class)->get())->toHaveCount(2);
    expect($tag->taggables(Transfer::class)->first())->toBeInstanceOf(Transfer::class);
});

test('tag can be attached to income', function () {
    $tag = TagFactory::new()->create();
    $income = Income::factory()->create();

    $income->tags()->attach($tag);

    expect($tag->taggables(Income::class)->get())->toHaveCount(1);
    expect($tag->taggables(Income::class)->first()->id)->toBe($income->id);
});

test('tag can be attached to expense', function () {
    $tag = TagFactory::new()->create();
    $expense = Expense::factory()->create();

    $expense->tags()->attach($tag);

    expect($tag->taggables(Expense::class)->get())->toHaveCount(1);
    expect($tag->taggables(Expense::class)->first()->id)->toBe($expense->id);
});

test('tag can be attached to transfer', function () {
    $tag = TagFactory::new()->create();
    $transfer = Transfer::factory()->create();

    $transfer->tags()->attach($tag);

    expect($tag->taggables(Transfer::class)->get())->toHaveCount(1);
    expect($tag->taggables(Transfer::class)->first()->id)->toBe($transfer->id);
});

test('same tag can be attached to multiple model types', function () {
    $tag = TagFactory::new()->create();
    $income = Income::factory()->create();
    $expense = Expense::factory()->create();
    $transfer = Transfer::factory()->create();

    $income->tags()->attach($tag);
    $expense->tags()->attach($tag);
    $transfer->tags()->attach($tag);

    expect($tag->taggables(Income::class)->get())->toHaveCount(1);
    expect($tag->taggables(Expense::class)->get())->toHaveCount(1);
    expect($tag->taggables(Transfer::class)->get())->toHaveCount(1);
});

test('tags can be synced on income', function () {
    $income = Income::factory()->create();

    $initialTags = TagFactory::new()->count(2)->create();
    $income->tags()->attach($initialTags);
    expect($income->tags)->toHaveCount(2);

    $newTags = TagFactory::new()->count(3)->create();
    $income->syncTags($newTags->pluck('id')->toArray());
    $income->refresh();

    expect($income->tags)->toHaveCount(3);
});

test('tags can be synced on expense', function () {
    $expense = Expense::factory()->create();

    $initialTags = TagFactory::new()->count(2)->create();
    $expense->tags()->attach($initialTags);

    $newTags = TagFactory::new()->count(1)->create();
    $expense->syncTags($newTags->pluck('id')->toArray());
    $expense->refresh();

    expect($expense->tags)->toHaveCount(1);
    expect($expense->tags->first()->id)->toBe($newTags->first()->id);
});

test('tags can be detached from models', function () {
    $tag = TagFactory::new()->create();
    $expense = Expense::factory()->create();

    $expense->tags()->attach($tag);
    expect($expense->tags)->toHaveCount(1);

    $expense->detachTags($tag->id);
    $expense->refresh();

    expect($expense->tags)->toHaveCount(0);
});

test('deleting tag removes pivot entries', function () {
    $tag = TagFactory::new()->create();
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

    TagFactory::new()->create();
});

test('updating tag calls TagService clearCache', function () {
    $tag = TagFactory::new()->create();

    $tagService = Mockery::mock(TagService::class);
    $tagService->shouldReceive('clearCache')
        ->once()
        ->withNoArgs();

    app()->instance(TagService::class, $tagService);

    $tag->update(['name' => 'Updated Name']);
});

test('deleting tag calls TagService clearCache', function () {
    $tag = TagFactory::new()->create();

    $tagService = Mockery::mock(TagService::class);
    $tagService->shouldReceive('clearCache')
        ->once()
        ->withNoArgs();

    app()->instance(TagService::class, $tagService);

    $tag->delete();
});

test('multiple tags can be attached to same income', function () {
    $income = Income::factory()->create();
    $tags = TagFactory::new()->count(5)->create();

    $income->attachTags($tags->pluck('id')->toArray());

    expect($income->tags)->toHaveCount(5);
});

test('tag relationships return collections', function () {
    $tag = TagFactory::new()->create();
    Income::factory()->create()->tags()->attach($tag);
    Expense::factory()->create()->tags()->attach($tag);
    Transfer::factory()->create()->tags()->attach($tag);

    expect($tag->taggables(Income::class)->get())->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
    expect($tag->taggables(Expense::class)->get())->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
    expect($tag->taggables(Transfer::class)->get())->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
});
