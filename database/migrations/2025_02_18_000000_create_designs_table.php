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
        // Creating the designs table
        Schema::create('designs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('photo', 500)->nullable();
            $table->string('design_number')->nullable();
            $table->string('title')->nullable();
            $table->string('design_type')->nullable();
            $table->string('buyers')->nullable();
            $table->string('description', 5000)->nullable();
            $table->enum('status', ['Pending', 'Approved', 'Making Sample', 'Testing', 'Finishing', 'Completed'])->default('Pending');
            $table->decimal('total', 10, 2)->nullable();
            $table->timestamps(); // Automatically adds created_at and updated_at columns
        });

        // Creating the design_files table
        Schema::create('design_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('design_id')->nullable();
            $table->string('filename')->nullable();
            $table->timestamps();
        });

        // Creating the design_items table
        Schema::create('design_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('design_id')->nullable();
            $table->unsignedBigInteger('item_id')->nullable();
            $table->string('description', 1000)->nullable();
            $table->string('color')->nullable();
            $table->string('unit')->nullable();
            $table->string('size')->nullable();
            $table->decimal('qty', 10, 2)->nullable();
            $table->decimal('rate', 10, 2)->nullable();
            $table->decimal('total', 10, 2)->nullable();
            $table->enum('status', ['Not Received', 'Some Received', 'Received', ''])->default('Not Received');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        // Dropping the tables in reverse order of creation
        Schema::dropIfExists('design_items');
        Schema::dropIfExists('design_files');
        Schema::dropIfExists('designs');
    }
};
