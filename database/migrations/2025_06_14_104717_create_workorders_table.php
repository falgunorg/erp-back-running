<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkordersTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('workorders', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('wo_number');
            $table->integer('buyer_id');
            $table->integer('company_id');
            $table->string('season');
            $table->string('description');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('workorders');
    }
}
