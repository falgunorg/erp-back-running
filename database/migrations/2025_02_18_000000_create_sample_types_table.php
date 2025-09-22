<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSampleTypesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('sample_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('buyer_id')->nullable()->constrained()->onDelete('set null');
            $table->unsignedBigInteger('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('title', 255)->nullable();
            $table->timestamps(0);  // created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('sample_types');
    }
}
