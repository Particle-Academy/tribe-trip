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
        {{-- App layout for authenticated users --}}
        <div class="min-h-screen">
            {{-- Navigation Header --}}
            <header class="sticky top-0 z-50 border-b border-[#D4C9B8] dark:border-zinc-700 bg-white/90 dark:bg-zinc-800/90 backdrop-blur-sm">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 items-center justify-between">
                        {{-- Logo --}}
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2">
                            <img src="{{ asset('images/logo1-icon-100.png') }}" alt="TribeTrip" class="h-10 w-10" />
                            <span class="text-xl font-semibold text-[#3D4A36] dark:text-white">TribeTrip</span>
                        </a>

                        {{-- Navigation --}}
                        <nav class="hidden md:flex items-center gap-6">
                            <a href="{{ route('member.resources') }}" class="text-sm font-medium text-[#5A6350] hover:text-[#3D4A36] dark:text-zinc-300 dark:hover:text-white {{ request()->routeIs('member.resources*') ? '!text-[#4A5240] dark:!text-white' : '' }}">
                                Resources
                            </a>
                            <a href="{{ route('member.reservations') }}" class="text-sm font-medium text-[#5A6350] hover:text-[#3D4A36] dark:text-zinc-300 dark:hover:text-white {{ request()->routeIs('member.reservations*') ? '!text-[#4A5240] dark:!text-white' : '' }}">
                                My Reservations
                            </a>
                            <a href="{{ route('member.profile') }}" class="text-sm font-medium text-[#5A6350] hover:text-[#3D4A36] dark:text-zinc-300 dark:hover:text-white {{ request()->routeIs('member.profile') ? '!text-[#4A5240] dark:!text-white' : '' }}">
                                Profile
                            </a>
                            @if(auth()->user()->isAdmin())
                                <a href="{{ route('admin.members') }}" class="text-sm font-medium text-[#8B5A3C] hover:text-[#6B4A2C] dark:text-[#C9B896] dark:hover:text-white">
                                    Admin
                                </a>
                            @endif
                        </nav>

                        {{-- User Menu --}}
                        <div class="flex items-center gap-4">
                            <flux:dropdown>
                                <flux:button variant="ghost" class="!text-[#4A5240]">
                                    <flux:avatar size="sm" name="{{ auth()->user()->name }}" class="mr-2" />
                                    <span class="hidden sm:inline">{{ auth()->user()->name }}</span>
                                    <flux:icon name="chevron-down" variant="micro" class="ml-1" />
                                </flux:button>

                                <flux:menu>
                                    <flux:menu.item href="{{ route('member.profile') }}" icon="user">
                                        Profile
                                    </flux:menu.item>
                                    <flux:menu.item href="{{ route('member.reservations') }}" icon="calendar">
                                        My Reservations
                                    </flux:menu.item>
                                    @if(auth()->user()->isAdmin())
                                        <flux:menu.separator />
                                        <flux:menu.item href="{{ route('admin.members') }}" icon="cog-6-tooth">
                                            Admin Panel
                                        </flux:menu.item>
                                    @endif
                                    <flux:menu.separator />
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <flux:menu.item type="submit" icon="arrow-right-start-on-rectangle">
                                            Sign Out
                                        </flux:menu.item>
                                    </form>
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </div>
                </div>
            </header>

            {{-- Page content --}}
            <main class="py-6">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    {{ $slot }}
                </div>
            </main>

            {{-- Footer --}}
            <footer class="border-t border-[#D4C9B8] dark:border-zinc-700 bg-white dark:bg-zinc-800 py-6 mt-auto">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-2">
                            <img src="{{ asset('images/logo1-icon-100.png') }}" alt="TribeTrip" class="h-6 w-6" />
                            <span class="text-sm font-medium text-[#4A5240] dark:text-white">TribeTrip</span>
                        </div>
                        <p class="text-xs text-[#7A8B6E] dark:text-zinc-400">
                            &copy; {{ date('Y') }} TribeTrip. Community resource sharing made simple.
                        </p>
                    </div>
                </div>
            </footer>
        </div>

        @fluxScripts

        <!-- PWA Scripts -->
        {!! PwaKit::scripts() !!}
    </body>
</html>
