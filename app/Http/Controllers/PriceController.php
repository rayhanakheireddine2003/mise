<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\Supplier;
use App\Services\PriceService;
use App\Services\UnitConverter;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * MISE-9: prices overview, price history of one ingredient, and the
 * "new price" form. The rules themselves are in App\Services\PriceService.
 */
class PriceController extends Controller
{
    /** Every ingredient with the price in effect today. */
    public function overview(PriceService $prices)
    {
        $today = now()->toDateString();

        $rows = Ingredient::orderBy('name')->get()->map(fn (Ingredient $ingredient) => [
            'ingredient' => $ingredient,
            'current' => $prices->priceAt($ingredient, $today)?->load('supplier'),
        ]);

        return view('prices.overview', compact('rows'));
    }

    /** All prices of one ingredient (newest first) + the form to add one. */
    public function index(Ingredient $ingredient, UnitConverter $converter, PriceService $prices)
    {
        return view('prices.index', [
            'ingredient' => $ingredient,
            'history' => $ingredient->prices()->with('supplier')
                ->orderByDesc('effective_from')->orderByDesc('id')->get(),
            'current' => $prices->priceAt($ingredient, now()->toDateString()),
            'suppliers' => Supplier::orderBy('name')->get(),
            'units' => $converter->unitsFor($ingredient),
        ]);
    }

    public function store(Request $request, Ingredient $ingredient, PriceService $prices)
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'price' => ['required', 'numeric', 'gt:0', 'max:1000000'],
            'unit' => ['required', 'string', 'max:50'],
            'effective_from' => ['required', 'date'],
        ]);

        try {
            $prices->record($ingredient, (int) $data['supplier_id'], (float) $data['price'], $data['unit'], $data['effective_from']);
        } catch (InvalidArgumentException $e) {
            // e.g. chicken "per ml": show the message under the Unit field.
            throw ValidationException::withMessages(['unit' => $e->getMessage()]);
        }

        return redirect()->route('ingredients.prices.index', $ingredient)
            ->with('status', "New price for {$ingredient->name} saved. The old prices stay in the history.");
    }
}
