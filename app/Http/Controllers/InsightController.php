<?php

namespace App\Http\Controllers;

use App\Models\Developer;
use App\Jobs\GenerateDeveloperInsightsJob;

class InsightController extends Controller
{
    public function generate(
        Developer $developer
    ) {

        GenerateDeveloperInsightsJob::dispatch(
            $developer
        );

        return response()->json([
            'success' => true,
            'message' => 'Insight generation queued'
        ]);
    }

    public function show(
        Developer $developer
    ) {

        return $developer
            ->insights()
            ->latest()
            ->first();
    }

    public function regenerate(
        Developer $developer
    ) {
        return $this->generate(
            $developer
        );
    }

    public function generateAll()
    {
        Developer::query()->each(function (Developer $developer) {
            GenerateDeveloperInsightsJob::dispatch($developer);
        });

        return response()->json([
            'success' => true,
            'message' => 'Insight generation queued for all developers'
        ]);
    }
}

