<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Libraries\Tokenizer;

class CreateUsersTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        // Creating the users table
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('full_name', 255);
            $table->string('email', 255);
            $table->string('password', 255);
            $table->string('staff_id', 255)->nullable();
            $table->integer('role_permission')->nullable();
            $table->integer('department')->nullable();
            $table->integer('designation')->nullable();
            $table->integer('company')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'Suspended'])->default('Active');
            $table->string('photo', 255)->nullable();
            $table->string('sign', 255)->nullable();
            $table->dateTime('last_login_at')->nullable();
            $table->timestamps(0);
        });

        DB::table('users')->insert([
            'full_name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Tokenizer::password('Admin123'), // Ensure you hash the password
            'role_permission' => 1,
            'department' => 7,
            'designation' => 3,
            'company' => 1,
            'status' => 'Active', // Status 'Active' by default
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        // Dropping the users table
        Schema::dropIfExists('users');
    }
}
