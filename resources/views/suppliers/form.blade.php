{{-- One form for both "add" and "edit": $supplier->exists is true when editing. --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $supplier->exists ? "Edit {$supplier->name}" : 'Add supplier' }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST"
                      action="{{ $supplier->exists ? route('suppliers.update', $supplier) : route('suppliers.store') }}"
                      class="space-y-5">
                    @csrf
                    @if ($supplier->exists)
                        @method('PUT')
                    @endif

                    <div>
                        <x-input-label for="name" value="Name" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                                      :value="old('name', $supplier->name)" required autofocus />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="phone" value="Phone (optional)" />
                        <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full"
                                      :value="old('phone', $supplier->phone)" />
                        <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>{{ $supplier->exists ? 'Save changes' : 'Add supplier' }}</x-primary-button>
                        <a href="{{ route('suppliers.index') }}" class="text-sm text-gray-600 underline">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
