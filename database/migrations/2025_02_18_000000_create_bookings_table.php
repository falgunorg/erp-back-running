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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_number', 255)->nullable();
            $table->string('pi_number', 255)->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->unsignedInteger('supplier_id')->nullable();
            $table->unsignedInteger('company_id')->nullable();
            $table->string('booking_date', 255)->nullable();
            $table->string('delivery_date', 255)->nullable();
            $table->string('billing_address', 500)->nullable();
            $table->string('delivery_address', 500)->nullable();
            $table->string('booking_from', 255)->nullable();
            $table->string('booking_to', 255)->nullable();
            $table->string('currency', 255)->nullable();
            $table->string('remark', 1000)->nullable();
            $table->unsignedInteger('terms')->nullable();
            $table->enum('status', ['Pending', 'Placed', 'Confirmed', 'Rejected'])->default('Pending');
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->unsignedInteger('placed_by')->default(0);
            $table->string('placed_at', 100)->nullable();
            $table->unsignedInteger('confirmed_by')->default(0);
            $table->string('confirmed_at', 100)->nullable();
            $table->unsignedInteger('rejected_by')->default(0);
            $table->string('rejected_at', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('booking_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('booking_id')->nullable();
            $table->unsignedInteger('budget_id')->nullable();
            $table->unsignedInteger('budget_item_id')->nullable();
            $table->string('description', 2000)->nullable();
            $table->string('remarks', 255)->nullable();
            $table->string('color', 255)->nullable();
            $table->string('size', 255)->nullable();
            $table->string('shade', 255)->nullable();
            $table->string('tex', 255)->nullable();
            $table->string('unit', 255)->nullable();
            $table->string('photo', 500)->nullable();
            $table->string('qty', 255)->nullable();
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->decimal('total', 10, 2)->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('bookings_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('booking_id')->nullable();
            $table->string('filename', 300)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('bookings_files');
        Schema::dropIfExists('booking_items');
        Schema::dropIfExists('bookings');
    }
};
