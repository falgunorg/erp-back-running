<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTechnicalPackageFilesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('technical_package_files', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('technical_package_id');
            $table->enum('file_type', ['technical_package', 'spec_sheet', 'block_pattern', 'special_operation']);
            $table->string('filename');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('technical_package_files');
    }
}
