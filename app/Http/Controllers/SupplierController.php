<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * MISE-7: suppliers list, add, edit, delete.
 * A supplier with prices or deliveries can't be deleted (price history must stay).
 */
class SupplierController extends Controller
{
    public function index()
    {
        $suppliers = Supplier::withCount('prices')->orderBy('name')->get();

        return view('suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('suppliers.form', ['supplier' => new Supplier]);
    }

    public function store(Request $request)
    {
        $supplier = Supplier::create($this->validated($request));

        return redirect()->route('suppliers.index')
            ->with('status', "Supplier \"{$supplier->name}\" added.");
    }

    public function edit(Supplier $supplier)
    {
        return view('suppliers.form', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $supplier->update($this->validated($request, $supplier));

        return redirect()->route('suppliers.index')
            ->with('status', "Supplier \"{$supplier->name}\" updated.");
    }

    public function destroy(Supplier $supplier)
    {
        // Prices are never deleted (architecture rule 3), so a supplier
        // that has prices or deliveries has to stay.
        if ($supplier->prices()->exists() || $supplier->purchases()->exists()) {
            return redirect()->route('suppliers.index')
                ->with('error', "\"{$supplier->name}\" has prices or deliveries, so it can't be deleted.");
        }

        $supplier->delete();

        return redirect()->route('suppliers.index')
            ->with('status', "Supplier \"{$supplier->name}\" deleted.");
    }

    private function validated(Request $request, ?Supplier $supplier = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('suppliers', 'name')->ignore($supplier)],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);
    }
}
