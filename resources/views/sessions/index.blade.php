@extends(config('auth-sessions.layout', 'layouts.app'))

@section('title', 'Active Sessions')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Active Sessions</h1>
            <p class="text-sm text-gray-500 mt-1">These devices are currently signed in to your account.</p>
        </div>
        @if($sessions->count() > 1)
            <form method="POST" action="{{ route('auth-sessions.destroy-others') }}" onsubmit="return confirm('Sign out all other sessions?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md shadow-sm">
                    Sign out other sessions
                </button>
            </form>
        @endif
    </div>

    @if(session('status'))
        <div class="mb-4 p-4 rounded-md bg-green-50 border border-green-200 text-green-800 text-sm">
            {{ session('status') }}
        </div>
    @endif

    <div class="space-y-4">
        @forelse($sessions as $session)
            <x-auth-sessions::session-card
                :session="$session"
                :is-current="$currentSession && $currentSession->id === $session->id" />
        @empty
            <div class="text-center py-12 text-gray-500">No active sessions.</div>
        @endforelse
    </div>
</div>
@endsection
