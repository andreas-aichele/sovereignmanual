<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="synthwave" @class(['dark' => ($appearance ?? 'system') === 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name', 'Sovereign Manual') }}</title>

        @isset($description)
            <meta name="description" content="{{ $description }}">
        @endisset

        @isset($keywords)
            <meta name="keywords" content="{{ is_array($keywords) ? implode(', ', $keywords) : $keywords }}">
        @endisset

        @isset($canonical)
            <link rel="canonical" href="{{ $canonical }}">
        @endisset

        @isset($alternate)
            <link rel="alternate" href="{{ $alternate }}">
        @endisset

        <link rel="icon" href="/logo.svg" type="image/svg+xml">

        @fonts
        @vite('resources/js/app.js')
    </head>
    <body class="min-h-screen bg-base-100 font-sans text-base-content antialiased">
        <div class="min-h-screen">
            {{ $slot }}
        </div>
    </body>
</html>
