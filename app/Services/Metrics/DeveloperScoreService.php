<?php

namespace App\Services\Metrics;

class DeveloperScoreService
{
    public function calculate(
        array $metrics
    ): float {

        $taskScore =
            $metrics['task_completion_score'];

        $reviewScore =
            $metrics['review_score'];

        $deliveryScore =
            $metrics['delivery_speed_score'];

        $codeQualityScore =
            $metrics['code_quality_score'];

        $wTask = (float) \App\Models\Setting::get('weight_task_completion', 0.40);
        $wReview = (float) \App\Models\Setting::get('weight_reviews', 0.20);
        $wDelivery = (float) \App\Models\Setting::get('weight_delivery', 0.20);
        $wQuality = (float) \App\Models\Setting::get('weight_code_quality', 0.20);

        return round(

            ($taskScore * $wTask)

            +

            ($reviewScore * $wReview)

            +

            ($deliveryScore * $wDelivery)

            +

            ($codeQualityScore * $wQuality),

            2
        );
    }
}