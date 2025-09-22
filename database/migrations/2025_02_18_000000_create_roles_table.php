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
        // Create roles table
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->string('level', 200)->nullable();
            $table->timestamps(0);  // created_at and updated_at
            $table->softDeletes();  // deleted_at for soft deletes
        });

        // Create role_options table
        Schema::create('role_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id')->constrained('roles')->onDelete('cascade');
            $table->string('option_name', 200);
            $table->boolean('view_download')->default(0);
            $table->boolean('add_edit')->default(0);
            $table->boolean('delete_void')->default(0);
            $table->boolean('approved_reject')->default(0);
            $table->timestamps(0);  // created_at and updated_at
            $table->softDeletes();  // deleted_at for soft deletes
        });

        DB::table('roles')->insert([
            'title' => 'Admin',
            'level' => 'Admin',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('role_options');
        Schema::dropIfExists('roles');
    }
};
