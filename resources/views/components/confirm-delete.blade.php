@props([
    'action',
    'message' => 'Are you sure you want to delete this item?',
])

@php
    $modalId = 'confirm-delete-' . uniqid();
@endphp

<button type="button" {{ $attributes->merge(['class' => 'btn btn-ghost btn-sm text-error']) }} onclick="document.getElementById('{{ $modalId }}').showModal()">
    {{ $slot }}
</button>

<dialog id="{{ $modalId }}" class="modal">
    <div class="modal-box">
        <h3 class="text-lg font-bold">Confirm Delete</h3>
        <p class="py-4">{{ $message }}</p>
        <div class="modal-action">
            <form method="dialog">
                <button class="btn btn-ghost">Cancel</button>
            </form>
            <form action="{{ $action }}" method="POST">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-error">Delete</button>
            </form>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop">
        <button>close</button>
    </form>
</dialog>
