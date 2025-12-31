<?php

namespace App\Services;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class TagService
{
    private const CACHE_KEY = 'tags.all';

    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Get all tags (cached).
     *
     * @return Collection<int, Tag>
     */
    public function getAll(): Collection
    {
        return Cache::remember(
            self::CACHE_KEY,
            self::CACHE_TTL,
            fn () => Tag::all()
        );
    }

    /**
     * Clear the tags cache.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
