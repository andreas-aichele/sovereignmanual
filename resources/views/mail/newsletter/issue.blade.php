<x-mail::message>
# {{ $issue->subject }}

{{ $issue->intro }}

@foreach ($issue->posts as $post)
## {{ $post['title'] }}

{{ $post['excerpt'] }}

<x-mail::button :url="$post['url']">
{{ __('newsletter.issue.read', [], $locale) }}
</x-mail::button>
@endforeach

{{ __('newsletter.issue.unsubscribe_prompt', [], $locale) }}

[{{ __('newsletter.issue.unsubscribe', [], $locale) }}]({{ $unsubscribeUrl }})
</x-mail::message>
