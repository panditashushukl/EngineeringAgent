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
        Schema::create('developer_metrics', function (Blueprint $table) {
            $table->id();

            $table->foreignId('developer_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->date('period_start');

            $table->date('period_end');

            $table->integer('commits')->default(0);

            $table->integer('prs_created')->default(0);

            $table->integer('prs_merged')->default(0);

            $table->integer('reviews_done')->default(0);

            $table->integer('bugs_fixed')->default(0);

            $table->integer('deployments')->default(0);

            $table->decimal('task_completion_score', 5, 2);

            $table->decimal('code_quality_score', 5, 2);

            $table->decimal('review_score', 5, 2);

            $table->decimal('delivery_speed_score', 5, 2);

            $table->decimal('developer_score', 5, 2);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('developer_metrics');
    }
};
