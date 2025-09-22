<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePosTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('pos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->nullable();
            $table->string('po_number')->nullable();
            $table->integer('wo_id')->nullable();
            $table->date('issued_date')->nullable();
            $table->date('delivery_date')->nullable();
            $table->integer('purchase_contract_id')->nullable();
            $table->integer('company_id')->nullable();
            $table->integer('buyer_id')->nullable();
            $table->string('brand')->nullable();
            $table->string('season')->nullable();
            $table->string('description')->nullable();
            $table->integer('technical_package_id')->nullable();
            $table->string('buyer_style_name')->nullable();
            $table->string('item_name')->nullable();
            $table->string('item_type')->nullable();
            $table->string('department')->nullable();
            $table->string('wash_details')->nullable();
            $table->string('destination')->nullable();
            $table->string('ship_mode')->nullable();
            $table->integer('shipping_terms')->nullable();
            $table->string('packing_method')->nullable();
            $table->integer('payment_terms')->nullable();
            $table->integer('total_qty')->nullable();
            $table->decimal('total_value', 10, 2)->default(0);
            $table->string('special_operations')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('pos');
    }
}
