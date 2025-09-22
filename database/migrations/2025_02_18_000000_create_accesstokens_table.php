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
        Schema::create('accesstokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('ip', 50)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->string('token', 255)->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('accesstokens');
    }
};
