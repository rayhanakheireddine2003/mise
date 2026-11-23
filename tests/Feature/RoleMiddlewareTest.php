<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * MISE-3: the "role" middleware from MISE-2.
 * A small test-only route protected with role:owner,chef is used,
 * so these tests don't depend on any real page.
 */
class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth', 'role:owner,chef'])
            ->get('/_test/kitchen-only', fn () => 'welcome to the kitchen');
    }

    public function test_listed_roles_are_let_in(): void
    {
        foreach (['owner', 'chef'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get('/_test/kitchen-only')
                ->assertOk()
                ->assertSee('welcome to the kitchen');
        }
    }

    public function test_other_roles_get_403(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'storekeeper']))
            ->get('/_test/kitchen-only')
            ->assertForbidden();
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/_test/kitchen-only')->assertRedirect(route('login'));
    }
}
