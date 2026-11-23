{{-- One form for both "add" and "edit": $ingredient->exists is true when editing. --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $ingredient->exists ? "Edit {$ingredient->name}" : 'Add ingredient' }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST"
                      action="{{ $ingredient->exists ? route('ingredients.update', $ingredient) : route('ingredients.store') }}"
                      class="space-y-5">
                    @csrf
                    @if ($ingredient->exists)
                        @method('PUT')
                    @endif

                    <div>
                        <x-input-label for="name" value="Name" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                                      :value="old('name', $ingredient->name)" required autofocus />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="base_unit" value="Base unit (how we store and count it)" />
                        <select id="base_unit" name="base_unit" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                            @foreach (\App\Models\Ingredient::BASE_UNITS as $unit)
                                <option value="{{ $unit }}" @selected(old('base_unit', $ingredient->base_unit) === $unit)>{{ $unit }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('base_unit')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="grams_per_piece" value="Grams per piece (only for 'piece', optional)" />
                        <x-text-input id="grams_per_piece" name="grams_per_piece" type="number" step="0.001" min="0"
                                      class="mt-1 block w-full" :value="old('grams_per_piece', $ingredient->grams_per_piece)"
                                      placeholder="e.g. 120 for a lemon" />
                        <x-input-error :messages="$errors->get('grams_per_piece')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="storage_area_id" value="Storage area" />
                        <select id="storage_area_id" name="storage_area_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">— None —</option>
                            @foreach ($areas as $area)
                                <option value="{{ $area->id }}" @selected(old('storage_area_id', $ingredient->storage_area_id) == $area->id)>{{ $area->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('storage_area_id')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>{{ $ingredient->exists ? 'Save changes' : 'Add ingredient' }}</x-primary-button>
                        <a href="{{ route('ingredients.index') }}" class="text-sm text-gray-600 underline">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
