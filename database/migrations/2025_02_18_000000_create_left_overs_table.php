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
        // Creating the `left_overs` table
        Schema::create('left_overs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('left_over_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('buyer_id')->nullable();
            $table->unsignedBigInteger('style_id')->nullable();
            $table->string('season', 255)->nullable();
            $table->string('title', 300)->nullable();
            $table->string('carton', 255)->nullable();
            $table->string('qty', 255)->nullable();
            $table->enum('item_type', ['Best', 'Good'])->nullable();
            $table->string('reference', 255)->nullable();
            $table->string('remarks', 500)->nullable();
            $table->string('photo', 300)->nullable();
            $table->enum('status', ['Pending', 'Checked', 'Received'])->default('Pending');
            $table->unsignedBigInteger('received_by')->nullable();
            $table->timestamps();
        });

        // Creating the `left_over_balance` table
        Schema::create('left_over_balance', function (Blueprint $table) {
            $table->id();
            $table->string('lo_number', 255)->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('buyer_id')->nullable();
            $table->unsignedBigInteger('style_id')->nullable();
            $table->string('season', 255)->nullable();
            $table->string('title', 300)->nullable();
            $table->string('item_type', 255)->nullable();
            $table->decimal('carton', 10, 2)->nullable();
            $table->decimal('qty', 10, 2)->nullable();
            $table->string('photo', 500)->nullable();
            $table->timestamps();
        });

        // Creating the `left_over_issues` table
        Schema::create('left_over_issues', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('left_over_id')->nullable();
            $table->enum('issue_type', ['Sister-Factory', 'Stock-Lot'])->nullable();
            $table->string('title', 255)->nullable();
            $table->string('reference', 255)->nullable();
            $table->unsignedBigInteger('issue_to_company_id')->nullable();
            $table->string('delivery_challan', 255)->nullable();
            $table->unsignedBigInteger('buyer_id')->nullable();
            $table->unsignedBigInteger('style_id')->nullable();
            $table->string('remarks', 500)->nullable();
            $table->decimal('carton', 10, 2)->nullable();
            $table->decimal('qty', 10, 2)->nullable();
            $table->string('photo', 255)->nullable();
            $table->string('challan_copy', 500)->nullable();
            $table->string('item_type', 255)->nullable();
            $table->string('season', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('left_over_issues');
        Schema::dropIfExists('left_over_balance');
        Schema::dropIfExists('left_overs');
    }
};
