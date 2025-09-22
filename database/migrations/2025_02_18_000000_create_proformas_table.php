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
        Schema::create('proformas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('proforma_number', 255)->nullable();
            $table->unsignedBigInteger('purchase_contract_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('title', 255)->nullable();
            $table->string('currency', 255)->nullable();
            $table->string('issued_date', 255)->nullable();
            $table->string('delivery_date', 255)->nullable();
            $table->string('pi_validity', 255)->nullable();
            $table->string('net_weight', 255)->nullable();
            $table->string('gross_weight', 255)->nullable();
            $table->string('freight_charge', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('bank_account_name', 300)->nullable();
            $table->string('bank_account_number', 255)->nullable();
            $table->string('bank_brunch_name', 255)->nullable();
            $table->string('bank_name', 255)->nullable();
            $table->string('bank_address', 255)->nullable();
            $table->string('bank_swift_code', 255)->nullable();
            $table->decimal('total', 10, 2)->nullable();
            $table->enum('status', ['Pending', 'Placed', 'Confirmed', 'Submitted', 'Checked', 'Cost-Approved', 'Finalized', 'Received', 'BTB-Submitted', 'Rejected', 'Approved'])->default('Pending');

            // Tracking fields
            $actions = ['placed', 'confirmed', 'submitted', 'checked', 'cost_approved', 'finalized', 'approved', 'received', 'btb_submit', 'rejected'];
            foreach ($actions as $action) {
                $table->unsignedBigInteger("{$action}_by")->default(0);
                $table->string("{$action}_at", 100)->nullable();
            }

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('proforma_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('proforma_id')->nullable();
            $table->string('filename', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('proforma_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('proforma_id')->nullable();
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->unsignedBigInteger('booking_item_id')->nullable();
            $table->unsignedBigInteger('budget_id')->nullable();
            $table->unsignedBigInteger('budget_item_id')->nullable();
            $table->string('hscode', 255)->nullable();
            $table->unsignedBigInteger('item_id')->nullable();
            $table->string('description', 500)->nullable();
            $table->decimal('qty', 10, 2)->nullable();
            $table->string('unit', 100)->nullable();
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->decimal('total', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('proforma_items');
        Schema::dropIfExists('proforma_files');
        Schema::dropIfExists('proformas');
    }
};
