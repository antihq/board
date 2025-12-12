<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ $title ?? config('app.name') }}</title>

<meta name="description" content="Starter Kit" />

<meta property="og:site_name" content="{{ config('app.name') }}" />
<meta property="og:title" content="{{ config('app.name') }}" />
<meta property="og:description" content="Starter Kit" />
<meta property="og:image" content="{{ url('/opengraph.png') }}" />
<meta property="og:url" content="{{ url('/') }}" />
<meta property="og:type" content="website" />

<meta name="twitter:title" content="{{ config('app.name') }}" />
<meta name="twitter:description" content="Starter Kit" />
<meta name="twitter:image" content="{{ url('/opengraph.png') }}" />
<meta name="twitter:image:alt" content="{{ config('app.name') }}" />
<meta name="twitter:card" content="summary_large_image" />

<link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
<link rel="icon" type="image/svg+xml" href="/favicon.svg" />
<link rel="shortcut icon" href="/favicon.ico" />
<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
<meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}" />
<link rel="manifest" href="/site.webmanifest" />

<link rel="preconnect" href="https://fonts.bunny.net" />
<link href="https://fonts.bunny.net/css?family=inter:400,500,600&display=swap" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])

@if ($dark ?? true)
    @fluxAppearance
@endif
