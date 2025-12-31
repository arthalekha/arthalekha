<?php

namespace App\Services;

use App\Models\Person;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class PersonService
{
    private const CACHE_KEY = 'persons.all';

    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Get all persons (cached).
     *
     * @return Collection<int, Person>
     */
    public function getAll(): Collection
    {
        return Cache::remember(
            self::CACHE_KEY,
            self::CACHE_TTL,
            fn () => Person::all()
        );
    }

    /**
     * Clear the persons cache.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
