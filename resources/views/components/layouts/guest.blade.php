<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name', 'TribeTrip') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxAppearance

        <!-- PWA Support -->
        {!! PwaKit::head() !!}
    </head>
    <body class="min-h-screen bg-[#F2EDE4] dark:bg-zinc-900 antialiased">
        {{-- Guest layout for authentication pages --}}
        <div class="flex min-h-screen flex-col items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
            {{-- Logo and branding --}}
            <div class="mb-8 text-center">
                <a href="/" class="inline-flex items-center gap-2">
                    <img src="{{ asset('images/logo1-icon-100.png') }}" alt="TribeTrip" class="h-12 w-12" />
                    <span class="text-2xl font-semibold text-[#3D4A36] dark:text-white">TribeTrip</span>
                </a>
                <p class="mt-2 text-sm text-[#5A6350] dark:text-zinc-400">Community Resource Sharing</p>
            </div>

            {{-- Main content slot --}}
            <div class="w-full max-w-md">
                {{ $slot }}
            </div>

            {{-- Footer --}}
            <div class="mt-8 text-center text-xs text-[#7A8B6E] dark:text-zinc-400">
                &copy; {{ date('Y') }} TribeTrip. All rights reserved.
            </div>
        </div>

        @fluxScripts

        <!-- PWA Scripts -->
        {!! PwaKit::scripts() !!}
    </body>
</html>
