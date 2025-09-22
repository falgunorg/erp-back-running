<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id(); // Auto-increment primary key
            $table->string('title', 255)->nullable();
            $table->unsignedBigInteger('receiver')->nullable();
            $table->string('url', 255)->nullable();
            $table->string('description', 500)->nullable();
            $table->boolean('is_read')->default(0); // Boolean for read status
            $table->timestamps(); // Adds created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notifications');
    }
}
