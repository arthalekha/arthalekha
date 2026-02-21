<x-layouts.app>
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('tags.index') }}" class="btn btn-ghost btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Back to Tags
        </a>
    </div>

    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <h2 class="card-title text-2xl font-bold mb-4">Create Tag</h2>

            <form action="{{ route('tags.store') }}" method="POST">
                @csrf

                <div class="form-control mb-4">
                    <label class="label" for="name">
                        <span class="label-text">Name</span>
                    </label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}"
                           class="input input-bordered @error('name') input-error @enderror"
                           placeholder="Enter tag name" required>
                    @error('name')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <div class="form-control mb-4">
                    <label class="label" for="color">
                        <span class="label-text">Color</span>
                    </label>
                    <div class="flex gap-3 items-center">
                        <input type="color" name="color" id="color" value="{{ old('color', '#3B82F6') }}"
                               class="w-16 h-12 rounded cursor-pointer border-0">
                        <input type="text" id="color_text" value="{{ old('color', '#3B82F6') }}"
                               class="input input-bordered flex-1 @error('color') input-error @enderror"
                               placeholder="#000000" maxlength="7" readonly>
                    </div>
                    @error('color')
                        <label class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </label>
                    @enderror
                </div>

                <div class="form-control mb-6">
                    <label class="label">
                        <span class="label-text">Preview</span>
                    </label>
                    <div class="p-4 bg-base-200 rounded-lg">
                        <span id="tag-preview" class="badge text-white" style="background-color: {{ old('color', '#3B82F6') }}">
                            {{ old('name', 'Tag Preview') }}
                        </span>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('tags.index') }}" class="btn btn-ghost">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Tag</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const colorInput = document.getElementById('color');
        const colorText = document.getElementById('color_text');
        const nameInput = document.getElementById('name');
        const preview = document.getElementById('tag-preview');

        colorInput.addEventListener('input', function() {
            colorText.value = this.value.toUpperCase();
            preview.style.backgroundColor = this.value;
        });

        nameInput.addEventListener('input', function() {
            preview.textContent = this.value || 'Tag Preview';
        });
    });
</script>
</x-layouts.app>
