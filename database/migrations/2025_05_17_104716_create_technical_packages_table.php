<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTechnicalPackagesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('technical_packages', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('po_id')->nullable();
            $table->integer('wo_id')->nullable();
            $table->integer('user_id')->nullable();
            $table->date('received_date')->nullable();
            $table->string('techpack_number')->nullable();
            $table->integer('buyer_id')->nullable();
            $table->string('buyer_style_name')->nullable();
            $table->string('brand')->nullable();
            $table->string('item_name')->nullable();
            $table->string('season')->nullable();
            $table->string('item_type')->nullable();
            $table->string('department')->nullable();
            $table->string('description')->nullable();
            $table->integer('company_id')->nullable();
            $table->string('wash_details')->nullable();
            $table->string('special_operation')->nullable();
             $table->string('front_photo')->nullable();
            $table->string('back_photo')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('technical_packages');
    }
}
