<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateColorsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('colors', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key (default 'id' field)
            $table->string('title')->nullable(); // Title as varchar, nullable
            $table->unsignedBigInteger('user_id')->nullable()->constrained()->onDelete('set null'); // Foreign key for user, nullable
            $table->timestamps(); // Created_at and updated_at timestamps
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('colors');
    }
}
