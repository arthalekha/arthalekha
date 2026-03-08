<?php

namespace App\Data;

class NavigationItem
{
    /**
     * @param  array<int, NavigationItem>  $children
     */
    public function __construct(
        public string $label,
        public string $route,
        public string $icon,
        public string $activePattern,
        public array $children = [],
        public int $order = 0,
    ) {}
}
