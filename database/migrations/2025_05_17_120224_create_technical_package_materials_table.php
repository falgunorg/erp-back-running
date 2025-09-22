<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTechnicalPackageMaterialsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('technical_package_materials', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('technical_package_id')->nullable();
            $table->integer('item_type_id')->nullable();
            $table->integer('item_id')->nullable();
            $table->string('item_name')->nullable();
            $table->string('item_details')->nullable();
            $table->string('color')->nullable();
            $table->string('size')->nullable();
            $table->string('position')->nullable();
            $table->string('unit')->nullable();
            $table->decimal('consumption', 10, 2)->default(0);
            $table->decimal('wastage', 10, 2)->default(0);
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
        Schema::dropIfExists('technical_package_materials');
    }
}
