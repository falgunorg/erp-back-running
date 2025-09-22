<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMachinesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('machines', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number', 30)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('title', 255)->nullable();
            $table->string('photo', 400)->nullable();
            $table->string('brand', 255)->nullable();
            $table->string('model', 255)->nullable();
            $table->string('type', 255)->nullable();
            $table->string('unit', 255)->nullable();
            $table->string('reference', 255)->nullable();
            $table->integer('efficiency')->nullable();
            $table->string('note', 1000)->nullable();
            $table->string('description', 5000)->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'Sold', 'Rented', 'Idle'])->default('Active');
            $table->string('purchase_date', 255)->nullable();
            $table->decimal('purchase_value', 10, 2)->nullable();
            $table->decimal('purchase_value_bdt', 10, 2)->nullable();
            $table->string('warranty_ends_at', 255)->nullable();
            $table->string('guarantee_ends_at', 255)->nullable();
            $table->string('ownership', 255)->default('Own');
            $table->string('category', 255)->default('Usual');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('machines');
    }
}
