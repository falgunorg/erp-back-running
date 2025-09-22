<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSchedulesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255)->nullable();
            $table->string('startDate', 255)->nullable();
            $table->string('endDate', 255)->nullable();
            $table->unsignedBigInteger('ownerId')->nullable()->constrained('users')->onDelete('set null');
            $table->string('rRule', 255)->nullable();
            $table->boolean('allDay')->nullable();
            $table->string('notes', 500)->nullable();
            $table->enum('priority', ['low', 'medium', 'high'])->default('low');
            $table->timestamps(0);  // created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('schedules');
    }
}
