<?php

namespace App\Http\Controllers;

use App\Models\StorageArea;
use Illuminate\Http\Request;

/**
 * MISE-4: the storage areas page (Cold room, Dry store, Freezer...).
 * List, add and delete on a single page.
 */
class StorageAreaController extends Controller
{
    public function index()
    {
        $areas = StorageArea::withCount('ingredients')->orderBy('name')->get();

        return view('storage-areas.index', compact('areas'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:storage_areas,name'],
        ]);

        StorageArea::create($data);

        return redirect()->route('storage-areas.index')
            ->with('status', "Storage area \"{$data['name']}\" added.");
    }

    public function destroy(StorageArea $storageArea)
    {
        // Ingredients in this area are kept: the database sets their
        // storage_area_id to empty (nullOnDelete in the migration).
        $storageArea->delete();

        return redirect()->route('storage-areas.index')
            ->with('status', "Storage area \"{$storageArea->name}\" deleted.");
    }
}
