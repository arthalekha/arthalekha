<?php

use App\Data\NavigationItem;
use App\Services\NavigationService;

it('stores and retrieves navigation items for a group', function () {
    $service = new NavigationService;

    $service->add('individual', new NavigationItem('Accounts', 'accounts.index', 'icons.building-library', 'accounts.*'));
    $service->add('individual', new NavigationItem('Incomes', 'incomes.index', 'icons.banknotes', 'incomes.*'));

    $items = $service->items('individual');

    expect($items)->toHaveCount(2)
        ->and($items[0]->label)->toBe('Accounts')
        ->and($items[1]->label)->toBe('Incomes');
});

it('returns items sorted by order', function () {
    $service = new NavigationService;

    $service->add('individual', new NavigationItem('Expenses', 'expenses.index', 'icons.credit-card', 'expenses.*', order: 30));
    $service->add('individual', new NavigationItem('Accounts', 'accounts.index', 'icons.building-library', 'accounts.*', order: 10));
    $service->add('individual', new NavigationItem('Incomes', 'incomes.index', 'icons.banknotes', 'incomes.*', order: 20));

    $items = $service->items('individual');

    expect($items[0]->label)->toBe('Accounts')
        ->and($items[1]->label)->toBe('Incomes')
        ->and($items[2]->label)->toBe('Expenses');
});

it('returns empty array for non-existent group', function () {
    $service = new NavigationService;

    expect($service->items('nonexistent'))->toBe([]);
});

it('keeps groups separate', function () {
    $service = new NavigationService;

    $service->add('individual', new NavigationItem('Accounts', 'accounts.index', 'icons.building-library', 'accounts.*'));
    $service->add('family', new NavigationItem('Family Accounts', 'family.accounts.index', 'icons.building-library', 'family.accounts.*'));

    expect($service->items('individual'))->toHaveCount(1)
        ->and($service->items('individual')[0]->label)->toBe('Accounts')
        ->and($service->items('family'))->toHaveCount(1)
        ->and($service->items('family')[0]->label)->toBe('Family Accounts');
});

it('supports items with children', function () {
    $service = new NavigationService;

    $service->add('individual', new NavigationItem(
        label: 'Recurring',
        route: '#',
        icon: 'icons.arrow-path',
        activePattern: 'recurring-*',
        children: [
            new NavigationItem('Incomes', 'recurring-incomes.index', 'icons.banknotes', 'recurring-incomes.*'),
            new NavigationItem('Expenses', 'recurring-expenses.index', 'icons.credit-card', 'recurring-expenses.*'),
        ],
    ));

    $items = $service->items('individual');

    expect($items)->toHaveCount(1)
        ->and($items[0]->children)->toHaveCount(2)
        ->and($items[0]->children[0]->label)->toBe('Incomes')
        ->and($items[0]->children[1]->label)->toBe('Expenses');
});
