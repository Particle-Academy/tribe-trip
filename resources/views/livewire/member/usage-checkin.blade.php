{{-- Member check-in interface --}}
<div class="space-y-6 max-w-2xl mx-auto">
    {{-- Page header --}}
    <div>
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="{{ route('member.reservations') }}">My Reservations</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Check In</flux:breadcrumbs.item>
        </flux:breadcrumbs>
        <flux:heading size="xl" class="mt-2">Check In</flux:heading>
        <flux:text class="mt-1 text-zinc-500">Complete your use of {{ $usageLog->resource->name }}</flux:text>
    </div>

    {{-- Flash messages --}}
    @if (session('error'))
        <flux:callout variant="danger" icon="exclamation-circle" dismissible>
            {{ session('error') }}
        </flux:callout>
    @endif

    {{-- Resource and usage summary --}}
    <flux:card>
        <div class="flex items-center gap-4 mb-4">
            @if ($usageLog->resource->primaryImageUrl)
                <img
                    src="{{ $usageLog->resource->primaryImageUrl }}"
                    alt="{{ $usageLog->resource->name }}"
                    class="w-20 h-20 rounded-lg object-cover"
                />
            @else
                <div class="w-20 h-20 bg-zinc-100 dark:bg-zinc-800 rounded-lg flex items-center justify-center">
                    <flux:icon name="{{ $usageLog->resource->type->icon() }}" class="w-8 h-8 text-zinc-400" />
                </div>
            @endif
            <div>
                <flux:heading size="lg">{{ $usageLog->resource->name }}</flux:heading>
                <flux:text size="sm" class="text-zinc-500">
                    Checked out: {{ $usageLog->checked_out_at->format('g:i A') }}
                    ({{ $usageLog->checked_out_at->diffForHumans() }})
                </flux:text>
            </div>
        </div>

        {{-- Starting info --}}
        <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4">
            <flux:text size="sm" class="text-zinc-500 mb-2">Starting Information</flux:text>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <flux:text class="text-zinc-500">Start Reading</flux:text>
                    <flux:text class="font-medium">
                        {{ $usageLog->start_reading !== null ? number_format($usageLog->start_reading, 1) : '—' }}
                    </flux:text>
                </div>
                <div>
                    <flux:text class="text-zinc-500">Time</flux:text>
                    <flux:text class="font-medium">{{ $usageLog->checked_out_at->format('g:i A') }}</flux:text>
                </div>
            </div>
            @if ($usageLog->start_notes)
                <div class="mt-2">
                    <flux:text size="sm" class="text-zinc-500">Notes:</flux:text>
                    <flux:text size="sm">{{ $usageLog->start_notes }}</flux:text>
                </div>
            @endif
        </div>
    </flux:card>

    {{-- Check-in form --}}
    <flux:card>
        <flux:heading size="lg" class="mb-4">Record Ending Condition</flux:heading>

        <form wire:submit="checkin" class="space-y-6">
            {{-- Photo capture section --}}
            <div>
                <div class="flex items-center justify-between mb-2">
                    <flux:text class="font-medium">Meter/Odometer Photo</flux:text>
                    <flux:button wire:click="toggleManualEntry" variant="ghost" size="sm" type="button">
                        {{ $useManualEntry ? 'Use Camera' : 'Enter Manually' }}
                    </flux:button>
                </div>

                @if (!$useManualEntry)
                    <div class="space-y-4">
                        <flux:callout variant="info" icon="camera">
                            <flux:callout.text>
                                Take a photo of the odometer or meter showing the final reading.
                            </flux:callout.text>
                        </flux:callout>

                        <flux:input
                            wire:model="endPhoto"
                            type="file"
                            accept="image/*"
                            capture="environment"
                            description="Take a photo or select from gallery"
                        />

                        @if ($endPhoto)
                            <div class="mt-2">
                                <img
                                    src="{{ $endPhoto->temporaryUrl() }}"
                                    alt="Preview"
                                    class="max-h-48 rounded-lg border border-zinc-200 dark:border-zinc-700"
                                />
                            </div>
                        @endif

                        @error('endPhoto')
                            <flux:text class="text-red-500 text-sm">{{ $message }}</flux:text>
                        @enderror
                    </div>
                @endif

                {{-- Manual entry --}}
                @if ($useManualEntry || true)
                    <div class="mt-4 {{ !$useManualEntry ? 'pt-4 border-t border-zinc-200 dark:border-zinc-700' : '' }}">
                        <flux:input
                            wire:model="endReading"
                            type="number"
                            step="0.1"
                            min="0"
                            label="{{ $useManualEntry ? 'Current Reading' : 'Reading (optional)' }}"
                            placeholder="e.g., 45180.2"
                            description="Enter the final odometer or meter reading"
                        />

                        @if ($usageLog->start_reading !== null && $endReading)
                            @php
                                $usage = max(0, (float)$endReading - $usageLog->start_reading);
                            @endphp
                            <flux:text size="sm" class="text-zinc-500 mt-2">
                                Usage: {{ number_format($usage, 1) }} {{ $usageLog->resource->pricing_unit?->pluralLabel() ?? 'units' }}
                            </flux:text>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Notes --}}
            <flux:textarea
                wire:model="notes"
                label="Notes (optional)"
                placeholder="Any notes about the ending condition, issues, etc..."
                rows="3"
            />

            {{-- Checklist --}}
            <flux:callout variant="warning" icon="clipboard-document-check">
                <flux:callout.heading>Before Completing Check-in</flux:callout.heading>
                <flux:callout.text>
                    <ul class="list-disc list-inside space-y-1">
                        <li>Return all keys and equipment</li>
                        <li>Remove any personal belongings</li>
                        <li>Report any new damage or issues</li>
                        <li>Return to the designated parking location</li>
                    </ul>
                </flux:callout.text>
            </flux:callout>

            {{-- Submit --}}
            <div class="flex gap-3">
                <flux:button type="submit" variant="primary" class="flex-1">
                    <flux:icon name="check" class="w-5 h-5 mr-2" />
                    Complete Check In
                </flux:button>
                <flux:button href="{{ route('member.reservations') }}" variant="ghost">
                    Cancel
                </flux:button>
            </div>
        </form>
    </flux:card>
</div>
