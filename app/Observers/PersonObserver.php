<?php

namespace App\Observers;

use App\Models\Person;
use App\Services\PersonService;

class PersonObserver
{
    public function __construct(private PersonService $personService) {}

    /**
     * Handle the Person "created" event.
     */
    public function created(Person $person): void
    {
        $this->personService->clearCache();
    }

    /**
     * Handle the Person "updated" event.
     */
    public function updated(Person $person): void
    {
        $this->personService->clearCache();
    }

    /**
     * Handle the Person "deleted" event.
     */
    public function deleted(Person $person): void
    {
        $this->personService->clearCache();
    }
}
