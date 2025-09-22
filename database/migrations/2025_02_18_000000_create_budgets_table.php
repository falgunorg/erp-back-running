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
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->string('budget_number', 255)->nullable();
            $table->unsignedInteger('purchase_id')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->string('brand', 255)->nullable();
            $table->string('qty', 255)->nullable();
            $table->decimal('total_order_value', 10, 2)->nullable();
            $table->string('sizes', 255)->nullable();
            $table->string('ratio', 255)->nullable();
            $table->string('colors', 255)->nullable();
            $table->string('issued_date', 255)->nullable();
            $table->string('product_description', 500)->nullable();
            $table->string('note', 500)->nullable();
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->decimal('total_cost', 10, 2)->nullable();
            $table->enum('status', ['Pending', 'Placed', 'Confirmed', 'Submitted', 'Checked', 'Cost-Approved', 'Finalized', 'Approved', 'Rejected'])->default('Pending');
            $table->unsignedInteger('placed_by')->default(0);
            $table->string('placed_at', 100)->nullable();
            $table->unsignedInteger('confirmed_by')->default(0);
            $table->string('confirmed_at', 100)->nullable();
            $table->unsignedInteger('submitted_by')->default(0);
            $table->string('submitted_at', 100)->nullable();
            $table->unsignedInteger('checked_by')->default(0);
            $table->string('checked_at', 100)->nullable();
            $table->unsignedInteger('cost_approved_by')->default(0);
            $table->string('cost_approved_at', 100)->nullable();
            $table->unsignedInteger('finalized_by')->default(0);
            $table->string('finalized_at', 100)->nullable();
            $table->unsignedInteger('approved_by')->default(0);
            $table->string('approved_at', 100)->nullable();
            $table->unsignedInteger('rejected_by')->default(0);
            $table->string('rejected_at', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('budget_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('budget_id')->nullable();
            $table->string('filename', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('budget_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('item_id')->nullable();
            $table->unsignedInteger('budget_id')->nullable();
            $table->string('title', 255)->nullable();
            $table->string('description', 500)->nullable();
            $table->unsignedInteger('supplier_id')->nullable();
            $table->string('cuttable_width', 255)->nullable();
            $table->string('actual', 255)->nullable();
            $table->string('wastage_parcentage', 255)->nullable();
            $table->decimal('cons_total', 10, 2)->nullable();
            $table->string('unit', 255)->nullable();
            $table->string('size', 255)->nullable();
            $table->string('color', 255)->nullable();
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->decimal('unit_total_cost', 10, 2)->nullable();
            $table->string('total_req_qty', 255)->nullable();
            $table->decimal('order_total_cost', 10, 2)->default(0.00);
            $table->decimal('used', 10, 2)->default(0.00);
            $table->decimal('used_budget', 10, 2)->nullable();
            $table->decimal('balance', 10, 2)->default(0.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('budget_items');
        Schema::dropIfExists('budget_files');
        Schema::dropIfExists('budgets');
    }
};
