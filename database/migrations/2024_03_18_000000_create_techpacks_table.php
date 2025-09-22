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
        // Creating the techpacks table
        Schema::create('techpacks', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255)->nullable();
            $table->string('techpack_number', 255)->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->constrained('users')->onDelete('set null'); // Assuming `users` table exists
            $table->unsignedBigInteger('buyer_id')->nullable()->constrained('buyers')->onDelete('set null'); // Assuming `buyers` table exists
            $table->string('season', 255)->nullable();
            $table->string('item_type', 255)->nullable();
            $table->string('description', 5000)->nullable();
            $table->string('item_name', 255)->nullable();
            $table->string('sizes', 500)->nullable();
            $table->string('wash_details', 255)->nullable();
            $table->string('operations', 500)->nullable();
            $table->string('fds_shrinkage_length', 255)->nullable();
            $table->string('fds_shrinkage_width', 255)->nullable();
            $table->string('fds_gsm', 255)->nullable();
            $table->string('fds_width', 255)->nullable();
            $table->string('fds_composition', 255)->nullable();
            $table->string('techpack_file', 500)->nullable();
            $table->string('specsheet', 500)->nullable();
            $table->string('block_pattern', 500)->nullable();
            $table->string('photo', 500)->nullable();
            $table->enum('status', ['Pending', 'Placed', 'Consumption Done', 'Sample Done', 'Costing Done'])->default('Pending');
            $table->string('placed_at', 100)->nullable();
            $table->unsignedBigInteger('consumption_by')->default(0);
            $table->string('consumption_at', 100)->nullable();
            $table->unsignedBigInteger('costing_by')->default(0);
            $table->string('costing_at', 100)->nullable();
            $table->string('sample_approve_at', 100)->nullable();
            $table->timestamps(0); // created_at and updated_at
        });

        // Creating the techpack_files table
        Schema::create('techpack_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('techpack_id')->nullable()->constrained('techpacks')->onDelete('cascade'); // Assuming `techpacks` table exists
            $table->string('filename', 500)->nullable();
            $table->timestamps(0); // created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        // Dropping the techpack_files and techpacks tables
        Schema::dropIfExists('techpack_files');
        Schema::dropIfExists('techpacks');
    }
};
