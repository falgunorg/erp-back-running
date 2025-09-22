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
        // Create lcs table
        Schema::create('lcs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->string('serial_number', 255)->nullable();
            $table->string('proformas', 500);
            $table->string('apply_date', 255)->nullable();
            $table->string('issued_date', 255)->nullable();
            $table->string('lc_number', 255)->nullable();
            $table->string('lc_validity', 255)->nullable();
            $table->string('currency', 255)->nullable();
            $table->unsignedBigInteger('bank')->nullable();
            $table->decimal('total', 10, 2)->nullable();
            $table->string('commodity', 255)->nullable();
            $table->string('maturity_date', 255)->nullable();
            $table->string('paid_date', 255)->nullable();
            $table->string('pcc_avail', 255)->nullable();
            $table->enum('status', ['Pending', 'Approved', 'Rejected', 'Material In housed', 'Pending Payment', 'Paid'])->default('Pending');
            $table->timestamps();
        });

        // Create lc_items table
        Schema::create('lc_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lc_id')->nullable();
            $table->string('pi_number', 255)->nullable();
            $table->decimal('total', 10, 2)->nullable();
            $table->unsignedBigInteger('purchase_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('lc_items');
        Schema::dropIfExists('lcs');
    }
};
