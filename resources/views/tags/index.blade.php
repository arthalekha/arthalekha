<x-layouts.app>
<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Tags</h1>
        <a href="{{ route('tags.create') }}" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add Tag
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            @if ($tags->isEmpty())
                <div class="text-center py-8">
                    <p class="text-base-content/70">No tags found.</p>
                    <a href="{{ route('tags.create') }}" class="btn btn-primary btn-sm mt-4">Create your first tag</a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Color</th>
                                <th>Preview</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tags as $tag)
                                <tr>
                                    <td class="font-medium">{{ $tag->name }}</td>
                                    <td>
                                        <code class="text-sm">{{ $tag->color }}</code>
                                    </td>
                                    <td>
                                        <span class="badge text-white" style="background-color: {{ $tag->color }}">
                                            {{ $tag->name }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('tags.show', $tag) }}" class="btn btn-ghost btn-sm tooltip" data-tip="View">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                </svg>
                                            </a>
                                            <a href="{{ route('tags.edit', $tag) }}" class="btn btn-ghost btn-sm tooltip" data-tip="Edit">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                                </svg>
                                            </a>
                                            <form action="{{ route('tags.destroy', $tag) }}" method="POST" class="inline"
                                                  onsubmit="return confirm('Are you sure you want to delete this tag?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-ghost btn-sm text-error">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $tags->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
</x-layouts.app>
