<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConversationsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key (default 'id' field)
            $table->string('title')->nullable(); // Title as varchar, nullable
            $table->unsignedBigInteger('user1_id')->nullable()->constrained('users')->onDelete('set null'); // Foreign key for user1, nullable
            $table->unsignedBigInteger('user2_id')->nullable()->constrained('users')->onDelete('set null'); // Foreign key for user2, nullable
            $table->timestamps(); // Created_at and updated_at timestamps
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('conversations');
    }
}
