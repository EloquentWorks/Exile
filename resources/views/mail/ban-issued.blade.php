<x-mail::message>
# {{ $mail['heading'] ?? 'Your access has been suspended' }}

{{ $mail['intro'] ?? 'A moderation enforcement has been applied to your account.' }}

@if ($showReason && $ban->reason !== null)
**{{ $mail['reason_label'] ?? 'Reason' }}:** {{ $ban->reason }}
@endif

@if ($showExpiration)
@if ($formattedExpiration !== null)
**{{ $mail['expiration_label'] ?? 'Expires' }}:** {{ $formattedExpiration }}
@else
{{ $mail['permanent_text'] ?? 'This enforcement is permanent.' }}
@endif
@endif

@if (
    ! empty($mail['action_text'])
    && ! empty($mail['action_url'])
)
<x-mail::button :url="$mail['action_url']">
{{ $mail['action_text'] }}
</x-mail::button>
@endif

@if (! empty($mail['outro']))
{{ $mail['outro'] }}
@endif

{{ $mail['salutation'] ?? 'Regards,' }}<br>
{{ config('app.name') }}
</x-mail::message>