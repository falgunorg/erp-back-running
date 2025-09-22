<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMailConfigsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('mail_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('host', 255)->nullable();
            $table->string('port', 20)->nullable();
            $table->string('protocol', 30)->nullable();
            $table->string('encryption', 30)->nullable();
            $table->enum('validate_chert', ['true', 'false'])->default('true');
            $table->string('username', 255)->nullable();
            $table->string('password', 255)->nullable();
            $table->string('smtp_host', 255)->nullable();
            $table->string('smtp_port', 20)->nullable();
            $table->string('smtp_protocol', 255)->nullable();
            $table->string('smtp_username', 255)->nullable();
            $table->string('smtp_password', 255)->nullable();
            $table->string('smtp_encryption', 11)->nullable();
            $table->string('user_name', 200)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('mail_configs');
    }
}
