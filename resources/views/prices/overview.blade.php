<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Prices today</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-600">
                        <tr>
                            <th class="px-6 py-3">Ingredient</th>
                            <th class="px-6 py-3">Current price</th>
                            <th class="px-6 py-3">Supplier</th>
                            <th class="px-6 py-3">Since</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            @php($ingredient = $row['ingredient'])
                            @php($current = $row['current'])
                            <tr class="border-t">
                                <td class="px-6 py-3 font-medium text-gray-900">{{ $ingredient->name }}</td>
                                @if ($current)
                                    <td class="px-6 py-3">
                                        ${{ number_format($current->price, 2) }} / {{ $current->unit }}
                                        <span class="text-gray-500">({{ $current->perStandardUnitLabel($ingredient->base_unit) }})</span>
                                    </td>
                                    <td class="px-6 py-3">{{ $current->supplier->name }}</td>
                                    <td class="px-6 py-3">{{ $current->effective_from->format('d M Y') }}</td>
                                @else
                                    <td class="px-6 py-3 text-amber-700" colspan="3">No price yet</td>
                                @endif
                                <td class="px-6 py-3 text-right">
                                    <a href="{{ route('ingredients.prices.index', $ingredient) }}" class="text-indigo-600 hover:underline">
                                        History / new price
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-6 text-gray-500">No ingredients yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
