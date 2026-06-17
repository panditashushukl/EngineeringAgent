<?php

namespace App\Http\Controllers;

use App\Models\Integration;
use Illuminate\Http\Request;
use App\Jobs\SyncRepositoriesJob;
use Illuminate\Validation\Rule;
use App\Services\Integrations\OAuthProviderFactory;
use App\Services\Queue\QueueHelper;

class IntegrationController extends Controller
{
    public function index()
    {
        return Integration::latest()->paginate();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'provider' => ['required', 'in:github,gitlab,bitbucket'],
            'access_token' => ['required', 'string'],
            'refresh_token' => ['nullable', 'string'],
        ]);

        $integration = Integration::create([
            ...$validated,
            'user_id' => $request->user()->id,
        ]);

        return response()->json($integration, 201);
    }

    public function show(Integration $integration)
    {
        return $integration->loadCount('repositories');
    }

    public function update(Request $request, Integration $integration)
    {
        $validated = $request->validate([
            'provider' => ['sometimes', 'in:github,gitlab,bitbucket'],
            'access_token' => ['sometimes', 'string'],
            'refresh_token' => ['nullable', 'string'],
        ]);

        $integration->update($validated);

        return response()->json($integration);
    }

    public function connect(Request $request, string $provider)
    {
        $provider = $this->validatedProvider($provider);
        $oauth = OAuthProviderFactory::make($provider);
        $url = $oauth->getAuthorizationUrl();

        if ($request->expectsJson()) {
            return response()->json([
                'url' => $url
            ]);
        }

        return redirect()->away($url);
    }

    public function callback(Request $request, string $provider)
    {
        $provider = $this->validatedProvider($provider);

        if ($request->has('error')) {
            $message = $request->query('error_description')
                ?? $request->query('error')
                ?? 'OAuth authorization failed.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }

            if (!\Illuminate\Support\Facades\Auth::check()) {
                return redirect('/login')->withErrors(['email' => $message]);
            }

            return redirect(
                "/integrations?provider={$provider}&status=error"
            );
        }

        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $tokens = OAuthProviderFactory::make($provider)
            ->exchangeCode($validated['code']);

        if (! isset($tokens['access_token'])) {
            return response()->json([
                'message' => $tokens['error_description']
                    ?? $tokens['error']
                    ?? 'OAuth provider did not return an access token.',
            ], 422);
        }

        $user = $request->user();
        $wasGuest = ($user === null);

        if ($wasGuest) {
            $tempIntegration = new Integration([
                'provider' => $provider,
                'access_token' => $tokens['access_token'],
            ]);

            try {
                $externalUser = \App\Services\Integrations\ProviderFactory::make($tempIntegration)
                    ->getAuthenticatedUser();
            } catch (\Throwable $e) {
                return redirect('/login')->withErrors(['email' => 'Failed to retrieve profile from ' . ucfirst($provider) . '.']);
            }

            $email = $externalUser['email'] ?? null;

            if (!$email) {
                return redirect('/login')->withErrors(['email' => 'Could not retrieve verified email from ' . ucfirst($provider) . '.']);
            }

            $user = \App\Models\User::where('email', $email)->first();

            if (!$user) {
                $user = \App\Models\User::create([
                    'name' => $externalUser['name'] ?? $externalUser['login'] ?? $externalUser['username'] ?? 'User',
                    'email' => $email,
                    'password' => bcrypt(\Illuminate\Support\Str::random(16)),
                ]);
            }

            \Illuminate\Support\Facades\Auth::login($user);
            $request->session()->regenerate();
        }

        $integration = Integration::updateOrCreate(
            [
                'user_id' => $user->id,
                'provider' => $provider,
            ],
            [
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'] ?? null,
            ]
        );

        if (!app()->runningUnitTests()) {
            SyncRepositoriesJob::dispatch($integration);
            QueueHelper::runWorkerInBackground();
        }

        $payload = [
            'success' => true,
            'integration' => $integration,
        ];

        if ($request->expectsJson()) {
            return response()->json($payload);
        }

        if ($wasGuest) {
            return redirect('/');
        }

        return redirect(
            "/integrations?provider={$provider}&status=connected"
        );
    }

    public function connectGitlab()
    {
        return $this->connect(request(), 'gitlab');
    }

    public function gitlabCallback(Request $request)
    {
        return $this->callback($request, 'gitlab');
    }

    public function sync(
        Integration $integration
    ) {
        SyncRepositoriesJob::dispatch(
            $integration
        );

        QueueHelper::runWorkerInBackground();

        return response()->json([
            'success' => true,
            'message' => 'Repository sync started'
        ]);
    }

    public function destroy(
        Integration $integration
    ) {
        $integration->delete();

        return response()->json([
            'success' => true
        ]);
    }

    private function validatedProvider(string $provider): string
    {
        validator(
            ['provider' => $provider],
            ['provider' => ['required', Rule::in(['github', 'gitlab', 'bitbucket'])]]
        )->validate();

        return $provider;
    }
}
