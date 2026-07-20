<x-mail::message>
# {{ __('newsletter.mail.heading', [], $locale) }}

{{ __('newsletter.mail.intro', [], $locale) }}

<x-mail::button :url="$confirmationUrl">
{{ __('newsletter.mail.confirm', [], $locale) }}
</x-mail::button>

{{ __('newsletter.mail.unsubscribe_prompt', [], $locale) }}

[{{ __('newsletter.mail.unsubscribe', [], $locale) }}]({{ $unsubscribeUrl }})

{{ __('newsletter.mail.closing', [], $locale) }},<br>
{{ config('app.name') }}
</x-mail::message>
