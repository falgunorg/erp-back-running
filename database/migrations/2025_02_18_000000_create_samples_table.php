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
        // Create sample_balance table
        Schema::create('sample_balance', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sample_store_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('qty', 10, 2)->nullable();
            $table->timestamps(0);  // created_at and updated_at
            $table->softDeletes();  // deleted_at for soft deletes
        });

        // Create sample_store table
        Schema::create('sample_store', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('store_number', 20)->nullable();
            $table->string('title', 255)->nullable();
            $table->unsignedBigInteger('item_type')->nullable()->constrained()->onDelete('set null');
            $table->string('code', 255)->nullable();
            $table->unsignedBigInteger('buyer_id')->nullable()->constrained()->onDelete('set null');
            $table->unsignedBigInteger('techpack_id')->nullable()->constrained()->onDelete('set null');
            $table->string('color', 255)->nullable();
            $table->string('unit', 255)->nullable();
            $table->string('size', 255)->default('0');
            $table->unsignedBigInteger('reference')->nullable()->constrained()->onDelete('set null');
            $table->string('description', 1000)->nullable();
            $table->string('photo', 255)->nullable();
            $table->timestamps(0);  // created_at and updated_at
        });

        // Create sample_store_activities table
        Schema::create('sample_store_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->unsignedBigInteger('sample_store_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('type', ['Add', 'Issue'])->nullable();
            $table->decimal('qty', 10, 2)->nullable();
            $table->unsignedBigInteger('sor_id')->nullable()->constrained()->onDelete('set null');
            $table->string('reference', 500)->nullable();
            $table->string('remarks', 500)->nullable();
            $table->timestamps(0);  // created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('sample_store_activities');
        Schema::dropIfExists('sample_store');
        Schema::dropIfExists('sample_balance');
    }
};
