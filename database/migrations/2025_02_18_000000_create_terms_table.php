<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTermsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Creating the terms table
        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255)->nullable();
            $table->longText('description')->nullable();
            $table->timestamps(0); // created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Dropping the terms table
        Schema::dropIfExists('terms');
    }
}
