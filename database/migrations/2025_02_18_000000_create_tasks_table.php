<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTasksTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id(); // Auto incrementing primary key
            $table->string('title', 255)->nullable();
            $table->string('description', 1000)->nullable();
            $table->enum('status', ['pending', 'processing', 'completed'])->default('pending');
            $table->unsignedBigInteger('column_id')->nullable()->constrained('columns')->onDelete('set null'); // Assuming `columns` table exists for the foreign key reference
            $table->timestamps(0); // created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('tasks');
    }
}
