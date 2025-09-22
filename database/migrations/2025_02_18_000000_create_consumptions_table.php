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
        // Create consumptions table
        Schema::create('consumptions', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key (default 'id' field)
            $table->string('consumption_number')->nullable(); // Consumption number as varchar, nullable
            $table->unsignedBigInteger('user_id')->nullable()->constrained()->onDelete('set null'); // Foreign key for user, nullable
            $table->unsignedBigInteger('techpack_id')->nullable()->constrained()->onDelete('set null'); // Foreign key for techpack, nullable
            $table->string('description', 500)->nullable(); // Description as varchar, nullable with max length of 500
            $table->string('status')->nullable(); // Status as varchar, nullable
            $table->timestamps(); // Created_at and updated_at timestamps
        });

        // Create consumption_files table
        Schema::create('consumption_files', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key (default 'id' field)
            $table->unsignedBigInteger('consumption_id')->nullable()->constrained('consumptions')->onDelete('cascade'); // Foreign key for consumption, nullable
            $table->string('filename', 500)->nullable(); // Filename as varchar, nullable with max length of 500
            $table->timestamps(); // Created_at and updated_at timestamps
        });

        // Create consumption_items table
        Schema::create('consumption_items', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key (default 'id' field)
            $table->unsignedBigInteger('consumption_id')->nullable()->constrained('consumptions')->onDelete('cascade'); // Foreign key for consumption, nullable
            $table->unsignedBigInteger('item_id')->nullable()->constrained()->onDelete('set null'); // Foreign key for item, nullable
            $table->string('description')->nullable(); // Description as varchar, nullable
            $table->string('unit'); // Unit as varchar, required
            $table->string('size')->nullable(); // Size as varchar, nullable
            $table->string('color')->nullable(); // Color as varchar, nullable
            $table->string('qty')->nullable(); // Quantity as varchar, nullable
            $table->timestamps(); // Created_at and updated_at timestamps
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('consumption_items');
        Schema::dropIfExists('consumption_files');
        Schema::dropIfExists('consumptions');
    }
};
