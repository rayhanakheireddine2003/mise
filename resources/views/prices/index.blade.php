<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Prices: {{ $ingredient->name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- New price. Old prices are never changed: this always adds a new line. --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Record a new price</h3>

                @if ($suppliers->isEmpty())
                    <p class="text-sm text-amber-700">
                        Add a supplier first on the <a href="{{ route('suppliers.create') }}" class="underline">Suppliers</a> page.
                    </p>
                @else
                    <form method="POST" action="{{ route('ingredients.prices.store', $ingredient) }}"
                          class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-start">
                        @csrf
                        <div>
                            <x-input-label for="supplier_id" value="Supplier" />
                            <select id="supplier_id" name="supplier_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" @selected(old('supplier_id', $current?->supplier_id) == $supplier->id)>{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('supplier_id')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="price" value="Price ($)" />
                            <x-text-input id="price" name="price" type="number" step="0.01" min="0" class="mt-1 block w-full"
                                          :value="old('price')" required />
                            <x-input-error :messages="$errors->get('price')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="unit" value="Per" />
                            <select id="unit" name="unit" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                @foreach ($units as $unit)
                                    <option value="{{ $unit }}" @selected(old('unit', $current?->unit) === $unit)>{{ $unit }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('unit')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="effective_from" value="Valid from" />
                            <x-text-input id="effective_from" name="effective_from" type="date" class="mt-1 block w-full"
                                          :value="old('effective_from', now()->toDateString())" required />
                            <x-input-error :messages="$errors->get('effective_from')" class="mt-2" />
                        </div>
                        <div class="sm:col-span-4">
                            <x-primary-button>Save new price</x-primary-button>
                        </div>
                    </form>
                @endif
            </div>

            {{-- History, newest first --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-600">
                        <tr>
                            <th class="px-6 py-3">Valid from</th>
                            <th class="px-6 py-3">Price</th>
                            <th class="px-6 py-3">Same as</th>
                            <th class="px-6 py-3">Supplier</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($history as $price)
                            <tr class="border-t {{ $current?->is($price) ? 'bg-green-50' : '' }}">
                                <td class="px-6 py-3">
                                    {{ $price->effective_from->format('d M Y') }}
                                    @if ($current?->is($price))
                                        <span class="ml-2 text-xs font-semibold text-green-700">current</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3">${{ number_format($price->price, 2) }} / {{ $price->unit }}</td>
                                <td class="px-6 py-3 text-gray-600">{{ $price->perStandardUnitLabel($ingredient->base_unit) }}</td>
                                <td class="px-6 py-3">{{ $price->supplier->name }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-6 text-gray-500">No prices yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <a href="{{ route('prices.overview') }}" class="inline-block mt-2 text-sm text-gray-600 underline">← All prices</a>
        </div>
    </div>
</x-app-layout>
