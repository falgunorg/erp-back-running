<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStoresTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        // Create stores table
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->unsignedBigInteger('booking_item_id')->nullable()->constrained('booking_items')->onDelete('set null');
            $table->unsignedBigInteger('booking_user_id')->nullable()->constrained('booking_users')->onDelete('set null');
            $table->unsignedBigInteger('supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->unsignedBigInteger('buyer_id')->nullable()->constrained('buyers')->onDelete('set null');
            $table->unsignedBigInteger('company_id')->nullable()->constrained('companies')->onDelete('set null');
            $table->string('store_number', 255)->nullable();
            $table->unsignedBigInteger('booking_id')->nullable()->constrained('bookings')->onDelete('set null');
            $table->unsignedBigInteger('budget_id')->nullable()->constrained('budgets')->onDelete('set null');
            $table->unsignedBigInteger('budget_item_id')->nullable()->constrained('budget_items')->onDelete('set null');
            $table->unsignedBigInteger('techpack_id')->nullable()->constrained('techpacks')->onDelete('set null');
            $table->string('challan_no', 255)->nullable();
            $table->string('gate_pass', 255)->nullable();
            $table->string('description', 500)->nullable();
            $table->string('remarks', 500)->nullable();
            $table->string('color', 255)->nullable();
            $table->string('size', 255)->nullable();
            $table->string('shade', 255)->nullable();
            $table->string('tex', 255)->nullable();
            $table->string('unit', 255)->nullable();
            $table->decimal('qty', 10, 2)->nullable();
            $table->string('photo', 255)->nullable();
            $table->timestamps(0);  // created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('stores');
    }
}
