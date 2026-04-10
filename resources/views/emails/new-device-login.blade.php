@component('mail::message')
# New login detected

A new device just logged into your account.

**Device:** {{ $deviceType ?? 'Unknown' }}
**Browser:** {{ $browser ?? 'Unknown' }}
**Operating system:** {{ $os ?? 'Unknown' }}
**IP address:** {{ $ip }}
**Time:** {{ $when ?? now()->toFormattedDateString() }}

If this was you, you can ignore this message. If not, please review your active sessions and sign out any that you don't recognise.

@component('mail::button', ['url' => $sessionsUrl ?? url('/settings/sessions')])
Review active sessions
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
