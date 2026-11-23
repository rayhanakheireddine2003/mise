<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierTest extends TestCase
{
    use RefreshDatabase;

    private function storekeeper(): User
    {
        return User::factory()->create(['role' => 'storekeeper']);
    }

    public function test_storekeeper_can_add_and_edit_a_supplier(): void
    {
        $user = $this->storekeeper();

        $this->actingAs($user)->post(route('suppliers.store'), ['name' => 'Hamra Poultry', 'phone' => '01 123 456']);
        $supplier = Supplier::firstWhere('name', 'Hamra Poultry');
        $this->assertNotNull($supplier);

        $this->actingAs($user)
            ->put(route('suppliers.update', $supplier), ['name' => 'Hamra Poultry', 'phone' => '03 999 999'])
            ->assertSessionHasNoErrors();

        $this->assertSame('03 999 999', $supplier->fresh()->phone);
    }

    public function test_duplicate_supplier_name_shows_an_error(): void
    {
        Supplier::create(['name' => 'Central Market']);

        $this->actingAs($this->storekeeper())
            ->post(route('suppliers.store'), ['name' => 'Central Market'])
            ->assertSessionHasErrors('name');
    }

    public function test_supplier_without_prices_can_be_deleted(): void
    {
        $supplier = Supplier::create(['name' => 'Old supplier']);

        $this->actingAs($this->storekeeper())
            ->delete(route('suppliers.destroy', $supplier))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    }

    public function test_supplier_with_prices_cannot_be_deleted(): void
    {
        $supplier = Supplier::create(['name' => 'Hamra Poultry']);
        $chicken = Ingredient::create(['name' => 'Chicken breast', 'base_unit' => 'g']);
        $chicken->prices()->create([
            'supplier_id' => $supplier->id, 'price' => 5, 'unit' => 'kg',
            'price_per_base_unit' => 0.005, 'effective_from' => '2026-11-01',
        ]);

        $this->actingAs($this->storekeeper())
            ->delete(route('suppliers.destroy', $supplier))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
    }
}
