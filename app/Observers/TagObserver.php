<?php

namespace App\Observers;

use App\Models\Tag;
use App\Services\TagService;

class TagObserver
{
    public function __construct(private TagService $tagService) {}

    /**
     * Handle the Tag "created" event.
     */
    public function created(Tag $tag): void
    {
        $this->tagService->clearCache();
    }

    /**
     * Handle the Tag "updated" event.
     */
    public function updated(Tag $tag): void
    {
        $this->tagService->clearCache();
    }

    /**
     * Handle the Tag "deleted" event.
     */
    public function deleted(Tag $tag): void
    {
        $this->tagService->clearCache();
    }
}
