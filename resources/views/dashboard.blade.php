<x-layouts.app>
    <x-slot:title>Dashboard - TribeTrip</x-slot:title>

    {{-- Dashboard for approved members --}}
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <flux:heading size="xl" class="!text-[#3D4A36]">Dashboard</flux:heading>
        </div>

        {{-- Welcome Card --}}
        <flux:card class="!bg-white">
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-[#E8E2D6]">
                    <flux:icon name="hand-raised" class="h-6 w-6 text-[#4A5240]" />
                </div>
                <div>
                    <flux:heading class="!text-[#3D4A36]">Welcome back, {{ auth()->user()->name }}!</flux:heading>
                    <flux:text class="mt-1 !text-[#5A6350]">
                        You're logged in to TribeTrip. Browse resources to make a reservation.
                    </flux:text>
                </div>
            </div>
        </flux:card>

        {{-- Quick Actions --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <flux:card class="!bg-white hover:shadow-md transition-shadow">
                <a href="{{ route('member.resources') }}" class="block">
                    <div class="flex items-center gap-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#4A5240]">
                            <flux:icon name="squares-2x2" class="h-5 w-5 text-white" />
                        </div>
                        <div>
                            <flux:heading class="!text-[#3D4A36]">Browse Resources</flux:heading>
                            <flux:text size="sm" class="!text-[#7A8B6E]">Find and reserve resources</flux:text>
                        </div>
                    </div>
                </a>
            </flux:card>

            <flux:card class="!bg-white hover:shadow-md transition-shadow">
                <a href="{{ route('member.reservations') }}" class="block">
                    <div class="flex items-center gap-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#8B5A3C]">
                            <flux:icon name="calendar-days" class="h-5 w-5 text-white" />
                        </div>
                        <div>
                            <flux:heading class="!text-[#3D4A36]">My Reservations</flux:heading>
                            <flux:text size="sm" class="!text-[#7A8B6E]">View your bookings</flux:text>
                        </div>
                    </div>
                </a>
            </flux:card>

            <flux:card class="!bg-white hover:shadow-md transition-shadow">
                <a href="{{ route('member.profile') }}" class="block">
                    <div class="flex items-center gap-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#7A8B6E]">
                            <flux:icon name="user" class="h-5 w-5 text-white" />
                        </div>
                        <div>
                            <flux:heading class="!text-[#3D4A36]">My Profile</flux:heading>
                            <flux:text size="sm" class="!text-[#7A8B6E]">Update your information</flux:text>
                        </div>
                    </div>
                </a>
            </flux:card>
        </div>

        @if(auth()->user()->isAdmin())
            {{-- Admin Quick Access --}}
            <flux:card class="!bg-[#8B5A3C]/5 !border-[#8B5A3C]/20">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <flux:icon name="shield-check" class="h-5 w-5 text-[#8B5A3C]" />
                        <flux:heading class="!text-[#8B5A3C]">Admin Access</flux:heading>
                    </div>
                    <flux:button href="{{ route('admin.members') }}" variant="ghost" size="sm" class="!text-[#8B5A3C]">
                        Go to Admin Panel
                        <flux:icon name="arrow-right" variant="micro" class="ml-1" />
                    </flux:button>
                </div>
            </flux:card>
        @endif
    </div>
</x-layouts.app>
