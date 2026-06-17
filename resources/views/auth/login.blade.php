@extends('layouts.app')

@section('body')

<div class="min-h-screen flex items-center justify-center p-4 bg-gradient-to-b from-blue-50/60 via-white to-indigo-50/40 relative overflow-hidden font-sans">
    
    <!-- Background Ambient Glowing Auras (Light) -->
    <div class="absolute -top-40 -left-40 w-[600px] h-[600px] rounded-full bg-blue-300/10 blur-[120px] animate-pulse pointer-events-none"></div>
    <div class="absolute -bottom-40 -right-40 w-[600px] h-[600px] rounded-full bg-indigo-300/10 blur-[120px] animate-pulse pointer-events-none" style="animation-delay: 3s;"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] rounded-full bg-cyan-200/5 blur-[150px] pointer-events-none"></div>

    <!-- Futuristic Tech Grid Overlay (Light Slate) -->
    <div class="absolute inset-0 bg-[linear-gradient(to_right,#e2e8f0_1px,transparent_1px),linear-gradient(to_bottom,#e2e8f0_1px,transparent_1px)] bg-[size:4rem_4rem] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_50%,#000_70%,transparent_100%)] opacity-35 pointer-events-none"></div>

    <div class="w-full max-w-md perspective-1000 relative z-10">

        <!-- Glowing Card border wrapper -->
        <div class="relative group rounded-3xl p-[1px] bg-gradient-to-b from-white via-slate-200/80 to-blue-200/40 shadow-[0_20px_50px_rgba(8,112,184,0.06)] transition duration-500 hover:shadow-[0_25px_60px_rgba(8,112,184,0.1)]">
            
            <div id="login-card" class="p-10 bg-white/80 border-0 backdrop-blur-2xl rounded-3xl">

                <!-- Logo / Brand -->
                <div class="flex justify-center mb-8">
                    <div class="relative group/logo">

                        <!-- Soft Blue Glow -->
                        <div class="absolute inset-0 bg-gradient-to-r from-blue-300 via-cyan-300 to-indigo-400 
                        rounded-3xl blur-2xl opacity-30 group-hover/logo:opacity-50 
                        transition duration-500 animate-pulse">
                        </div>

                        <!-- Main Container -->
                        <div class="relative w-24 h-24 rounded-3xl 
                        bg-gradient-to-br from-blue-400 via-cyan-400 to-indigo-500
                        p-[1px] shadow-lg shadow-blue-500/10 
                        group-hover/logo:scale-105 transition duration-500">

                            <!-- Glass Card -->
                            <div class="w-full h-full rounded-3xl bg-white/95 backdrop-blur-xl
                            flex items-center justify-center border border-white/80">

                                <!-- Logo -->
                                <img
                                    src="https://workdesk.salaryslip.co/user-uploads/app-logo/20dd6dc4180b7f57d3a6ec6e53582a34.png"
                                    alt="Logo"
                                    class="w-14 h-14 object-contain drop-shadow-[0_0_10px_rgba(59,130,246,0.3)]">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Title -->
                <div class="text-center mb-8">
                    <h2 class="text-3xl font-extrabold tracking-tight text-slate-900 mb-2">Welcome Back</h2>
                    <p class="text-slate-500 text-sm font-medium">Sign in to your Engineering Agent</p>
                </div>

                <!-- Form Container -->
                <div id="form-container" class="relative">

                    <!-- Email Login -->
                    <form id="email-form" class="space-y-5">

                        <div>
                            <label for="email" class="block text-xs font-bold text-blue-600 mb-1.5 uppercase tracking-widest">Email Address</label>
                            <input id="email" name="email" type="email" placeholder="you@example.com" class="w-full px-4 py-3 border border-slate-200 bg-white/90 text-slate-900 placeholder:text-slate-400 focus:border-blue-500 focus:ring-blue-500/10 rounded-xl outline-none transition-all" />
                        </div>

                        <div>
                            <div class="flex justify-between items-center mb-1.5">
                                <label for="password" class="block text-xs font-bold text-blue-600 uppercase tracking-widest">Password</label>
                                <a href="#" class="text-xs text-blue-600 hover:text-indigo-600 transition-colors font-semibold">Forgot?</a>
                            </div>
                            <input id="password" name="password" type="password" placeholder="••••••••" class="w-full px-4 py-3 border border-slate-200 bg-white/90 text-slate-900 placeholder:text-slate-400 focus:border-blue-500 focus:ring-blue-500/10 rounded-xl outline-none transition-all" />
                        </div>

                        <button class="w-full mt-2 py-3 px-4 font-bold text-center bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 border-0 text-white shadow-lg shadow-blue-500/15 hover:shadow-blue-500/25 transition-all duration-300 rounded-xl cursor-pointer flex items-center justify-center gap-2" type="submit" id="btn-email-login">
                            Sign In
                        </button>

                        <!-- Divider -->
                        <div class="relative flex py-2 items-center">
                            <div class="flex-grow border-t border-slate-200"></div>
                            <span class="flex-shrink mx-4 text-slate-400 text-xs font-bold uppercase tracking-wider">Or continue with</span>
                            <div class="flex-grow border-t border-slate-200"></div>
                        </div>

                        <!-- GitHub Sign In Button -->
                        <a href="{{ route('oauth.connect', ['provider' => 'github']) }}" class="w-full py-3 px-4 font-bold text-center bg-white hover:bg-slate-50 border border-slate-200 text-slate-800 shadow-sm hover:shadow transition-all duration-300 rounded-xl cursor-pointer flex items-center justify-center gap-2 no-underline" id="btn-github-login">
                            <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/>
                            </svg>
                            <span>Sign in with GitHub</span>
                        </a>

                        <!-- GitLab Sign In Button -->
                        <a href="{{ route('oauth.connect', ['provider' => 'gitlab']) }}" class="w-full py-3 px-4 font-bold text-center bg-slate-100 hover:bg-slate-200 border border-slate-300 text-slate-900 shadow-sm shadow-slate-300/20 transition-all duration-300 rounded-xl cursor-pointer flex items-center justify-center gap-2 no-underline" id="btn-gitlab-login">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path fill="#fc6d26" d="M12 0L1.999 21.165l10.001 2.835 9.999-2.835L12 0z"/>
                                <path fill="#e24329" d="M12 0l2.103 5.78H9.897L12 0zM1.999 21.165l9.001-13.93L1.999 21.165zM22 21.165l-9.999-13.93 9.999 13.93z"/>
                            </svg>
                            <span>Sign in with GitLab</span>
                        </a>

                    </form>

                </div>

                <!-- Global Error / Success Messages -->
                <div id="auth-message" class="mt-4 text-sm text-center {{ $errors->any() ? 'block text-red-600 bg-red-50 border border-red-200 p-3 rounded-xl' : 'hidden' }}">
                    @if ($errors->any())
                        {{ $errors->first() }}
                    @endif
                </div>

            </div>
        </div>

        <!-- Footer -->
        <div class="mt-8 text-center text-sm text-slate-500 font-medium">
            Don't have an account? <a href="#" class="text-blue-600 hover:text-indigo-600 font-bold transition-colors hover:underline">Register</a>
        </div>

    </div>

</div>

<!-- Expose Laravel routes to JS -->
<script>
    window.routes = {
        login: "{{ route('login.store') }}",
        dashboard: "{{ route('dashboard') }}"
    };

    document.addEventListener('DOMContentLoaded', function() {
        const emailForm = document.getElementById('email-form');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const btnLogin = document.getElementById('btn-email-login');
        const authMessage = document.getElementById('auth-message');

        emailForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // Clear messages
            authMessage.classList.add('hidden');
            authMessage.className = 'mt-4 text-sm text-center hidden';
            authMessage.innerText = '';

            const email = emailInput.value.trim();
            const password = passwordInput.value;

            if (!email || !password) {
                authMessage.innerText = 'Please enter both email and password.';
                authMessage.className = 'mt-4 text-sm text-center text-red-600 bg-red-50 border border-red-200 p-3 rounded-xl block';
                return;
            }

            // Show loading state
            btnLogin.disabled = true;
            const originalBtnText = btnLogin.innerHTML;
            btnLogin.innerHTML = `
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" style="width:1.25rem;height:1.25rem;vertical-align:middle;">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Signing In...
            `;

            fetch(window.routes.login, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    email: email,
                    password: password
                })
            })
            .then(async response => {
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || 'Login failed. Please try again.');
                }
                return data;
            })
            .then(data => {
                // Success!
                authMessage.innerText = 'Login successful! Redirecting...';
                authMessage.className = 'mt-4 text-sm text-center text-emerald-600 bg-emerald-50 border border-emerald-200 p-3 rounded-xl block';
                
                setTimeout(() => {
                    window.location.href = window.routes.dashboard;
                }, 800);
            })
            .catch(error => {
                btnLogin.disabled = false;
                btnLogin.innerHTML = originalBtnText;
                
                authMessage.innerText = error.message;
                authMessage.className = 'mt-4 text-sm text-center text-red-600 bg-red-50 border border-red-200 p-3 rounded-xl block';
            });
        });
    });
</script>

@endsection