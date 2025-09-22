<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeavesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->enum('leave_type', ['Sick Leave', 'Casual Leave', 'Earn Leave', 'Maternity Leave']);
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('total_days');
            $table->enum('status', ['Pending', 'Recommended', 'Approved', 'Rejected'])->default('Pending');
            $table->text('reason')->nullable();
            $table->datetime('recommended_at')->nullable();
            $table->unsignedBigInteger('recommended_by')->nullable();
            $table->datetime('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->datetime('rejected_at')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->unsignedBigInteger('company')->nullable();
            $table->unsignedBigInteger('department')->nullable();
            $table->string('designation', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('leaves');
    }
}
