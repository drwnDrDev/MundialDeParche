<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Open Graph / Social sharing -->
        <meta property="og:type"        content="website" />
        <meta property="og:site_name"   content="Mundial de Parche" />
        <meta property="og:title"       content="Mundial de Parche — Quiniela FIFA 2026" />
        <meta property="og:description" content="La quiniela del parche. Predice los goles, los clasificados y el campeón del Mundial 2026." />
        <meta property="og:image"       content="{{ url('images/og.png') }}" />
        <meta property="og:image:width" content="1200" />
        <meta property="og:image:height" content="630" />
        <meta property="og:url"         content="{{ url('/') }}" />
        <meta name="twitter:card"       content="summary_large_image" />
        <meta name="twitter:image"      content="{{ url('images/og.png') }}" />

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Bungee&family=Bungee+Inline&family=Bowlby+One&family=Anton&family=Archivo+Black&family=Space+Grotesk:wght@400;500;600;700&family=VT323&family=Press+Start+2P&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @viteReactRefresh
        @vite(['resources/js/app.jsx', "resources/js/Pages/{$page['component']}.jsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
