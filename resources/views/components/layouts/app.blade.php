<!DOCTYPE html>
<html data-theme="synthwave"
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    @class(['dark' => ($appearance ?? 'system') === 'dark'])>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Sovereign Manual') }}</title>

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

    @isset($alternate)
        <link href="{{ $alternate }}" rel="alternate">
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
