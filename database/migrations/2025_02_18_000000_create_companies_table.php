<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompaniesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('companies', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key (default 'id' field)
            $table->string('title')->nullable(); // Title as varchar, nullable
            $table->enum('type', ['Own', 'Other'])->nullable(); // Enum field for company type
            $table->string('address', 500)->nullable(); // Address as varchar, nullable with max length of 500
            $table->timestamps(); // Created_at and updated_at timestamps
        });

        DB::table('companies')->insert([
            'title' => 'Head Office',
            'type' => 'Own',
            'address' => 'New Address',
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
        Schema::dropIfExists('companies');
    }
}
