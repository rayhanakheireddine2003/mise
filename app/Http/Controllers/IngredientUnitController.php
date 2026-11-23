<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\IngredientUnit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * MISE-6: pack sizes of an ingredient, e.g. "tin" = 4000 ml of vegetable oil.
 * They are added and deleted from the ingredient's edit page.
 */
class IngredientUnitController extends Controller
{
    /**
     * Names a pack size can't use, because they are standard units already.
     * TODO after MISE-2: use array_keys(UnitConverter::STANDARD_UNITS) instead.
     */
    private const RESERVED_NAMES = ['g', 'kg', 'ml', 'l', 'piece'];

    public function store(Request $request, Ingredient $ingredient)
    {
        // The fields are called pack_name / pack_qty (not "name") so they don't
        // mix with the ingredient's own "name" field on the same page.
        $request->validate([
            'pack_name' => [
                'required', 'string', 'max:50',
                Rule::notIn(self::RESERVED_NAMES),
                // The same ingredient can't have two pack sizes with the same name.
                Rule::unique('ingredient_units', 'name')->where('ingredient_id', $ingredient->id),
            ],
            'pack_qty' => ['required', 'numeric', 'gt:0', 'max:10000000'],
        ], [
            'pack_name.not_in' => 'That is a standard unit already. Choose a pack name like "case" or "bag".',
            'pack_name.unique' => 'This ingredient already has a pack size with that name.',
        ]);

        $unit = $ingredient->units()->create([
            'name' => $request->input('pack_name'),
            'qty_in_base_unit' => $request->input('pack_qty'),
        ]);

        return redirect()->route('ingredients.edit', $ingredient)
            ->with('status', "Pack size \"{$unit->name}\" added.");
    }

    public function destroy(IngredientUnit $unit)
    {
        $unit->delete();

        return redirect()->route('ingredients.edit', $unit->ingredient_id)
            ->with('status', "Pack size \"{$unit->name}\" deleted.");
    }
}
