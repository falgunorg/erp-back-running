<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHolidaysTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->date('date')->nullable();
            $table->timestamps(); // Automatically adds created_at and updated_at columns
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('holidays');
    }
}
