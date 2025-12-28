@extends('layouts.app')

@section('content')
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
            <div class="flex justify-between items-start">
                <h2 class="card-title text-2xl font-bold">{{ $tag->name }}</h2>
                <div class="flex gap-2">
                    <a href="{{ route('tags.edit', $tag) }}" class="btn btn-ghost btn-sm">Edit</a>
                    <form action="{{ route('tags.destroy', $tag) }}" method="POST" class="inline"
                          onsubmit="return confirm('Are you sure you want to delete this tag?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-ghost btn-sm text-error">Delete</button>
                    </form>
                </div>
            </div>

            <div class="divider"></div>

            <div class="space-y-4">
                <div>
                    <label class="text-sm font-medium text-base-content/70">Preview</label>
                    <div class="mt-1">
                        <span class="badge badge-lg text-white" style="background-color: {{ $tag->color }}">
                            {{ $tag->name }}
                        </span>
                    </div>
                </div>

                <div>
                    <label class="text-sm font-medium text-base-content/70">Color</label>
                    <div class="flex items-center gap-3 mt-1">
                        <div class="w-8 h-8 rounded border" style="background-color: {{ $tag->color }}"></div>
                        <code class="text-sm">{{ $tag->color }}</code>
                    </div>
                </div>

                <div>
                    <label class="text-sm font-medium text-base-content/70">Created</label>
                    <p class="mt-1">{{ $tag->created_at->format('F d, Y \a\t h:i A') }}</p>
                </div>

                <div>
                    <label class="text-sm font-medium text-base-content/70">Last Updated</label>
                    <p class="mt-1">{{ $tag->updated_at->format('F d, Y \a\t h:i A') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

