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

            {{-- Pack sizes: only once the ingredient exists (it needs an id) --}}
            @if ($ingredient->exists)
                <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                    <div>
                        <h3 class="font-semibold text-gray-800">Pack sizes</h3>
                        <p class="text-sm text-gray-500">How this ingredient is bought, e.g. 1 case = 5000 {{ $ingredient->base_unit }}.</p>
                    </div>

                    @forelse ($ingredient->units()->orderBy('name')->get() as $unit)
                        <div class="flex items-center justify-between border-b pb-2">
                            <span>1 <strong>{{ $unit->name }}</strong> = {{ (float) $unit->qty_in_base_unit }} {{ $ingredient->base_unit }}</span>
                            <form method="POST" action="{{ route('ingredient-units.destroy', $unit) }}"
                                  onsubmit="return confirm('Delete pack size {{ $unit->name }}?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-sm text-red-600 hover:underline">Delete</button>
                            </form>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No pack sizes yet.</p>
                    @endforelse

                    <form method="POST" action="{{ route('ingredients.units.store', $ingredient) }}" class="flex flex-wrap items-end gap-3">
                        @csrf
                        <div>
                            <x-input-label for="pack_name" value="Pack name" />
                            <x-text-input id="pack_name" name="pack_name" type="text" class="mt-1 w-40"
                                          :value="old('pack_name')" placeholder="e.g. case" required />
                        </div>
                        <div>
                            <x-input-label for="pack_qty" value="Contains ({{ $ingredient->base_unit }})" />
                            <x-text-input id="pack_qty" name="pack_qty" type="number" step="0.001" min="0"
                                          class="mt-1 w-40" :value="old('pack_qty')" required />
                        </div>
                        <x-secondary-button type="submit">Add pack size</x-secondary-button>
                    </form>
                    <x-input-error :messages="$errors->get('pack_name')" />
                    <x-input-error :messages="$errors->get('pack_qty')" />
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
