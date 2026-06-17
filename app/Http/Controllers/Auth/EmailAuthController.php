<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\EmailLoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class EmailAuthController extends Controller
{

    public function login(EmailLoginRequest $request)
    {
        $data = $request->validated();

        $credentials = [
            'password' => $data['password'],
        ];

        if (isset($data['email'])) {
            $credentials['email'] = $data['email'];
        } elseif (isset($data['phone'])) {
            $credentials['phone'] = $data['phone'];
        } elseif (isset($data['identifier'])) {
            $field = filter_var($data['identifier'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
            $credentials[$field] = $data['identifier'];
        }

        if (!Auth::attempt($credentials)) {
            return $this->unauthorized('Invalid credentials');
        }

        $request->session()->regenerate();

        $user = Auth::user();
        
        $response = [
            'user' => $user,
        ];

        return $this->success('Login successful', $response);
    }

    public function logout(Request $request)
    {
        if ($request->user()?->currentAccessToken() instanceof PersonalAccessToken) {
            $request->user()->currentAccessToken()->delete();
        }

        if ($request->hasSession()) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        // Check if this is a web request (expects HTML response)
        if ($request->expectsJson()) {
            return $this->success('Logged out successfully', null);
        }

        // For web requests, redirect to login page
        return redirect()->route('login');
    }

    public function logoutAllDevices(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            if ($request->expectsJson()) {
                return $this->unauthorized('Unauthenticated');
            }
            return redirect()->route('login');
        }

        // 1. Revoke all Sanctum tokens
        $user->tokens()->delete();

        // 2. Revoke other sessions (database and file drivers)
        $userId = $user->id;

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('sessions')) {
                \Illuminate\Support\Facades\DB::table('sessions')
                    ->where('user_id', $userId)
                    ->where('id', '!=', $request->session()->getId())
                    ->delete();
            }
        } catch (\Throwable $e) {
            // Ignore if sessions table is not queryable
        }

        if ($request->hasSession()) {
            $currentSessionId = $request->session()->getId();
            $sessionDir = config('session.files', storage_path('framework/sessions'));
            if (is_dir($sessionDir)) {
                $files = glob($sessionDir . '/*');
                foreach ($files as $file) {
                    if (basename($file) === '.gitignore' || basename($file) === $currentSessionId) {
                        continue;
                    }
                    if (is_file($file)) {
                        $content = @file_get_contents($file);
                        if ($content !== false) {
                            $data = json_decode($content, true);
                            if (is_array($data)) {
                                foreach ($data as $key => $val) {
                                    if (str_starts_with($key, 'login_') && $val == $userId) {
                                        @unlink($file);
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Finally, logout the current session
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($request->expectsJson()) {
            return $this->success('Logged out from all devices successfully', null);
        }

        return redirect()->route('login');
    }

    protected function success(string $message, $data = null)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ]);
    }

    protected function unauthorized(string $message)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 401);
    }
}
