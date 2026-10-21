<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $title ?? 'SIGAT' }} | {{ config('app.name', 'SIGAT') }}</title>

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet" />

<style>
    [x-cloak] { display: none !important; }
</style>

@vite(['resources/css/app.css', 'resources/js/app.js'])
