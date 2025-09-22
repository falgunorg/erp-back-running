<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSuppliersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id()->unsigned(); // BigInt UNSIGNED ID
            $table->string('company_name', 256)->nullable();
            $table->string('email', 1000)->nullable();
            $table->string('attention_person', 255)->nullable();
            $table->string('office_number', 1000)->nullable();
            $table->string('mobile_number', 1000)->nullable();
            $table->string('address', 700)->nullable();
            $table->string('state', 1000)->nullable();
            $table->string('postal_code', 200)->nullable();
            $table->string('country', 255)->nullable();
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->string('product_supply', 500)->nullable();
            $table->string('vat_reg_number', 255)->nullable();
            $table->unsignedBigInteger('added_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('type', 255)->default('store');
            $table->timestamps(0); // created_at and updated_at
            $table->softDeletes(); // deleted_at for soft deletes
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('suppliers');
    }
}
