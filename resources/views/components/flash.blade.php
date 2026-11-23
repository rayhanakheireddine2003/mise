{{--
    Shows the one-time messages a controller sends with
    ->with('status', '...') (green) or ->with('error', '...') (red).
    It is included once in layouts/app.blade.php, so every page gets it.
--}}
@if (session('status'))
    <div class="max-w-7xl mx-auto mt-6 px-4 sm:px-6 lg:px-8">
        <div class="rounded-md bg-green-50 border border-green-200 p-4 text-sm text-green-800">
            {{ session('status') }}
        </div>
    </div>
@endif

@if (session('error'))
    <div class="max-w-7xl mx-auto mt-6 px-4 sm:px-6 lg:px-8">
        <div class="rounded-md bg-red-50 border border-red-200 p-4 text-sm text-red-800">
            {{ session('error') }}
        </div>
    </div>
@endif
