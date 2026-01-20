<?php

use App\Models\User;
use Database\Factories\TagFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('guest cannot access tags index', function () {
    $this->get(route('tags.index'))
        ->assertRedirect(route('login'));
});

test('authenticated user can view tags index', function () {
    TagFactory::new()->count(3)->create();

    $this->actingAs($this->user)
        ->get(route('tags.index'))
        ->assertSuccessful()
        ->assertViewIs('tags.index')
        ->assertViewHas('tags');
});

test('authenticated user can view create tag form', function () {
    $this->actingAs($this->user)
        ->get(route('tags.create'))
        ->assertSuccessful()
        ->assertViewIs('tags.create');
});

test('authenticated user can create a tag', function () {
    $tagData = [
        'name' => 'Test Tag',
        'color' => '#FF5733',
    ];

    $this->actingAs($this->user)
        ->post(route('tags.store'), $tagData)
        ->assertRedirect(route('tags.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('tags', $tagData);
});

test('creating a tag requires a name', function () {
    $this->actingAs($this->user)
        ->post(route('tags.store'), [
            'name' => '',
            'color' => '#FF5733',
        ])
        ->assertSessionHasErrors('name');
});

test('creating a tag requires a valid hex color', function () {
    $this->actingAs($this->user)
        ->post(route('tags.store'), [
            'name' => 'Test Tag',
            'color' => 'invalid',
        ])
        ->assertSessionHasErrors('color');
});

test('tag name must be unique', function () {
    TagFactory::new()->create(['name' => 'Existing Tag']);

    $this->actingAs($this->user)
        ->post(route('tags.store'), [
            'name' => 'Existing Tag',
            'color' => '#FF5733',
        ])
        ->assertSessionHasErrors('name');
});

test('authenticated user can view a tag', function () {
    $tag = TagFactory::new()->create();

    $this->actingAs($this->user)
        ->get(route('tags.show', $tag))
        ->assertSuccessful()
        ->assertViewIs('tags.show')
        ->assertViewHas('tag', $tag);
});

test('authenticated user can view edit tag form', function () {
    $tag = TagFactory::new()->create();

    $this->actingAs($this->user)
        ->get(route('tags.edit', $tag))
        ->assertSuccessful()
        ->assertViewIs('tags.edit')
        ->assertViewHas('tag', $tag);
});

test('authenticated user can update a tag', function () {
    $tag = TagFactory::new()->create();

    $updatedData = [
        'name' => 'Updated Tag',
        'color' => '#00FF00',
    ];

    $this->actingAs($this->user)
        ->put(route('tags.update', $tag), $updatedData)
        ->assertRedirect(route('tags.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('tags', $updatedData);
});

test('updating a tag allows keeping the same name', function () {
    $tag = TagFactory::new()->create(['name' => 'Original Name']);

    $this->actingAs($this->user)
        ->put(route('tags.update', $tag), [
            'name' => 'Original Name',
            'color' => '#00FF00',
        ])
        ->assertRedirect(route('tags.index'))
        ->assertSessionHas('success');
});

test('authenticated user can delete a tag', function () {
    $tag = TagFactory::new()->create();

    $this->actingAs($this->user)
        ->delete(route('tags.destroy', $tag))
        ->assertRedirect(route('tags.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
});
