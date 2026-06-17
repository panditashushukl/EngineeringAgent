<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'developer_insights',
            function (Blueprint $table) {

                $table->id();

                $table->foreignId(
                    'developer_id'
                )->constrained()
                 ->cascadeOnDelete();

                $table->longText(
                    'summary'
                )->nullable();

                $table->json(
                    'strengths'
                )->nullable();

                $table->json(
                    'weaknesses'
                )->nullable();

                $table->json(
                    'risks'
                )->nullable();

                $table->json(
                    'recommendations'
                )->nullable();

                $table->longText(
                    'raw_response'
                )->nullable();

                $table->timestamps();
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'developer_insights'
        );
    }
};