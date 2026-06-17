<!DOCTYPE html>
<html data-theme="synthwave"
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    @class(['dark' => ($appearance ?? 'system') === 'dark'])>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Sovereign Manual') }}</title>

    <meta name="robots" content="{{ $robots ?? 'index, follow' }}">

    @isset($description)
        <meta name="description" content="{{ $description }}">
    @endisset

    @isset($keywords)
        <meta name="keywords"
            content="{{ is_array($keywords) ? implode(', ', $keywords) : $keywords }}">
    @endisset

    @isset($canonical)
        <link href="{{ $canonical }}" rel="canonical">
    @endisset

    @isset($alternates)
        @foreach ($alternates as $alternateLocale => $alternateUrl)
            <link href="{{ $alternateUrl }}" hreflang="{{ $alternateLocale }}"
                rel="alternate">
        @endforeach
    @endisset

    @isset($xDefault)
        <link href="{{ $xDefault }}" hreflang="x-default" rel="alternate">
    @endisset

    <meta property="og:type" content="{{ $ogType ?? 'website' }}">
    <meta property="og:title"
        content="{{ $ogTitle ?? ($title ?? config('app.name', 'Sovereign Manual')) }}">
    @isset($description)
        <meta property="og:description" content="{{ $description }}">
    @endisset
    @isset($canonical)
        <meta property="og:url" content="{{ $canonical }}">
    @endisset
    @isset($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
    @endisset
    <meta name="twitter:card"
        content="{{ isset($ogImage) ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title"
        content="{{ $ogTitle ?? ($title ?? config('app.name', 'Sovereign Manual')) }}">
    @isset($description)
        <meta name="twitter:description" content="{{ $description }}">
    @endisset
    @isset($ogImage)
        <meta name="twitter:image" content="{{ $ogImage }}">
    @endisset

    @isset($structuredData)
        <script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @endisset

    <link type="image/svg+xml" href="/logo.svg" rel="icon">

    @fonts
    @vite('resources/css/app.css')
</head>

<body class="bg-base-100 text-base-content min-h-screen font-sans antialiased">
    <div class="min-h-screen">
        {{ $slot }}
    </div>

    @vite('resources/js/app.js')
</body>

</html>
