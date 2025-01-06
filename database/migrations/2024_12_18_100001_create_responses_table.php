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
        Schema::create(config('mcp.table_names.responses', 'responses'), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('message_id')->constrained(config('mcp.table_names.messages', 'messages'))
                ->onUpdate('cascade')->onDelete('cascade');
            $table->json('content');
            $table->string('anthropic_id');
            $table->string('model');
            $table->string('role')->default('assistant');
            $table->string('stop_reason')->nullable();
            $table->string('stop_sequence')->nullable();
            $table->string('type')->default('message');
            $table->integer('input_tokens')->default(0);
            $table->integer('output_tokens')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('mcp.table_names.responses', 'responses'));
    }
};