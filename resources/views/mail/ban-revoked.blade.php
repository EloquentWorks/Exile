<x-mail::message>
# {{ $mail['heading'] ?? 'Your enforcement has been revoked' }}

{{ $mail['intro'] ?? 'The moderation enforcement applied to your account is no longer active.' }}

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