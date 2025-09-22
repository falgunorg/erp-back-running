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
    public function up()
    {
        Schema::create('banks', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255)->nullable();
            $table->string('branch', 255)->nullable();
            $table->string('account_number', 255)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('tel', 255)->nullable();
            $table->string('fax', 255)->nullable();
            $table->string('swift_code', 255)->nullable();
            $table->string('routing_number', 255)->nullable();
            $table->string('country', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('banks');
    }
};
