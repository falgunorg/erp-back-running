<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTeamsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('teams', function (Blueprint $table) {
            $table->id(); // Auto incrementing primary key
            $table->string('team_number', 30)->nullable();
            $table->string('title', 255)->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->constrained('users')->onDelete('set null'); // Assuming `users` table exists for the foreign key reference
            $table->unsignedBigInteger('team_lead')->nullable()->constrained('users')->onDelete('set null'); // Assuming `users` table exists for the foreign key reference
            $table->text('employees')->nullable(); // Could store employee IDs or a list as JSON
            $table->unsignedBigInteger('department')->nullable()->constrained('departments')->onDelete('set null'); // Assuming `departments` table exists
            $table->unsignedBigInteger('company_id')->nullable()->constrained('companies')->onDelete('set null'); // Assuming `companies` table exists
            $table->string('description', 1000)->nullable();
            $table->timestamps(0); // created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('teams');
    }
}
