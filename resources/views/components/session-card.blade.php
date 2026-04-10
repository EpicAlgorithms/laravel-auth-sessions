@props(['session', 'isCurrent' => false])

<div class="bg-white rounded-lg border @if($isCurrent) border-green-400 ring-2 ring-green-200 @else border-gray-200 @endif p-4 flex items-start gap-4">
    <x-auth-sessions::device-icon :type="$session->device_type_id" class="flex-shrink-0 w-10 h-10 text-gray-500" />

    <div class="flex-1 min-w-0">
        <div class="font-medium text-gray-900">
            {{ $session->browser_name ?? 'Unknown browser' }}@if($session->browser_version) <span class="text-gray-500 font-normal">({{ $session->browser_version }})</span>@endif
            @if($session->os_name) on {{ $session->os_name }}@endif
        </div>

        <div class="text-sm text-gray-500 mt-1">
            <span>{{ $session->ip_address }}</span>
            @if($session->last_seen_at)
                <span class="mx-1">&middot;</span>
                <span>Last active {{ $session->last_seen_at->diffForHumans() }}</span>
            @endif
        </div>

        @if($isCurrent)
            <span class="inline-block mt-2 px-2 py-0.5 text-xs font-medium bg-green-100 text-green-800 rounded">
                Current session
            </span>
        @endif
    </div>

    @if(! $isCurrent)
        <form method="POST" action="{{ route('auth-sessions.destroy', $session->id) }}" class="flex-shrink-0">
            @csrf
            @method('DELETE')
            <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-white hover:bg-red-50 border border-red-300 text-red-700 text-sm font-medium rounded-md">
                Sign out
            </button>
        </form>
    @endif
</div>
