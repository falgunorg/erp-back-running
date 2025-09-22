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
        // Create departments table
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->timestamps();
        });

        // Create designations table
        Schema::create('designations', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->timestamps();
        });

        DB::table('departments')->insert([
            ["title" => "Accounts & Finance"],
            ["title" => "Audit"],
            ["title" => "Commercial"],
            ["title" => "HR"],
            ["title" => "Administration"],
            ["title" => "Management"],
            ["title" => "IT"],
            ["title" => "Marketing"],
            ["title" => "Production"],
            ["title" => "Electric"],
            ["title" => "Merchandising"],
            ["title" => "Sample"],
            ["title" => "Development"],
            ["title" => "Finishing"],
            ["title" => "Store"],
            ["title" => "Cutting"],
            ["title" => "Sewing"],
            ["title" => "Embroidery"],
            ["title" => "Planing"],
            ["title" => "Maintenance"],
            ["title" => "Purchase"],
            ["title" => "Washing"],
        ]);

        DB::table('designations')->insert([
            ["title" => "Manager"],
            ["title" => "Assistant Manager"],
            ["title" => "Programmer"],
            ["title" => "Graphics Designer"],
            ["title" => "Designer"],
            ["title" => "Assistant Accountant"],
            ["title" => "Asst. Merchandiser"],
            ["title" => "Merchandiser"],
            ["title" => "Store Executive"],
            ["title" => "Sr. Merchandiser"],
            ["title" => "Managing Director"],
            ["title" => "Store Assistant"],
            ["title" => "Jr Operator"],
            ["title" => "Quality Controller"],
            ["title" => "Finishing QAD"],
            ["title" => "Cutting Incharge"],
            ["title" => "Sewing Incharge"],
            ["title" => "Finishing Incharge"],
            ["title" => "Embroidery Incharge"],
            ["title" => "Sample Incharge"],
            ["title" => "Asst. General Manager"],
            ["title" => "Sr. Executive"],
            ["title" => "Deputy General Manager"],
            ["title" => "General Manager"],
            ["title" => "Jr. Executive"],
            ["title" => "Factory Incharge"],
            ["title" => "Receptionist"],
            ["title" => "Executive"],
            ["title" => "Store Keeper"],
            ["title" => "Officer"],
            ["title" => "SubStore Assistant"],
            ["title" => "Executive Director"],
            ["title" => "Director"],
            ["title" => "Washing Incharge"]
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('designations');
        Schema::dropIfExists('departments');
    }
};
