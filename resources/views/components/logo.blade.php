<svg {{ $attributes->merge(['class' => 'size-8']) }} viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg" fill="none">
    {{-- Left page --}}
    <path d="M4 8 C10 6, 16 6, 20 8 L20 36 C16 34, 10 34, 4 36 Z"
          fill="currentColor" opacity="0.15" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
    {{-- Right page --}}
    <path d="M36 8 C30 6, 24 6, 20 8 L20 36 C24 34, 30 34, 36 36 Z"
          fill="currentColor" opacity="0.1" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
    {{-- Ledger lines on left page --}}
    <line x1="9" y1="16" x2="18" y2="15" stroke="currentColor" stroke-width="1.5" opacity="0.4" stroke-linecap="round"/>
    <line x1="9" y1="22" x2="18" y2="21" stroke="currentColor" stroke-width="1.5" opacity="0.4" stroke-linecap="round"/>
    <line x1="9" y1="28" x2="18" y2="27" stroke="currentColor" stroke-width="1.5" opacity="0.4" stroke-linecap="round"/>
    {{-- Rising trend on right page --}}
    <polyline points="23,28 26,23 29,25 34,16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    <polyline points="31,16 34,16 34,19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
