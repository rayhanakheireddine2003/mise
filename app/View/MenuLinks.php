<?php

namespace App\View;

use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * MISE-3: the Mise links in the top menu, and who may see each one.
 *
 * To add a page to the menu, add one line here. Keep "roles" the same as the
 * role:... middleware on its route in routes/web.php.
 */
class MenuLinks
{
    private const LINKS = [
        ['label' => 'Recipes',       'route' => 'recipes.index',       'active' => 'recipes.*',       'roles' => ['owner', 'chef']],
        ['label' => 'Ingredients',   'route' => 'ingredients.index',   'active' => 'ingredients.*',   'roles' => ['owner', 'chef']],
        ['label' => 'Storage areas', 'route' => 'storage-areas.index', 'active' => 'storage-areas.*', 'roles' => ['owner', 'chef']],
        ['label' => 'Suppliers',     'route' => 'suppliers.index',     'active' => 'suppliers.*',     'roles' => ['owner', 'storekeeper']],
        ['label' => 'Prices',        'route' => 'prices.overview',     'active' => 'prices.*',        'roles' => ['owner', 'storekeeper']],
    ];

    /**
     * The links this user should see: their role is allowed AND the page
     * exists already (Route::has), so links appear as pages get merged.
     */
    public static function for(User $user): array
    {
        return array_values(array_filter(
            self::LINKS,
            fn (array $link) => $user->hasRole(...$link['roles']) && Route::has($link['route'])
        ));
    }
}
