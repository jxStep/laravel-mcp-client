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
        Schema::create(config('mcp.table_names.runners', 'runners'), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('response_id')->constrained(config('mcp.table_names.responses', 'responses'))
                ->onUpdate('cascade')->onDelete('cascade');
            $table->uuid('uuid')->unique();
            $table->string('mcp_server');
            $table->string('tool_name');
            $table->string('tool_use_id');
            $table->json('arguments');
            $table->json('result')->nullable();
            $table->text('error')->nullable();
            $table->string('status');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('mcp.table_names.runners', 'runners'));
    }
};