<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStylesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Create styles table
        Schema::create('styles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('title', 255)->nullable();
            $table->unsignedBigInteger('buyer_id')->nullable()->constrained('buyers')->onDelete('set null');
            $table->timestamps(0);  // created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('styles');
    }
}
