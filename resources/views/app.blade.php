@extends('layouts.app')

@section('body')
    <div id="engineering-agent-app">
        <div class="boot-screen">
            <div class="boot-mark">EA</div>
            <div>
                <p class="boot-title">Engineering Agent</p>
                <p class="boot-subtitle">Loading engineering intelligence...</p>
            </div>
        </div>
    </div>

    <script>
        window.__ENGINEERING_AGENT__ = @json($appData);
        window.__ENGINEERING_AGENT_CONFIG__ = {
            appName: @json(config('app.name', 'Engineering Agent')),
            csrfToken: @json(csrf_token()),
        };
    </script>
@endsection
