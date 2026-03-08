<?php

namespace App\Providers;

use App\Data\NavigationItem;
use App\Observers\TagObserver;
use App\Services\NavigationService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use SourcedOpen\Tags\Models\Tag;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(NavigationService::class);
    }

    public function boot(): void
    {
        $this->configureCommands();
        $this->configurePasswordValidation();
        $this->configureDates();
        $this->configureObservers();
        $this->configureNavigation();
    }

    private function configureObservers(): void
    {
        Tag::observe(TagObserver::class);
    }

    private function configureDates(): void
    {
        Date::use(CarbonImmutable::class);
    }

    private function configureCommands(): void
    {
        DB::prohibitDestructiveCommands(
            $this->app->isProduction()
        );
    }

    private function configurePasswordValidation(): void
    {
        Password::defaults(fn () => $this->app->isProduction() ? Password::min(8)->uncompromised() : null);
    }

    private function configureNavigation(): void
    {
        $nav = $this->app->make(NavigationService::class);

        $nav->add('individual', new NavigationItem('Accounts', 'accounts.index', 'icons.building-library', 'accounts.*', order: 10));
        $nav->add('individual', new NavigationItem('Incomes', 'incomes.index', 'icons.banknotes', 'incomes.*', order: 20));
        $nav->add('individual', new NavigationItem('Expenses', 'expenses.index', 'icons.credit-card', 'expenses.*', order: 30));
        $nav->add('individual', new NavigationItem('Transfers', 'transfers.index', 'icons.arrows-right-left', 'transfers.*', order: 40));
        $nav->add('individual', new NavigationItem('Tags', 'tags.index', 'icons.tag', 'tags.*', order: 50));
        $nav->add('individual', new NavigationItem('Projections', 'projected-dashboard', 'icons.chart-bar', 'projected-dashboard', order: 60));
        $nav->add('individual', new NavigationItem(
            label: 'Recurring',
            route: '#',
            icon: 'icons.arrow-path',
            activePattern: 'recurring-*',
            children: [
                new NavigationItem('Incomes', 'recurring-incomes.index', 'icons.banknotes', 'recurring-incomes.*'),
                new NavigationItem('Expenses', 'recurring-expenses.index', 'icons.credit-card', 'recurring-expenses.*'),
                new NavigationItem('Transfers', 'recurring-transfers.index', 'icons.arrows-right-left', 'recurring-transfers.*'),
                new NavigationItem('Pending', 'recurring-transactions.dashboard', 'icons.arrow-path', 'recurring-transactions.*'),
            ],
            order: 70,
        ));

        $nav->add('family', new NavigationItem('Accounts', 'family.accounts.index', 'icons.building-library', 'family.accounts.*', order: 10));
        $nav->add('family', new NavigationItem('Incomes', 'family.incomes.index', 'icons.banknotes', 'family.incomes.*', order: 20));
        $nav->add('family', new NavigationItem('Expenses', 'family.expenses.index', 'icons.credit-card', 'family.expenses.*', order: 30));
        $nav->add('family', new NavigationItem('Transfers', 'family.transfers.index', 'icons.arrows-right-left', 'family.transfers.*', order: 40));
        $nav->add('family', new NavigationItem('Projections', 'family.projected-dashboard', 'icons.chart-bar', 'family.projected-dashboard', order: 50));
        $nav->add('family', new NavigationItem(
            label: 'Recurring',
            route: '#',
            icon: 'icons.arrow-path',
            activePattern: 'family.recurring-*',
            children: [
                new NavigationItem('Incomes', 'family.recurring-incomes.index', 'icons.banknotes', 'family.recurring-incomes.*'),
                new NavigationItem('Expenses', 'family.recurring-expenses.index', 'icons.credit-card', 'family.recurring-expenses.*'),
                new NavigationItem('Transfers', 'family.recurring-transfers.index', 'icons.arrows-right-left', 'family.recurring-transfers.*'),
            ],
            order: 60,
        ));
    }
}
