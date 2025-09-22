<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePoItemsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('po_items', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('po_id')->nullable();
            $table->string('color')->nullable();
            $table->string('size')->nullable();
            $table->string('inseam')->nullable();
            $table->integer('qty')->nullable();
            $table->decimal('fob', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('po_items');
    }
}
