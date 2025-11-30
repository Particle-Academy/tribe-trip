<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'TribeTrip') }} - Community Resource Sharing</title>

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
        {{-- Navigation Header --}}
        <header class="sticky top-0 z-50 border-b border-[#D4C9B8] dark:border-zinc-700 bg-[#F2EDE4]/90 dark:bg-zinc-900/80 backdrop-blur-sm">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    {{-- Logo --}}
                    <a href="/" class="inline-flex items-center gap-2">
                        <img src="{{ asset('images/logo1-icon-100.png') }}" alt="TribeTrip" class="h-10 rounded-lg w-10" />
                        <span class="text-xl font-semibold text-[#4A5240] dark:text-white">TribeTrip</span>
                    </a>

                    {{-- Navigation Links --}}
            @if (Route::has('login'))
                        <nav class="flex items-center gap-3">
                    @auth
                                <flux:button href="{{ url('/dashboard') }}" variant="ghost" size="sm">
                            Dashboard
                                </flux:button>
                    @else
                                <flux:button href="{{ route('login') }}" variant="ghost" size="sm">
                            Log in
                                </flux:button>
                        @if (Route::has('register'))
                                    <flux:button href="{{ route('register') }}" variant="primary" size="sm">
                                        Get Started
                                    </flux:button>
                        @endif
                    @endauth
                </nav>
            @endif
                </div>
            </div>
        </header>

        <main>
            {{-- Hero Section --}}
            <section class="relative overflow-hidden bg-gradient-to-b from-[#E8E2D6] to-[#F2EDE4] dark:from-zinc-800 dark:to-zinc-900">
                <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 sm:py-28 lg:px-8">
                    <div class="text-center">
                        <flux:heading size="xl" level="1" class="text-4xl sm:text-5xl lg:text-6xl font-bold tracking-tight text-[#3D4A36] dark:text-white">
                            Share resources.<br class="hidden sm:block" />
                            <span class="text-[#8B5A3C] dark:text-[#C9B896]">Build community.</span>
                        </flux:heading>
                        <flux:text class="mx-auto mt-6 max-w-2xl text-lg sm:text-xl text-[#5A6350] dark:text-zinc-300">
                            TribeTrip makes it easy for small communities to share vehicles, equipment, and spaces — with transparent tracking and fair billing for everyone.
                        </flux:text>
                        <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                            @if (Route::has('register'))
                                <flux:button href="{{ route('register') }}" variant="filled" class="w-full sm:w-auto px-8 py-3 !bg-[#4A5240] hover:!bg-[#3D4A36] !text-white">
                                    Join Your Community
                                </flux:button>
                            @endif
                            <flux:button href="#how-it-works" variant="ghost" class="w-full sm:w-auto px-8 py-3 !text-[#4A5240] hover:!bg-[#4A5240]/10">
                                See How It Works
                            </flux:button>
                        </div>
                    </div>
                </div>
                {{-- Decorative gradient orb --}}
                <div class="absolute -top-40 -right-40 h-80 w-80 rounded-full bg-[#7A8B6E]/20 blur-3xl dark:bg-[#7A8B6E]/10"></div>
                <div class="absolute -bottom-40 -left-40 h-80 w-80 rounded-full bg-[#8B5A3C]/20 blur-3xl dark:bg-[#8B5A3C]/10"></div>
            </section>

            {{-- UVP Features Section --}}
            <section class="py-20 sm:py-28 bg-white dark:bg-zinc-900">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="text-center mb-16">
                        <flux:heading size="lg" level="2" class="text-3xl sm:text-4xl font-bold text-zinc-900 dark:text-white">
                            Everything you need to share resources
                        </flux:heading>
                        <flux:text class="mt-4 max-w-2xl mx-auto text-zinc-600 dark:text-zinc-400">
                            From browsing available resources to automated billing — TribeTrip handles it all.
                        </flux:text>
                    </div>

                    <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                        {{-- Feature: Resource Catalog --}}
                        <flux:card class="p-6 hover:shadow-lg transition-shadow">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-[#E8E2D6] dark:bg-[#4A5240]/30 mb-4">
                                <flux:icon name="squares-2x2" class="h-6 w-6 text-[#4A5240] dark:text-[#7A8B6E]" />
                            </div>
                            <flux:heading size="lg" class="text-xl font-semibold text-[#3D4A36] dark:text-white">
                                Browse & Discover
                            </flux:heading>
                            <flux:text class="mt-2 text-[#5A6350] dark:text-zinc-400">
                                Explore your community's shared resources — vehicles, equipment, and spaces — with images, descriptions, and real-time availability.
                            </flux:text>
                        </flux:card>

                        {{-- Feature: Booking Calendar --}}
                        <flux:card class="p-6 hover:shadow-lg transition-shadow">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-[#E8E2D6] dark:bg-[#4A5240]/30 mb-4">
                                <flux:icon name="calendar-days" class="h-6 w-6 text-[#4A5240] dark:text-[#7A8B6E]" />
                            </div>
                            <flux:heading size="lg" class="text-xl font-semibold text-[#3D4A36] dark:text-white">
                                Easy Reservations
                            </flux:heading>
                            <flux:text class="mt-2 text-[#5A6350] dark:text-zinc-400">
                                Visual calendar shows when resources are available. Book time slots with automatic conflict prevention — no double bookings.
                            </flux:text>
                        </flux:card>

                        {{-- Feature: My Reservations --}}
                        <flux:card class="p-6 hover:shadow-lg transition-shadow">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-[#E8E2D6] dark:bg-[#4A5240]/30 mb-4">
                                <flux:icon name="clipboard-document-list" class="h-6 w-6 text-[#4A5240] dark:text-[#7A8B6E]" />
                            </div>
                            <flux:heading size="lg" class="text-xl font-semibold text-[#3D4A36] dark:text-white">
                                Track Your Bookings
                            </flux:heading>
                            <flux:text class="mt-2 text-[#5A6350] dark:text-zinc-400">
                                All your reservations in one place. See status, manage check-out and check-in, and view your usage history.
                            </flux:text>
                        </flux:card>

                        {{-- Feature: Usage Logger with Photo Capture --}}
                        <flux:card class="p-6 hover:shadow-lg transition-shadow border-2 border-[#8B5A3C]/30 dark:border-[#8B5A3C]/50">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-[#8B5A3C] mb-4">
                                <flux:icon name="camera" class="h-6 w-6 text-white" />
                            </div>
                            <div class="flex items-center gap-2 mb-2">
                                <flux:heading size="lg" class="text-xl font-semibold text-[#3D4A36] dark:text-white">
                                    Photo Evidence
                                </flux:heading>
                                <span class="inline-flex items-center rounded-full bg-[#8B5A3C]/10 dark:bg-[#8B5A3C]/30 px-2 py-0.5 text-xs font-medium text-[#8B5A3C] dark:text-[#C9B896]">
                                    Key Feature
                                </span>
                            </div>
                            <flux:text class="mt-2 text-[#5A6350] dark:text-zinc-400">
                                Capture odometer readings, equipment condition, and meter readings during check-out/in. Photo evidence builds trust and ensures accurate billing.
                            </flux:text>
                        </flux:card>

                        {{-- Feature: Automated Billing --}}
                        <flux:card class="p-6 hover:shadow-lg transition-shadow">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-[#E8E2D6] dark:bg-[#4A5240]/30 mb-4">
                                <flux:icon name="receipt-percent" class="h-6 w-6 text-[#4A5240] dark:text-[#7A8B6E]" />
                            </div>
                            <flux:heading size="lg" class="text-xl font-semibold text-[#3D4A36] dark:text-white">
                                Fair & Transparent Billing
                            </flux:heading>
                            <flux:text class="mt-2 text-[#5A6350] dark:text-zinc-400">
                                Monthly invoices generated automatically based on actual usage. View itemized charges and download PDF invoices — no guesswork.
                            </flux:text>
                        </flux:card>

                        {{-- Feature: PWA Mobile Experience --}}
                        <flux:card class="p-6 hover:shadow-lg transition-shadow">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-[#E8E2D6] dark:bg-[#4A5240]/30 mb-4">
                                <flux:icon name="device-phone-mobile" class="h-6 w-6 text-[#4A5240] dark:text-[#7A8B6E]" />
                            </div>
                            <flux:heading size="lg" class="text-xl font-semibold text-[#3D4A36] dark:text-white">
                                Mobile-First Experience
                            </flux:heading>
                            <flux:text class="mt-2 text-[#5A6350] dark:text-zinc-400">
                                Works like a native app on your phone. Check out and check in resources on location — even with spotty connectivity.
                            </flux:text>
                        </flux:card>
                    </div>
                </div>
            </section>

            {{-- How It Works Section --}}
            <section id="how-it-works" class="py-20 sm:py-28 bg-[#E8E2D6] dark:bg-zinc-800/50">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="text-center mb-16">
                        <flux:heading size="lg" level="2" class="text-3xl sm:text-4xl font-bold text-[#3D4A36] dark:text-white">
                            How It Works
                        </flux:heading>
                        <flux:text class="mt-4 max-w-2xl mx-auto text-[#5A6350] dark:text-zinc-400">
                            Four simple steps from browsing to billing.
                        </flux:text>
                    </div>

                    <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-4">
                        {{-- Step 1: Browse --}}
                        <div class="relative text-center">
                            <div class="flex h-16 w-16 mx-auto items-center justify-center rounded-full bg-[#4A5240] text-white text-2xl font-bold">
                                1
                            </div>
                            <flux:heading class="mt-4 text-lg font-semibold text-[#3D4A36] dark:text-white">
                                Browse Catalog
                            </flux:heading>
                            <flux:text class="mt-2 text-[#5A6350] dark:text-zinc-400">
                                Explore available resources with photos, pricing, and availability.
                            </flux:text>
                            {{-- Connector line (hidden on mobile) --}}
                            <div class="hidden lg:block absolute top-8 left-[60%] w-[80%] border-t-2 border-dashed border-[#7A8B6E]/50 dark:border-zinc-600"></div>
                        </div>

                        {{-- Step 2: Reserve --}}
                        <div class="relative text-center">
                            <div class="flex h-16 w-16 mx-auto items-center justify-center rounded-full bg-[#4A5240] text-white text-2xl font-bold">
                                2
                            </div>
                            <flux:heading class="mt-4 text-lg font-semibold text-[#3D4A36] dark:text-white">
                                Reserve Time
                            </flux:heading>
                            <flux:text class="mt-2 text-[#5A6350] dark:text-zinc-400">
                                Pick your time slot on the calendar and confirm your booking.
                            </flux:text>
                            <div class="hidden lg:block absolute top-8 left-[60%] w-[80%] border-t-2 border-dashed border-[#7A8B6E]/50 dark:border-zinc-600"></div>
                        </div>

                        {{-- Step 3: Use & Track --}}
                        <div class="relative text-center">
                            <div class="flex h-16 w-16 mx-auto items-center justify-center rounded-full bg-[#8B5A3C] text-white text-2xl font-bold">
                                3
                            </div>
                            <flux:heading class="mt-4 text-lg font-semibold text-[#3D4A36] dark:text-white">
                                Use & Track
                            </flux:heading>
                            <flux:text class="mt-2 text-[#5A6350] dark:text-zinc-400">
                                Check out the resource, capture photos, and check in when done.
                            </flux:text>
                            <div class="hidden lg:block absolute top-8 left-[60%] w-[80%] border-t-2 border-dashed border-[#7A8B6E]/50 dark:border-zinc-600"></div>
                        </div>

                        {{-- Step 4: Get Billed --}}
                        <div class="text-center">
                            <div class="flex h-16 w-16 mx-auto items-center justify-center rounded-full bg-[#4A5240] text-white text-2xl font-bold">
                                4
                            </div>
                            <flux:heading class="mt-4 text-lg font-semibold text-[#3D4A36] dark:text-white">
                                Get Billed
                            </flux:heading>
                            <flux:text class="mt-2 text-[#5A6350] dark:text-zinc-400">
                                Receive monthly invoices with transparent, usage-based charges.
                            </flux:text>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Key Benefits Section --}}
            <section class="py-20 sm:py-28 bg-white dark:bg-zinc-900">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="grid gap-12 lg:grid-cols-2 lg:gap-16 items-center">
                        <div>
                            <flux:heading size="lg" level="2" class="text-3xl sm:text-4xl font-bold text-[#3D4A36] dark:text-white">
                                Why communities choose TribeTrip
                            </flux:heading>
                            <flux:text class="mt-4 text-[#5A6350] dark:text-zinc-400">
                                Built for small communities that want to share resources fairly and efficiently.
                            </flux:text>

                            <div class="mt-8 space-y-6">
                                {{-- Benefit 1 --}}
                                <div class="flex gap-4">
                                    <div class="flex-shrink-0">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#E8E2D6] dark:bg-[#4A5240]/30">
                                            <flux:icon name="check-circle" class="h-6 w-6 text-[#4A5240] dark:text-[#7A8B6E]" />
                                        </div>
                                    </div>
                                    <div>
                                        <flux:heading class="text-base font-semibold text-[#3D4A36] dark:text-white">
                                            Easy Discovery
                                        </flux:heading>
                                        <flux:text class="mt-1 text-[#5A6350] dark:text-zinc-400">
                                            Browse resources with photos and see availability at a glance.
                                        </flux:text>
                                    </div>
                                </div>

                                {{-- Benefit 2 --}}
                                <div class="flex gap-4">
                                    <div class="flex-shrink-0">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#E8E2D6] dark:bg-[#4A5240]/30">
                                            <flux:icon name="check-circle" class="h-6 w-6 text-[#4A5240] dark:text-[#7A8B6E]" />
                                        </div>
                                    </div>
                                    <div>
                                        <flux:heading class="text-base font-semibold text-[#3D4A36] dark:text-white">
                                            Conflict-Free Booking
                                        </flux:heading>
                                        <flux:text class="mt-1 text-[#5A6350] dark:text-zinc-400">
                                            Calendar-based reservations with automatic double-booking prevention.
                                        </flux:text>
                                    </div>
                                </div>

                                {{-- Benefit 3 --}}
                                <div class="flex gap-4">
                                    <div class="flex-shrink-0">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#E8E2D6] dark:bg-[#4A5240]/30">
                                            <flux:icon name="check-circle" class="h-6 w-6 text-[#4A5240] dark:text-[#7A8B6E]" />
                                        </div>
                                    </div>
                                    <div>
                                        <flux:heading class="text-base font-semibold text-[#3D4A36] dark:text-white">
                                            Transparent Tracking
                                        </flux:heading>
                                        <flux:text class="mt-1 text-[#5A6350] dark:text-zinc-400">
                                            Photo evidence and automated logging ensure everyone is accountable.
                                        </flux:text>
                                    </div>
                                </div>

                                {{-- Benefit 4 --}}
                                <div class="flex gap-4">
                                    <div class="flex-shrink-0">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#E8E2D6] dark:bg-[#4A5240]/30">
                                            <flux:icon name="check-circle" class="h-6 w-6 text-[#4A5240] dark:text-[#7A8B6E]" />
                                        </div>
                                    </div>
                                    <div>
                                        <flux:heading class="text-base font-semibold text-[#3D4A36] dark:text-white">
                                            Fair Billing
                                        </flux:heading>
                                        <flux:text class="mt-1 text-[#5A6350] dark:text-zinc-400">
                                            Automated invoices based on actual usage — no guesswork or disputes.
                                        </flux:text>
                                    </div>
                                </div>

                                {{-- Benefit 5 --}}
                                <div class="flex gap-4">
                                    <div class="flex-shrink-0">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#E8E2D6] dark:bg-[#4A5240]/30">
                                            <flux:icon name="check-circle" class="h-6 w-6 text-[#4A5240] dark:text-[#7A8B6E]" />
                                        </div>
                                    </div>
                                    <div>
                                        <flux:heading class="text-base font-semibold text-[#3D4A36] dark:text-white">
                                            Mobile-First
                                        </flux:heading>
                                        <flux:text class="mt-1 text-[#5A6350] dark:text-zinc-400">
                                            Works offline on your phone — perfect for check-out/in on location.
                                        </flux:text>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Illustration/Visual with logo --}}
                        <div class="relative">
                            <div class="aspect-square rounded-2xl bg-gradient-to-br from-[#E8E2D6] to-[#D4C9B8] dark:from-[#4A5240]/30 dark:to-[#3D4A36]/30 p-8 flex items-center justify-center">
                                <div class="text-center">
                                    <img src="{{ asset('images/logo1-full-700.png') }}" alt="TribeTrip" class="max-w-[280px] mx-auto mb-6" />
                                    <div class="mt-4 flex justify-center gap-4">
                                        <div class="flex h-16 w-16 items-center justify-center rounded-xl bg-white/80 dark:bg-zinc-800/80">
                                            <flux:icon name="truck" class="h-8 w-8 text-[#4A5240] dark:text-[#7A8B6E]" />
                                        </div>
                                        <div class="flex h-16 w-16 items-center justify-center rounded-xl bg-white/80 dark:bg-zinc-800/80">
                                            <flux:icon name="wrench-screwdriver" class="h-8 w-8 text-[#8B5A3C] dark:text-[#C9B896]" />
                                        </div>
                                        <div class="flex h-16 w-16 items-center justify-center rounded-xl bg-white/80 dark:bg-zinc-800/80">
                                            <flux:icon name="home-modern" class="h-8 w-8 text-[#4A5240] dark:text-[#7A8B6E]" />
                                        </div>
                                    </div>
                                    <flux:text class="mt-4 text-[#5A6350] dark:text-zinc-400 font-medium">
                                        Vehicles • Equipment • Spaces
                                    </flux:text>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Final CTA Section --}}
            <section class="py-20 sm:py-28 bg-[#4A5240] dark:bg-[#3D4A36]">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 text-center">
                    <flux:heading size="lg" level="2" class="text-3xl sm:text-4xl font-bold text-white">
                        Ready to start sharing?
                    </flux:heading>
                    <flux:text class="mt-4 max-w-2xl mx-auto text-lg text-[#C9D4BE]">
                        Join your community and start booking shared resources today.
                    </flux:text>
                    <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                        @if (Route::has('register'))
                            <flux:button href="{{ route('register') }}" variant="filled" class="w-full sm:w-auto px-8 py-3 !bg-white !text-[#4A5240] hover:!bg-[#F2EDE4]">
                                Get Started Free
                            </flux:button>
                        @endif
                        @if (Route::has('login'))
                            <flux:button href="{{ route('login') }}" variant="ghost" class="w-full sm:w-auto px-8 py-3 !text-white !border-white/30 hover:!bg-white/10">
                                Sign In
                            </flux:button>
                        @endif
                    </div>
                </div>
            </section>
        </main>

        {{-- Footer --}}
        <footer class="bg-[#3D4A36] dark:bg-zinc-950 py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-2">
                        <img src="{{ asset('images/logo1-icon-100.png') }}" alt="TribeTrip" class="h-8 w-8" />
                        <span class="text-lg font-semibold text-white">TribeTrip</span>
                    </div>
                    <flux:text class="text-[#A8B89E] text-sm">
                        &copy; {{ date('Y') }} TribeTrip. Community resource sharing made simple.
                    </flux:text>
                </div>
            </div>
        </footer>

        @fluxScripts

        <!-- PWA Scripts -->
        {!! PwaKit::scripts() !!}
    </body>
</html>
