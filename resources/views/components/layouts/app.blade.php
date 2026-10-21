@props([
    'title' => 'SIGAT',
    'moduleLabel' => 'Sistema',
    'pageTitle' => 'Dashboard',
    'page' => [],
    'commandPaletteActions' => null,
])

@php
    $page = array_merge([
        'title' => $title,
        'moduleLabel' => $moduleLabel,
        'pageTitle' => $pageTitle,
        'headerTitle' => $pageTitle,
        'headerDescription' => null,
    ], $page);

    $commandPaletteActions = $commandPaletteActions ?? config('navigation.command_palette_actions', []);
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('components.layouts.partials.head', ['title' => $page['title']])
    </head>
    <body class="min-h-screen" x-data @keydown.ctrl.k.window.prevent="$store.commandPalette.toggle()">

        {{-- Sidebar Rail + Panel --}}
        @include('components.layouts.partials.sidebar')

        {{-- Main Content Area --}}
        <div class="ml-64 transition-all duration-300">
            <div class="max-w-[1400px] mx-auto px-6 py-4">

                {{-- Topbar --}}
                @include('components.layouts.partials.topbar', [
                    'moduleLabel' => $page['moduleLabel'],
                    'pageTitle' => $page['pageTitle'],
                ])

                {{-- Flash Messages --}}
                <x-ui.flash />

                {{-- Page Content --}}
                <main class="animate-fade-in">
                    {{ $slot }}
                </main>
            </div>
        </div>

        {{-- Command Palette --}}
        @include('components.layouts.partials.command-palette', [
            'page' => $page,
            'actions' => $commandPaletteActions,
        ])
    </body>
</html>
