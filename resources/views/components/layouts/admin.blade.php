<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? 'Admin - TribeTrip' }}</title>

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
        {{-- Admin layout with sidebar navigation --}}
        <div class="min-h-screen flex">
            {{-- Sidebar --}}
            <aside class="hidden lg:flex lg:flex-col lg:w-64 lg:border-r lg:border-[#D4C9B8] dark:lg:border-zinc-700 lg:bg-white dark:lg:bg-zinc-800">
                {{-- Logo --}}
                <div class="flex h-16 items-center gap-2 px-6 border-b border-[#D4C9B8] dark:border-zinc-700">
                    <img src="{{ asset('images/logo1-icon-100.png') }}" alt="TribeTrip" class="h-8 w-8" />
                    <span class="text-lg font-semibold text-[#3D4A36] dark:text-white">TribeTrip</span>
                    <flux:badge size="sm" class="!bg-[#8B5A3C]/10 !text-[#8B5A3C]">Admin</flux:badge>
                </div>

                {{-- Navigation --}}
                <nav class="flex-1 px-4 py-4 space-y-1">
                    <flux:navlist>
                        <flux:navlist.item href="{{ route('dashboard') }}" icon="home" :current="request()->routeIs('dashboard')">
                            Dashboard
                        </flux:navlist.item>
                        <flux:navlist.item href="{{ route('admin.members') }}" icon="users" :current="request()->routeIs('admin.members*')">
                            Members
                        </flux:navlist.item>
                        <flux:navlist.item href="{{ route('admin.approvals') }}" icon="user-plus" :current="request()->routeIs('admin.approvals')">
                            Approval Queue
                        </flux:navlist.item>
                        <flux:navlist.item href="{{ route('admin.invitations') }}" icon="envelope" :current="request()->routeIs('admin.invitations*')">
                            Invitations
                        </flux:navlist.item>
                        <flux:navlist.group heading="Resources" expandable>
                            <flux:navlist.item href="{{ route('admin.resources') }}" icon="squares-2x2" :current="request()->routeIs('admin.resources*')">
                                All Resources
                            </flux:navlist.item>
                            <flux:navlist.item href="{{ route('admin.usage-logs') }}" icon="clipboard-document-list" :current="request()->routeIs('admin.usage-logs')">
                                Usage Logs
                            </flux:navlist.item>
                        </flux:navlist.group>
                    </flux:navlist>
                </nav>

                {{-- Back to Member View --}}
                <div class="px-4 py-2 border-t border-[#D4C9B8] dark:border-zinc-700">
                    <flux:button href="{{ route('member.resources') }}" variant="ghost" size="sm" icon="arrow-left" class="w-full justify-start !text-[#5A6350]">
                        Back to Member View
                    </flux:button>
                </div>

                {{-- User section --}}
                <div class="border-t border-[#D4C9B8] dark:border-zinc-700 p-4">
                    <div class="flex items-center gap-3">
                        <flux:avatar size="sm" name="{{ auth()->user()->name }}" />
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-[#3D4A36] dark:text-white truncate">
                                {{ auth()->user()->name }}
                            </div>
                            <div class="text-xs text-[#7A8B6E] truncate">
                                {{ auth()->user()->email }}
                            </div>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <flux:button type="submit" variant="ghost" size="sm" icon="arrow-right-start-on-rectangle" class="!text-[#5A6350]" />
                        </form>
                    </div>
                </div>
            </aside>

            {{-- Main content --}}
            <div class="flex-1 flex flex-col">
                {{-- Mobile header --}}
                <header class="lg:hidden flex h-16 items-center gap-4 px-4 border-b border-[#D4C9B8] dark:border-zinc-700 bg-white dark:bg-zinc-800">
                    <img src="{{ asset('images/logo1-icon-100.png') }}" alt="TribeTrip" class="h-8 w-8" />
                    <span class="text-lg font-semibold text-[#3D4A36] dark:text-white">Admin</span>
                    <div class="ml-auto">
                        <flux:dropdown>
                            <flux:button variant="ghost" icon="bars-3" class="!text-[#4A5240]" />
                            <flux:menu>
                                <flux:menu.item href="{{ route('dashboard') }}" icon="home">Dashboard</flux:menu.item>
                                <flux:menu.item href="{{ route('admin.members') }}" icon="users">Members</flux:menu.item>
                                <flux:menu.item href="{{ route('admin.approvals') }}" icon="user-plus">Approvals</flux:menu.item>
                                <flux:menu.item href="{{ route('admin.invitations') }}" icon="envelope">Invitations</flux:menu.item>
                                <flux:menu.item href="{{ route('admin.resources') }}" icon="squares-2x2">Resources</flux:menu.item>
                                <flux:menu.item href="{{ route('admin.usage-logs') }}" icon="clipboard-document-list">Usage Logs</flux:menu.item>
                                <flux:menu.separator />
                                <flux:menu.item href="{{ route('member.resources') }}" icon="arrow-left">Member View</flux:menu.item>
                                <flux:menu.separator />
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <flux:menu.item type="submit" icon="arrow-right-start-on-rectangle">Sign Out</flux:menu.item>
                                </form>
                            </flux:menu>
                        </flux:dropdown>
                    </div>
                </header>

                {{-- Page content --}}
                <main class="flex-1 p-6">
                    <div class="mx-auto max-w-6xl">
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>

        @fluxScripts

        <!-- PWA Scripts -->
        {!! PwaKit::scripts() !!}
    </body>
</html>
