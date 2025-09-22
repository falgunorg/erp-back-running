<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateParcelsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('parcels', function (Blueprint $table) {
            $table->id(); // Auto-increment primary key
            $table->string('tracking_number', 255)->nullable();
            $table->string('photo', 255)->nullable();
            $table->string('item_type', 255)->nullable();
            $table->string('challan_no', 255)->nullable();
            $table->string('reference', 255)->nullable();
            $table->string('title', 255)->nullable();
            $table->string('description', 500)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('from_company')->nullable();
            $table->unsignedBigInteger('transit_by')->nullable();
            $table->unsignedBigInteger('received_by')->nullable();
            $table->dateTime('received_date')->nullable();
            $table->string('destination', 11)->nullable();
            $table->unsignedBigInteger('destination_person')->nullable();
            $table->string('qty', 255)->nullable();
            $table->string('buyer', 255)->nullable();
            $table->string('method', 255)->nullable();
            $table->enum('status', ['Issued', 'In Transit', 'Completed'])->default('Issued');
            $table->timestamps(); // Adds created_at and updated_at
            $table->softDeletes(); // Adds deleted_at for soft deletes
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('parcels');
    }
}
