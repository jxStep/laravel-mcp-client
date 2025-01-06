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
        Schema::create(config('mcp.table_names.logs', 'logs'), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('runner_id')
                ->constrained(config('mcp.table_names.runners', 'runners'))
                ->onUpdate('cascade')->onDelete('cascade');
            $table->string('level');
            $table->text('message');
            $table->json('context')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('mcp.table_names.logs', 'logs'));
    }
};
