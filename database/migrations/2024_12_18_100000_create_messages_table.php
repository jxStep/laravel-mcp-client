<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(config('mcp.table_names.messages', 'messages'), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('chat_id')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->string('role');
            $table->uuid('uuid')->unique();
            $table->json('content');
            $table->json('metadata')->nullable();
            $table->boolean('error')->default(false);
            $table->boolean('processed')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('mcp.table_names.messages', 'messages'));
    }
};
