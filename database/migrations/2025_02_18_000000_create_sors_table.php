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
        // Create sors table
        Schema::create('sors', function (Blueprint $table) {
            $table->id();
            $table->string('sor_number', 255)->nullable();
            $table->unsignedBigInteger('buyer_id')->nullable()->constrained('buyers')->onDelete('set null');
            $table->unsignedBigInteger('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->unsignedBigInteger('techpack_id')->nullable()->constrained('techpacks')->onDelete('set null');
            $table->string('season', 255)->nullable();
            $table->unsignedBigInteger('sample_type')->nullable()->constrained('sample_types')->onDelete('set null');
            $table->string('qty', 255)->nullable();
            $table->string('sizes', 255)->nullable();
            $table->string('colors', 255)->nullable();
            $table->string('photo', 500)->nullable();
            $table->string('issued_date', 255)->nullable();
            $table->string('delivery_date', 255)->nullable();
            $table->enum('status', [
                'Pending', 'Confirmed', 'Received With Material', 'Not Received',
                'On Cutting', 'On Sewing', 'On Finishing', 'Completed',
                'Making Pattern', 'Others', 'Testing'
            ])->default('Pending');
            $table->unsignedBigInteger('action_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps(0);  // created_at and updated_at
            $table->string('remarks', 1000)->nullable();
            $table->string('operations', 500)->nullable();
        });

        // Create sors_files table
        Schema::create('sors_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sor_id')->nullable()->constrained('sors')->onDelete('set null');
            $table->string('filename', 300)->nullable();
            $table->timestamps(0);  // created_at and updated_at
        });

        // Create sor_items table
        Schema::create('sor_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sor_id')->nullable()->constrained('sors')->onDelete('set null');
            $table->unsignedBigInteger('sample_store_id')->nullable()->constrained('sample_store')->onDelete('set null');
            $table->string('description', 1000)->nullable();
            $table->string('color', 255)->nullable();
            $table->string('unit', 255)->nullable();
            $table->string('size', 255)->nullable();
            $table->decimal('per_pc_cons', 10, 2)->nullable();
            $table->decimal('total', 10, 2)->nullable();
            $table->enum('status', ['Not Received', 'Received'])->default('Not Received');
            $table->timestamps(0);  // created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('sor_items');
        Schema::dropIfExists('sors_files');
        Schema::dropIfExists('sors');
    }
};
