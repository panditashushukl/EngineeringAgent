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
        Schema::create('deployments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('repository_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('developer_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('environment');

            $table->timestamp('deployed_at');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deployments');
    }
};
