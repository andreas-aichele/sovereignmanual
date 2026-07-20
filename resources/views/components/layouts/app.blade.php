@php
    $appearance = in_array(
        $appearance ?? 'system',
        ['system', 'light', 'dark'],
        true,
    )
        ? $appearance
        : 'system';
    $isDarkAppearance = $appearance === 'dark';
    $pageThemeColor =
        $themeColor ?? ($isDarkAppearance ? '#20231f' : '#f7f3ea');
@endphp

<!DOCTYPE html>
<html data-appearance="{{ $appearance }}"
    data-theme="{{ $isDarkAppearance ? 'editorial-dark' : 'editorial-light' }}"
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    @class(['dark' => $isDarkAppearance])>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Sovereign Manual') }}</title>

    <meta name="robots" content="{{ $robots ?? 'index, follow' }}">
    <meta name="theme-color" content="{{ $pageThemeColor }}">

    @isset($author)
        <meta name="author" content="{{ $author }}">
    @endisset

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
    @isset($ogLocale)
        <meta property="og:locale" content="{{ $ogLocale }}">
    @endisset
    @isset($ogLocaleAlternates)
        @foreach ($ogLocaleAlternates as $ogLocaleAlternate)
            <meta property="og:locale:alternate"
                content="{{ $ogLocaleAlternate }}">
        @endforeach
    @endisset
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
    @isset($articlePublishedTime)
        <meta property="article:published_time"
            content="{{ $articlePublishedTime }}">
    @endisset
    @isset($articleModifiedTime)
        <meta property="article:modified_time"
            content="{{ $articleModifiedTime }}">
    @endisset
    @isset($articleSection)
        <meta property="article:section" content="{{ $articleSection }}">
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
