@props(['tags'])

@if($tags->count() > 0)
<div class="flex flex-wrap gap-1">
    @foreach ($tags as $tag)
        <span class="badge badge-sm text-white" style="background-color: {{ $tag->color }}">
            {{ $tag->name }}
        </span>
    @endforeach
</div>
@else
<span class="text-base-content/50">-</span>
@endif
