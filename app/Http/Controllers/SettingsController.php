<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\Metrics\DeveloperMetricService;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function getSettings()
    {
        return response()->json([
            'success' => true,
            'settings' => [
                'ai_provider' => Setting::get('ai_provider', config('services.ai_provider', 'gemini')),
                'gemini_model' => Setting::get('gemini_model', config('services.gemini.model', 'gemini-2.5-pro')),
                'ollama_base_url' => Setting::get('ollama_base_url', config('services.ollama.base_url', 'http://localhost:11434')),
                'ollama_model' => Setting::get('ollama_model', config('services.ollama.model', 'llama3.1:8b')),
                'weight_task_completion' => (float) Setting::get('weight_task_completion', 0.40),
                'weight_reviews' => (float) Setting::get('weight_reviews', 0.20),
                'weight_delivery' => (float) Setting::get('weight_delivery', 0.20),
                'weight_code_quality' => (float) Setting::get('weight_code_quality', 0.20),
            ]
        ]);
    }

    public function saveSettings(Request $request)
    {
        $validated = $request->validate([
            'ai_provider' => 'required|string|in:ollama,gemini',
            'gemini_model' => 'nullable|string',
            'ollama_base_url' => 'required|string',
            'ollama_model' => 'required|string',
            'weight_task_completion' => 'required|numeric|min:0|max:1',
            'weight_reviews' => 'required|numeric|min:0|max:1',
            'weight_delivery' => 'required|numeric|min:0|max:1',
            'weight_code_quality' => 'required|numeric|min:0|max:1',
        ]);

        $sum = $validated['weight_task_completion'] + 
               $validated['weight_reviews'] + 
               $validated['weight_delivery'] + 
               $validated['weight_code_quality'];

        if (abs($sum - 1.0) > 0.001) {
            return response()->json([
                'success' => false,
                'message' => 'The sum of all scoring weights must equal exactly 1.00 (100%). Currently it is ' . round($sum * 100, 1) . '%.'
            ], 422);
        }

        Setting::set('ai_provider', $validated['ai_provider']);
        Setting::set('gemini_model', $validated['gemini_model'] ?? 'gemini-2.5-pro');
        Setting::set('ollama_base_url', $validated['ollama_base_url']);
        Setting::set('ollama_model', $validated['ollama_model']);
        Setting::set('weight_task_completion', $validated['weight_task_completion']);
        Setting::set('weight_reviews', $validated['weight_reviews']);
        Setting::set('weight_delivery', $validated['weight_delivery']);
        Setting::set('weight_code_quality', $validated['weight_code_quality']);

        return response()->json([
            'success' => true,
            'message' => 'Settings saved successfully.'
        ]);
    }

    public function recalculateMetrics(DeveloperMetricService $metricService)
    {
        $metrics = \App\Models\DeveloperMetric::all();
        
        foreach ($metrics as $metric) {
            $developer = $metric->developer;
            if (!$developer) {
                continue;
            }
            $start = \Carbon\Carbon::parse($metric->period_start);
            $end = \Carbon\Carbon::parse($metric->period_end);
            
            $results = $metricService->calculate($developer, $start, $end);
            $metric->update($results);
        }

        return response()->json([
            'success' => true,
            'message' => 'All developer metrics and scores have been recalculated successfully based on the new weights.'
        ]);
    }
}
