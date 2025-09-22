<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePayrollsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id(); // Auto-increment primary key
            $table->string('full_name', 255)->nullable();
            $table->string('staff_id', 255)->nullable();
            $table->integer('company_id')->nullable();
            $table->integer('department_id')->nullable();
            $table->integer('designation_id')->nullable();
            $table->string('basic_salary', 255)->nullable();
            $table->string('house_rent', 255)->nullable();
            $table->string('medical_allowance', 255)->nullable();
            $table->string('transport_allowance', 255)->nullable();
            $table->string('food_allowance', 255)->nullable();
            $table->string('gross_salary', 255)->nullable();
            $table->timestamps(); // Adds created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('payrolls');
    }
}
