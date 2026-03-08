<?php

namespace App\View\Components;

use App\Services\NavigationService;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Navigation extends Component
{
    /** @var array<int, \App\Data\NavigationItem> */
    public array $items;

    public function __construct(NavigationService $navigationService)
    {
        $group = request()->routeIs('family.*') ? 'family' : 'individual';
        $this->items = $navigationService->items($group);
    }

    public function render(): View
    {
        return view('components.navigation');
    }
}
