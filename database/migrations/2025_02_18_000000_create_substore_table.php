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
        // Create substore_access table
        Schema::create('substore_access', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('area', 1000)->nullable();
            $table->timestamps(0);
        });

        // Create sub_stores table
        Schema::create('sub_stores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('part_id')->nullable()->constrained('parts')->onDelete('set null');
            $table->unsignedBigInteger('company_id')->nullable()->constrained('companies')->onDelete('set null');
            $table->bigInteger('qty')->nullable();
            $table->timestamps(0);
        });

        // Create sub_store_issues table
        Schema::create('sub_store_issues', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('part_id')->nullable()->constrained('parts')->onDelete('set null');
            $table->unsignedBigInteger('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->unsignedBigInteger('substore_id')->nullable()->constrained('sub_stores')->onDelete('set null');
            $table->enum('issue_type', ['Self', 'Sister-Factory', 'Sub-Contract', 'Stock-Lot', 'Sample']);
            $table->date('issue_date')->nullable();
            $table->unsignedBigInteger('issue_to')->nullable()->constrained('users')->onDelete('set null');
            $table->string('line', 255)->nullable();
            $table->string('reference', 255)->nullable();
            $table->unsignedBigInteger('issuing_company')->nullable()->constrained('companies')->onDelete('set null');
            $table->string('challan_copy', 255)->nullable();
            $table->unsignedBigInteger('company_id')->nullable()->constrained('companies')->onDelete('set null');
            $table->string('remarks', 500)->nullable();
            $table->bigInteger('qty')->nullable();
            $table->unsignedBigInteger('request_id')->nullable()->constrained('requests')->onDelete('set null');
            $table->timestamps(0);
        });

        // Create sub_store_receives table
        Schema::create('sub_store_receives', function (Blueprint $table) {
            $table->id();
            $table->date('receive_date')->nullable();
            $table->unsignedBigInteger('requisition_id')->nullable()->constrained('requisitions')->onDelete('set null');
            $table->unsignedBigInteger('requisition_item_id')->nullable()->constrained('requisition_items')->onDelete('set null');
            $table->unsignedBigInteger('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->unsignedBigInteger('substore_id')->nullable()->constrained('sub_stores')->onDelete('set null');
            $table->unsignedBigInteger('company_id')->nullable()->constrained('companies')->onDelete('set null');
            $table->unsignedBigInteger('part_id')->nullable()->constrained('parts')->onDelete('set null');
            $table->bigInteger('qty')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->string('challan_no', 255)->nullable();
            $table->string('mrr_no', 255)->nullable();
            $table->string('gate_pass', 255)->nullable();
            $table->string('challan_copy', 400);
            $table->timestamps(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('sub_store_receives');
        Schema::dropIfExists('sub_store_issues');
        Schema::dropIfExists('sub_stores');
        Schema::dropIfExists('substore_access');
    }
};
