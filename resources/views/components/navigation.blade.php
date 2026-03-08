@foreach ($items as $item)
    <li>
        @if ($item->children)
            <details>
                <summary @class(['active' => request()->routeIs($item->activePattern)])>
                    <x-dynamic-component :component="$item->icon" />
                    {{ $item->label }}
                </summary>
                <ul class="bg-base-100 rounded-t-none p-2 z-1 w-48">
                    @foreach ($item->children as $child)
                        <li>
                            <a href="{{ route($child->route) }}" @class(['active' => request()->routeIs($child->activePattern)])>
                                <x-dynamic-component :component="$child->icon" />
                                {{ $child->label }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </details>
        @else
            <a href="{{ route($item->route) }}" @class(['active' => request()->routeIs($item->activePattern)])>
                <x-dynamic-component :component="$item->icon" />
                {{ $item->label }}
            </a>
        @endif
    </li>
@endforeach
