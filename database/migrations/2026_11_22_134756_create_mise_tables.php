<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * All Mise tables in one migration.
 *
 * Order matters: a table that points to another table (a foreign key)
 * must be created AFTER the table it points to. down() drops them in
 * the reverse order.
 *
 * Rules to remember (see CLAUDE.md):
 *  - Every quantity is stored in the ingredient's base unit (g, ml or piece).
 *  - Money and quantities are decimals, never floats.
 *  - Prices are never updated: a new price is a new ingredient_prices row.
 *  - Only Laravel schema methods, no raw SQL, so this also works on MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Where an ingredient is kept: "Cold room", "Dry store", "Freezer"...
        Schema::create('storage_areas', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('base_unit'); // g, ml or piece (Ingredient::BASE_UNITS)
            $table->decimal('grams_per_piece', 10, 3)->nullable(); // e.g. 1 lemon = 120 g
            // If the storage area is deleted, the ingredient stays (area becomes empty).
            $table->foreignId('storage_area_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        // Pack sizes, e.g. "tin" = 4000 ml of vegetable oil.
        Schema::create('ingredient_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // e.g. case, tin, bag
            $table->decimal('qty_in_base_unit', 14, 3);
            $table->timestamps();

            $table->unique(['ingredient_id', 'name']);
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        // Price history. Never update or delete a row: add a new one instead.
        Schema::create('ingredient_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            // restrict: a supplier that has prices can't be deleted.
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->decimal('price', 12, 4);              // what the supplier charges...
            $table->string('unit');                       // ...for this unit (kg, tin, piece...)
            $table->decimal('price_per_base_unit', 14, 6); // price for 1 g / 1 ml / 1 piece
            $table->date('effective_from');
            $table->timestamps();

            $table->index(['ingredient_id', 'effective_from']);
        });

        // A delivery / invoice from a supplier.
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->string('invoice_no')->nullable();
            $table->date('purchased_on');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // who entered it
            $table->timestamps();
        });

        Schema::create('purchase_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->restrictOnDelete();
            $table->decimal('qty', 12, 3);
            $table->string('unit');
            $table->decimal('unit_price', 12, 4);
            $table->timestamps();
        });

        // A dish on the menu, or a sub-recipe (e.g. garlic sauce) used inside dishes.
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_sub_recipe')->default(false);
            $table->decimal('yield_qty', 12, 3)->default(1);
            $table->string('yield_unit')->default('portion');
            $table->decimal('waste_pct', 5, 2)->default(0);
            $table->decimal('menu_price', 10, 2)->nullable(); // sub-recipes have no menu price
            $table->decimal('target_cost_pct', 5, 2)->default(30);
            $table->timestamps();
        });

        // One line of a recipe. Exactly ONE of ingredient_id or sub_recipe_id is set
        // (checked in the RecipeLine model, MISE-10).
        Schema::create('recipe_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('sub_recipe_id')->nullable()->constrained('recipes')->restrictOnDelete();
            $table->decimal('qty', 12, 3);
            $table->string('unit');
            $table->timestamps();
        });

        Schema::create('stock_counts', function (Blueprint $table) {
            $table->id();
            $table->date('counted_on');
            $table->string('status')->default('draft'); // draft or done
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('count_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_count_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->restrictOnDelete();
            $table->decimal('qty_in_base_unit', 14, 3);
            $table->timestamps();

            // Each ingredient appears at most once per count.
            $table->unique(['stock_count_id', 'ingredient_id']);
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->restrictOnDelete();
            $table->date('sold_on');
            $table->decimal('qty', 10, 2);
            $table->timestamps();
        });

        // Created when a price change pushes a dish above its target food-cost %.
        Schema::create('margin_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->decimal('old_cost_pct', 6, 2);
            $table->decimal('new_cost_pct', 6, 2);
            $table->boolean('seen')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Reverse order of up().
        Schema::dropIfExists('margin_alerts');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('count_lines');
        Schema::dropIfExists('stock_counts');
        Schema::dropIfExists('recipe_lines');
        Schema::dropIfExists('recipes');
        Schema::dropIfExists('purchase_lines');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('ingredient_prices');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('ingredient_units');
        Schema::dropIfExists('ingredients');
        Schema::dropIfExists('storage_areas');
    }
};
