<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        // Create costings table
        Schema::create('costings', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key (default 'id' field)
            $table->string('costing_number')->nullable(); // Costing number as varchar, nullable
            $table->unsignedBigInteger('user_id')->nullable()->constrained()->onDelete('set null'); // Foreign key for user, nullable
            $table->unsignedBigInteger('techpack_id')->nullable()->constrained()->onDelete('set null'); // Foreign key for techpack, nullable
            $table->decimal('total', 10, 2)->default(0.00); // Total as decimal with default value 0.00
            $table->timestamps(); // Created_at and updated_at timestamps
        });

        // Create costing_items table
        Schema::create('costing_items', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key (default 'id' field)
            $table->unsignedBigInteger('item_id')->nullable()->constrained()->onDelete('set null'); // Foreign key for item, nullable
            $table->unsignedBigInteger('costing_id')->nullable()->constrained('costings')->onDelete('cascade'); // Foreign key for costing, nullable
            $table->string('description', 500)->nullable(); // Description as varchar, nullable with max length of 500
            $table->string('actual')->nullable(); // Actual cost as varchar, nullable
            $table->string('wastage_parcentage')->nullable(); // Wastage percentage as varchar, nullable
            $table->decimal('cons_total', 10, 2)->nullable(); // Consumable total cost as decimal, nullable
            $table->string('unit')->nullable(); // Unit as varchar, nullable
            $table->string('size')->nullable(); // Size as varchar, nullable
            $table->string('color')->nullable(); // Color as varchar, nullable
            $table->decimal('unit_price', 10, 2)->nullable(); // Unit price as decimal, nullable
            $table->decimal('total', 10, 2)->default(0.00); // Total as decimal with default value 0.00
            $table->timestamps(); // Created_at and updated_at timestamps
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('costing_items');
        Schema::dropIfExists('costings');
    }
};
