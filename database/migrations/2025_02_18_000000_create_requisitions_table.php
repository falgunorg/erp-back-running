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
        // Create requisitions table
        Schema::create('requisitions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('requisition_number', 11)->nullable();
            $table->unsignedBigInteger('company_id')->nullable()->constrained()->onDelete('set null');
            $table->unsignedBigInteger('department')->nullable()->constrained()->onDelete('set null');
            $table->unsignedBigInteger('recommended_user')->nullable()->constrained()->onDelete('set null');
            $table->string('label')->nullable();
            $table->unsignedBigInteger('billing_company_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('total', 10, 2)->nullable();
            $table->unsignedBigInteger('placed_by')->default(0)->constrained('users');
            $table->string('placed_at')->nullable();
            $table->unsignedBigInteger('recommended_by')->default(0)->constrained('users');
            $table->string('valuated_at')->nullable();
            $table->unsignedBigInteger('valuated_by')->default(0)->constrained('users');
            $table->string('recommended_at')->nullable();
            $table->unsignedBigInteger('checked_by')->default(0)->constrained('users');
            $table->string('checked_at')->nullable();
            $table->unsignedBigInteger('rejected_by')->default(0)->constrained('users');
            $table->string('rejected_at')->nullable();
            $table->unsignedBigInteger('approved_by')->default(0)->constrained('users');
            $table->string('approved_at')->nullable();
            $table->unsignedBigInteger('finalized_by')->default(0)->constrained('users');
            $table->string('finalized_at')->nullable();
            $table->timestamps();
            $table->enum('status', ['Pending', 'Placed', 'Recommended', 'Checked', 'Approved', 'Finalized', 'Rejected', 'Valuated'])->default('Pending');
        });

        // Create requisition_items table
        Schema::create('requisition_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('requisition_id')->nullable()->constrained('requisitions')->onDelete('cascade');
            $table->unsignedBigInteger('part_id')->nullable()->constrained()->onDelete('set null');
            $table->string('stock_in_hand')->nullable();
            $table->string('unit')->nullable();
            $table->bigInteger('qty')->default(0);
            $table->bigInteger('recommand_qty')->default(0);
            $table->bigInteger('audit_qty')->default(0);
            $table->bigInteger('final_qty')->default(0);
            $table->bigInteger('purchase_qty')->default(0);
            $table->unsignedBigInteger('finalized_by')->nullable()->constrained('users');
            $table->decimal('rate', 10, 2)->default(0.00);
            $table->decimal('final_rate', 10, 2)->default(0.00);
            $table->decimal('total', 10, 2)->default(0.00);
            $table->string('remarks')->nullable();
            $table->enum('status', ['Listed', 'Pending', 'Purchased', 'Inhoused'])->default('Listed');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('requisition_items');
        Schema::dropIfExists('requisitions');
    }
};
