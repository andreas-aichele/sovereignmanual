<x-mail::message>
# {{ __('waitlist.mail.heading', [], $locale) }}

{{ __('waitlist.mail.intro', [], $locale) }}

<x-mail::button :url="$confirmationUrl">
{{ __('waitlist.mail.confirm', [], $locale) }}
</x-mail::button>

{{ __('waitlist.mail.unsubscribe_prompt', [], $locale) }}

[{{ __('waitlist.mail.unsubscribe', [], $locale) }}]({{ $unsubscribeUrl }})

{{ __('waitlist.mail.closing', [], $locale) }},<br>
{{ config('app.name') }}
</x-mail::message>
