<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIssuesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('store_id')->nullable();
            $table->enum('issue_type', ['Self', 'Sister-Factory', 'Sub-Contract', 'Stock-Lot', 'Sample']);
            $table->unsignedBigInteger('issue_to')->nullable();
            $table->string('line')->nullable();
            $table->string('reference')->nullable();
            $table->unsignedBigInteger('issuing_company')->nullable();
            $table->string('delivery_challan')->nullable();
            $table->unsignedBigInteger('booking_item_id')->nullable();
            $table->unsignedBigInteger('booking_user_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('buyer_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('store_number')->nullable();
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
            $table->enum('status', ['Issued', 'Received'])->default('Issued');
            $table->timestamps(); // Automatically adds created_at and updated_at columns
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('issues');
    }
}
