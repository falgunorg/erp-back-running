<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up() {
        Schema::create('receives', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('booking_item_id')->nullable();
            $table->unsignedBigInteger('booking_user_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('buyer_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('mrr_number')->nullable();
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->unsignedBigInteger('budget_id')->nullable();
            $table->unsignedBigInteger('budget_item_id')->nullable();
            $table->unsignedBigInteger('techpack_id')->nullable();
            $table->string('challan_no')->nullable();
            $table->string('challan_copy', 500)->nullable();
            $table->string('gate_pass')->nullable();
            $table->string('description', 500)->nullable();
            $table->string('remarks', 500)->nullable();
            $table->string('color')->nullable();
            $table->string('size')->nullable();
            $table->string('shade')->nullable();
            $table->string('tex')->nullable();
            $table->string('unit')->nullable();
            $table->decimal('qty', 10, 2)->nullable();
            $table->string('photo')->nullable();
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('receives');
    }
};
