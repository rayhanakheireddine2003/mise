<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\StorageArea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorageAreaTest extends TestCase
{
    use RefreshDatabase;

    public function test_chef_can_add_a_storage_area(): void
    {
        $chef = User::factory()->create(['role' => 'chef']);

        $this->actingAs($chef)
            ->post(route('storage-areas.store'), ['name' => 'Cold room'])
            ->assertRedirect(route('storage-areas.index'));

        $this->assertDatabaseHas('storage_areas', ['name' => 'Cold room']);
        $this->actingAs($chef)->get(route('storage-areas.index'))->assertSee('Cold room');
    }

    public function test_duplicate_storage_area_name_shows_an_error(): void
    {
        $chef = User::factory()->create(['role' => 'chef']);
        StorageArea::create(['name' => 'Freezer']);

        $this->actingAs($chef)
            ->post(route('storage-areas.store'), ['name' => 'Freezer'])
            ->assertSessionHasErrors('name');

        $this->assertSame(1, StorageArea::count());
    }

    public function test_deleting_an_area_keeps_its_ingredients(): void
    {
        $chef = User::factory()->create(['role' => 'chef']);
        $area = StorageArea::create(['name' => 'Dry store']);
        $salt = Ingredient::create(['name' => 'Salt', 'base_unit' => 'g', 'storage_area_id' => $area->id]);

        $this->actingAs($chef)->delete(route('storage-areas.destroy', $area));

        $this->assertDatabaseMissing('storage_areas', ['id' => $area->id]);
        $this->assertNull($salt->fresh()->storage_area_id);
    }
}
