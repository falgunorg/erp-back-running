<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up() {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('sd_po')->nullable();
            $table->string('po_number', 100)->nullable();
            $table->string('sizes', 500)->nullable();
            $table->string('colors', 500)->nullable();
            $table->string('lead_time')->nullable();
            $table->string('booking_time')->nullable();
            $table->string('material_inhouse_time')->nullable();
            $table->string('production_time')->nullable();
            $table->string('save_time')->nullable();
            $table->integer('contract_id')->nullable();
            $table->string('shipping_method')->nullable();
            $table->integer('techpack_id')->nullable();
            $table->string('order_date', 100)->nullable();
            $table->string('revised_date', 100)->nullable();
            $table->string('revised_note', 500)->nullable();
            $table->string('delivery_address', 500)->nullable();
            $table->string('shipment_date', 100)->nullable();
            $table->string('packing_instructions', 1000)->nullable();
            $table->string('packing_method', 1000)->nullable();
            $table->string('comment', 1000)->nullable();
            $table->string('total_qty')->nullable();
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->enum('status', ['Pending', 'Recieved', 'Cancelled'])->default('Pending');
            $table->timestamps();
        });

        Schema::create('purchases_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_id')->nullable();
            $table->string('filename', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_id')->nullable();
            $table->string('description', 1000)->nullable();
            $table->string('size', 255)->nullable();
            $table->string('color', 255)->nullable();
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->string('qty', 255)->nullable();
            $table->decimal('total', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases_files');
        Schema::dropIfExists('purchases');
    }
};
