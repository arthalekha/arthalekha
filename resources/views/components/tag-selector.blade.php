@props(['tags', 'selected' => []])

@if($tags->count() > 0)
<div class="form-control mb-4">
    <label class="label">
        <span class="label-text">Tags (Optional)</span>
    </label>
    <div class="flex flex-wrap gap-2">
        @foreach ($tags as $tag)
            <label class="cursor-pointer flex items-center gap-2 px-3 py-1 rounded-full border border-base-300 hover:bg-base-200 transition-colors has-[:checked]:bg-primary/10 has-[:checked]:border-primary">
                <input type="checkbox" name="tags[]" value="{{ $tag->id }}"
                       class="checkbox checkbox-sm checkbox-primary"
                       @checked(in_array($tag->id, old('tags', $selected)))>
                <span class="text-sm" style="color: {{ $tag->color }}">{{ $tag->name }}</span>
            </label>
        @endforeach
    </div>
    @error('tags')
        <label class="label">
            <span class="label-text-alt text-error">{{ $message }}</span>
        </label>
    @enderror
</div>
@endif
