<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up() {
        Schema::create('purchase_contracts', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->unsignedBigInteger('buyer_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('year', 11)->nullable();
            $table->integer('total_qty')->default(0);
            $table->decimal('total_amount', 10, 2)->default(0.00);
            $table->string('currency')->nullable();
            $table->string('pcc_avail')->nullable();
            $table->string('tag_number')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('issued_date')->nullable();
            $table->string('shipment_date')->nullable();
            $table->string('expiry_date')->nullable();
            $table->string('revised_date')->nullable();
            $table->string('revised_note', 500)->nullable();
            $table->string('season')->nullable();
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('purchase_contracts');
    }
};
