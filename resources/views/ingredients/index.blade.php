<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Ingredients</h2>
            <a href="{{ route('ingredients.create') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-800 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                Add ingredient
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Search and filter (a GET form: the choices end up in the URL) --}}
            <form method="GET" action="{{ route('ingredients.index') }}"
                  class="bg-white shadow-sm sm:rounded-lg p-4 flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-48">
                    <x-input-label for="q" value="Search" />
                    <x-text-input id="q" name="q" type="search" class="mt-1 block w-full"
                                  :value="$search" placeholder="Ingredient name" />
                </div>
                <div>
                    <x-input-label for="area" value="Storage area" />
                    <select id="area" name="area" class="mt-1 border-gray-300 rounded-md shadow-sm">
                        <option value="">All areas</option>
                        @foreach ($areas as $area)
                            <option value="{{ $area->id }}" @selected($areaId == $area->id)>{{ $area->name }}</option>
                        @endforeach
                    </select>
                </div>
                <x-secondary-button type="submit">Filter</x-secondary-button>
                @if ($search || $areaId)
                    <a href="{{ route('ingredients.index') }}" class="text-sm text-gray-600 underline">Clear</a>
                @endif
            </form>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-600">
                        <tr>
                            <th class="px-6 py-3">Name</th>
                            <th class="px-6 py-3">Base unit</th>
                            <th class="px-6 py-3">Storage area</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($ingredients as $ingredient)
                            <tr class="border-t">
                                <td class="px-6 py-3 font-medium text-gray-900">{{ $ingredient->name }}</td>
                                <td class="px-6 py-3">
                                    {{ $ingredient->base_unit }}
                                    @if ($ingredient->grams_per_piece)
                                        <span class="text-gray-500">({{ (float) $ingredient->grams_per_piece }} g each)</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3">{{ $ingredient->storageArea?->name ?? '—' }}</td>
                                <td class="px-6 py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('ingredients.edit', $ingredient) }}" class="text-indigo-600 hover:underline">Edit</a>
                                    <form method="POST" action="{{ route('ingredients.destroy', $ingredient) }}" class="inline ml-3"
                                          onsubmit="return confirm('Delete {{ $ingredient->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-red-600 hover:underline">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-6 text-gray-500">No ingredients found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $ingredients->links() }}
        </div>
    </div>
</x-app-layout>
