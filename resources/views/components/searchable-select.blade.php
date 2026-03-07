@props([
    'name',
    'options',
    'selected' => '',
    'placeholder' => 'Select an option',
    'required' => false,
    'size' => 'md',
    'hasError' => false,
])

@php
    $inputClass = $size === 'sm' ? 'input input-bordered input-sm' : 'input input-bordered';
    $errorClass = $hasError ? ($size === 'sm' ? 'input-error' : 'input-error') : '';
    $uniqueId = 'ss-' . $name . '-' . uniqid();
@endphp

<div class="relative" data-searchable-select="{{ $uniqueId }}">
    <input type="hidden" name="{{ $name }}" value="{{ $selected }}">
    <input
        type="text"
        class="{{ $inputClass }} {{ $errorClass }} w-full"
        placeholder="{{ $placeholder }}"
        autocomplete="off"
        data-ss-search
        value="{{ collect($options)->firstWhere('value', $selected)['label'] ?? '' }}"
    >
    <div class="absolute z-50 w-full mt-1 bg-base-100 border border-base-300 rounded-box shadow-lg max-h-48 overflow-y-auto hidden" data-ss-dropdown>
        @foreach ($options as $option)
            <div
                class="px-3 py-2 cursor-pointer hover:bg-base-200 text-sm"
                data-ss-option
                data-ss-value="{{ $option['value'] }}"
                data-ss-label="{{ $option['label'] }}"
            >
                {{ $option['label'] }}
            </div>
        @endforeach
    </div>
</div>

@once
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-searchable-select]').forEach(function (container) {
            const hiddenInput = container.querySelector('input[type="hidden"]');
            const searchInput = container.querySelector('[data-ss-search]');
            const dropdown = container.querySelector('[data-ss-dropdown]');
            const options = container.querySelectorAll('[data-ss-option]');

            searchInput.addEventListener('focus', function () {
                dropdown.classList.remove('hidden');
                filterOptions('');
            });

            searchInput.addEventListener('input', function () {
                dropdown.classList.remove('hidden');
                filterOptions(this.value);
                if (this.value === '') {
                    hiddenInput.value = '';
                }
            });

            options.forEach(function (option) {
                option.addEventListener('click', function () {
                    hiddenInput.value = this.dataset.ssValue;
                    searchInput.value = this.dataset.ssLabel;
                    dropdown.classList.add('hidden');
                });
            });

            document.addEventListener('click', function (e) {
                if (!container.contains(e.target)) {
                    dropdown.classList.add('hidden');
                    if (hiddenInput.value) {
                        const selected = container.querySelector('[data-ss-value="' + hiddenInput.value + '"]');
                        if (selected) {
                            searchInput.value = selected.dataset.ssLabel;
                        }
                    }
                }
            });

            function filterOptions(query) {
                const lower = query.toLowerCase();
                options.forEach(function (option) {
                    const match = option.dataset.ssLabel.toLowerCase().includes(lower);
                    option.style.display = match ? '' : 'none';
                });
            }
        });
    });
</script>
@endonce
