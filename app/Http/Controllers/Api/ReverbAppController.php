<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\RestartReverbServer;
use App\Models\ReverbApp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReverbAppController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ReverbApp::query();

        if ($request->has('active')) {
            $query->where('is_active', (bool) $request->input('active'));
        }

        return response()->json(['data' => $query->get()->makeHidden('app_secret')]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'app_id' => ['sometimes', 'string', 'min:8', 'max:255', 'regex:/^[A-Za-z0-9_\-]+$/', 'unique:reverb_apps,app_id'],
            'app_key' => ['sometimes', 'string', 'min:8', 'max:255', 'regex:/^[A-Za-z0-9_\-]+$/', 'unique:reverb_apps,app_key'],
            'app_secret' => ['sometimes', 'string', 'min:16', 'max:255', 'regex:/^[A-Za-z0-9_\-]+$/'],
            'description' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'max_connections' => ['sometimes', 'integer', 'min:1'],
            'allowed_origins' => ['sometimes', 'array'],
            'allowed_origins.*' => ['string', function (string $attribute, mixed $value, \Closure $fail): void {
                if ($value === '*') {
                    return;
                }

                $isValidDomain = is_string($value)
                    && $value === trim($value)
                    && filter_var($value, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);

                if (! $isValidDomain) {
                    $fail("The {$attribute} field must be a domain (e.g. example.com) or '*'.");
                }
            }],
        ]);

        $app = ReverbApp::create($validated);

        return response()->json(['data' => $app->fresh()], 201);
    }

    public function show(ReverbApp $reverbApp): JsonResponse
    {
        return response()->json(['data' => $reverbApp->makeHidden('app_secret')]);
    }

    public function restart(): JsonResponse
    {
        RestartReverbServer::dispatch('API request');

        return response()->json(['message' => 'Reverb server restart has been queued.']);
    }
}
