<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReturnsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->constrained()->onDelete('set null');
            $table->unsignedBigInteger('return_to')->nullable()->constrained()->onDelete('set null');
            $table->unsignedBigInteger('store_id')->nullable()->constrained()->onDelete('set null');
            $table->unsignedBigInteger('issue_id')->nullable()->constrained()->onDelete('set null');
            $table->unsignedBigInteger('company_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('qty', 10, 2)->nullable();
            $table->enum('status', ['Pending', 'Received'])->default('Pending');
            $table->unsignedBigInteger('received_by')->nullable()->constrained('users');
            $table->string('remarks', 500)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('returns');
    }
}
