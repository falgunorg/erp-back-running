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
        Schema::create('parts', function (Blueprint $table) {
            $table->id(); // Auto-increment primary key
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('title', 255)->nullable();
            $table->enum('type', [
                'Stationery', 'Spare Parts', 'Electrical', 'Needle', 'Medical', 'Fire',
                'Compressor & boiler', 'Chemical', 'Printing', 'It', 'WTP', 'Vehicle',
                'Compliance', 'Mechanical'
            ])->default('Stationery');
            $table->string('unit', 255)->nullable();
            $table->string('min_balance', 255)->default('5');
            $table->string('brand', 255)->nullable();
            $table->string('model', 255)->nullable();
            $table->string('photo', 500)->nullable();
            $table->timestamps(); // Adds created_at and updated_at
        });

        Schema::create('parts_request', function (Blueprint $table) {
            $table->id(); // Auto-increment primary key
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('department')->nullable();
            $table->string('request_number', 255)->nullable();
            $table->unsignedBigInteger('substore_id')->nullable();
            $table->unsignedBigInteger('part_id')->nullable();
            $table->bigInteger('qty')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('remarks', 255)->nullable();
            $table->string('line', 255)->nullable();
            $table->string('photo', 400)->nullable();
            $table->enum('status', [
                'Pending', 'Approved', 'Delivered', 'Revised', 'Rejected', 'Cancelled'
            ])->default('Pending');
            $table->string('approved_at', 255)->nullable();
            $table->unsignedBigInteger('approved_by')->default(0);
            $table->string('cancelled_at', 255)->nullable();
            $table->unsignedBigInteger('cancelled_by')->default(0);
            $table->string('rejected_at', 255)->nullable();
            $table->unsignedBigInteger('rejected_by')->default(0);
            $table->string('delivered_at', 255)->nullable();
            $table->unsignedBigInteger('delivered_by')->default(0);
            $table->timestamps(); // Adds created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('parts_request');
        Schema::dropIfExists('parts');
    }
};
