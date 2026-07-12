<x-mail::message>
# {{ __('You have been invited to a camp') }}

@if ($inviterName)
{{ __(':name invites you to join the camp :camp.', ['name' => $inviterName, 'camp' => $campName]) }}
@else
{{ __('You have been invited to join the camp :camp.', ['camp' => $campName]) }}
@endif

<x-mail::button :url="$acceptUrl">
{{ __('Join the camp') }}
</x-mail::button>

{{ __('If the button does not work, copy this link into your browser:') }}

{{ $acceptUrl }}

{{ __('Thanks') }},<br>
{{ config('app.name') }}
</x-mail::message>
