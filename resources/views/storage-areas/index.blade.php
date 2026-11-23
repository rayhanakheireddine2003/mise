<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Storage areas</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Add a new area --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('storage-areas.store') }}" class="flex items-end gap-3">
                    @csrf
                    <div class="flex-1">
                        <x-input-label for="name" value="New storage area" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                                      :value="old('name')" placeholder="e.g. Cold room" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>
                    <x-primary-button>Add</x-primary-button>
                </form>
            </div>

            {{-- List of areas --}}
            <div class="bg-white shadow-sm sm:rounded-lg">
                @forelse ($areas as $area)
                    <div class="flex items-center justify-between px-6 py-4 border-b last:border-b-0">
                        <div>
                            <div class="font-medium text-gray-900">{{ $area->name }}</div>
                            <div class="text-sm text-gray-500">{{ $area->ingredients_count }} ingredient(s)</div>
                        </div>
                        <form method="POST" action="{{ route('storage-areas.destroy', $area) }}"
                              onsubmit="return confirm('Delete {{ $area->name }}? Its ingredients are kept.')">
                            @csrf
                            @method('DELETE')
                            <x-danger-button>Delete</x-danger-button>
                        </form>
                    </div>
                @empty
                    <p class="p-6 text-gray-500">No storage areas yet. Add your first one above.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
