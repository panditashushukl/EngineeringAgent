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
        Schema::create('bug_fixes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pull_request_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('developer_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('reason');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bug_fixes');
    }
};
