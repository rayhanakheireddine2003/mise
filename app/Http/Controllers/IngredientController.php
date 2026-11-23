<?php

namespace App\Http\Controllers;

use App\Models\CountLine;
use App\Models\Ingredient;
use App\Models\PurchaseLine;
use App\Models\RecipeLine;
use App\Models\StorageArea;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * MISE-5: ingredients list (with search and area filter), add, edit, delete.
 */
class IngredientController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('q');
        $areaId = $request->query('area');

        $ingredients = Ingredient::with('storageArea')
            // ?q=chick  -> names containing "chick"
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
            // ?area=3   -> only ingredients in storage area 3
            ->when($areaId, fn ($query) => $query->where('storage_area_id', $areaId))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString(); // keep ?q and ?area on the page links

        $areas = StorageArea::orderBy('name')->get();

        return view('ingredients.index', compact('ingredients', 'areas', 'search', 'areaId'));
    }

    public function create()
    {
        return view('ingredients.form', [
            'ingredient' => new Ingredient,
            'areas' => StorageArea::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $ingredient = Ingredient::create($this->validated($request));

        return redirect()->route('ingredients.edit', $ingredient)
            ->with('status', "Ingredient \"{$ingredient->name}\" added. You can add pack sizes below.");
    }

    public function edit(Ingredient $ingredient)
    {
        return view('ingredients.form', [
            'ingredient' => $ingredient,
            'areas' => StorageArea::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Ingredient $ingredient)
    {
        $ingredient->update($this->validated($request, $ingredient));

        return redirect()->route('ingredients.index')
            ->with('status', "Ingredient \"{$ingredient->name}\" updated.");
    }

    public function destroy(Ingredient $ingredient)
    {
        // An ingredient used in a recipe, a delivery or a stock count can't be
        // deleted, or those records would point to nothing.
        $inUse = RecipeLine::where('ingredient_id', $ingredient->id)->exists()
            || PurchaseLine::where('ingredient_id', $ingredient->id)->exists()
            || CountLine::where('ingredient_id', $ingredient->id)->exists();

        if ($inUse) {
            return redirect()->route('ingredients.index')
                ->with('error', "\"{$ingredient->name}\" is used in recipes, deliveries or stock counts, so it can't be deleted.");
        }

        $ingredient->delete(); // its pack sizes and prices are deleted with it (cascade)

        return redirect()->route('ingredients.index')
            ->with('status', "Ingredient \"{$ingredient->name}\" deleted.");
    }

    /**
     * The validation rules shared by store() and update().
     * $ingredient is passed on update, so its own name doesn't count as a duplicate.
     */
    private function validated(Request $request, ?Ingredient $ingredient = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('ingredients', 'name')->ignore($ingredient)],
            'base_unit' => ['required', Rule::in(Ingredient::BASE_UNITS)],
            'grams_per_piece' => ['nullable', 'numeric', 'gt:0', 'max:100000'],
            'storage_area_id' => ['nullable', 'exists:storage_areas,id'],
        ]);

        // grams_per_piece only makes sense for ingredients counted in pieces.
        if ($data['base_unit'] !== 'piece') {
            $data['grams_per_piece'] = null;
        }

        return $data;
    }
}
