<?php

namespace App\Observers;

use App\Services\TagService;
use SourcedOpen\Tags\Models\Tag;

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
