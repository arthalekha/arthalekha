<?php

namespace App\Services;

use App\Data\NavigationItem;

class NavigationService
{
    /** @var array<string, array<int, NavigationItem>> */
    protected array $items = [];

    public function add(string $group, NavigationItem $item): void
    {
        $this->items[$group][] = $item;
    }

    /**
     * @return array<int, NavigationItem>
     */
    public function items(string $group): array
    {
        $items = $this->items[$group] ?? [];

        usort($items, fn (NavigationItem $a, NavigationItem $b) => $a->order <=> $b->order);

        return $items;
    }
}
