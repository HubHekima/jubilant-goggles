<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? 'Purchase Ticket' }}</title>

        @fluxAppearance
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-900 antialiased">
        <flux:main container>
            {{-- Just the main content area, no sidebar --}}
            <div class="max-w-2xl mx-auto py-10">
                {{ $slot }}
            </div>
        </flux:main>

        @persist('flux-scripts')
            @fluxScripts
        @endpersist
    </body>
</html>
