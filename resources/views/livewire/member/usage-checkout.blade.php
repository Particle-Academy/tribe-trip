{{-- Member check-out interface --}}
<div class="space-y-6 max-w-2xl mx-auto">
    {{-- Page header --}}
    <div>
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="{{ route('member.reservations') }}">My Reservations</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Check Out</flux:breadcrumbs.item>
        </flux:breadcrumbs>
        <flux:heading size="xl" class="mt-2">Check Out</flux:heading>
        <flux:text class="mt-1 text-zinc-500">Start using {{ $reservation->resource->name }}</flux:text>
    </div>

    {{-- Flash messages --}}
    @if (session('error'))
        <flux:callout variant="danger" icon="exclamation-circle" dismissible>
            {{ session('error') }}
        </flux:callout>
    @endif

    @if (!$this->canCheckOut())
        <flux:callout variant="warning" icon="exclamation-triangle">
            <flux:callout.heading>Cannot Check Out</flux:callout.heading>
            <flux:callout.text>
                This reservation is not ready for check-out. Please ensure it is confirmed and within the reservation window.
            </flux:callout.text>
        </flux:callout>
    @else
        {{-- Resource summary --}}
        <flux:card>
            <div class="flex items-center gap-4">
                @if ($reservation->resource->primaryImageUrl)
                    <img
                        src="{{ $reservation->resource->primaryImageUrl }}"
                        alt="{{ $reservation->resource->name }}"
                        class="w-20 h-20 rounded-lg object-cover"
                    />
                @else
                    <div class="w-20 h-20 bg-zinc-100 dark:bg-zinc-800 rounded-lg flex items-center justify-center">
                        <flux:icon name="{{ $reservation->resource->type->icon() }}" class="w-8 h-8 text-zinc-400" />
                    </div>
                @endif
                <div>
                    <flux:heading size="lg">{{ $reservation->resource->name }}</flux:heading>
                    <flux:text size="sm" class="text-zinc-500">
                        {{ $reservation->starts_at->format('l, F j, Y') }} &bull;
                        {{ $reservation->starts_at->format('g:i A') }} - {{ $reservation->ends_at->format('g:i A') }}
                    </flux:text>
                    <flux:badge size="sm" :color="$reservation->resource->type->color()">
                        {{ $reservation->resource->type->label() }}
                    </flux:badge>
                </div>
            </div>
        </flux:card>

        {{-- Check-out form --}}
        <flux:card>
            <flux:heading size="lg" class="mb-4">Record Starting Condition</flux:heading>

            <form wire:submit="checkout" class="space-y-6">
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
                                    Take a photo of the odometer or meter showing the current reading.
                                </flux:callout.text>
                            </flux:callout>

                            <flux:input
                                wire:model="startPhoto"
                                type="file"
                                accept="image/*"
                                capture="environment"
                                description="Take a photo or select from gallery"
                            />

                            @if ($startPhoto)
                                <div class="mt-2">
                                    <img
                                        src="{{ $startPhoto->temporaryUrl() }}"
                                        alt="Preview"
                                        class="max-h-48 rounded-lg border border-zinc-200 dark:border-zinc-700"
                                    />
                                </div>
                            @endif

                            @error('startPhoto')
                                <flux:text class="text-red-500 text-sm">{{ $message }}</flux:text>
                            @enderror
                        </div>
                    @endif

                    {{-- Manual entry (always visible when toggle is on, or as alternative) --}}
                    @if ($useManualEntry || true)
                        <div class="mt-4 {{ !$useManualEntry ? 'pt-4 border-t border-zinc-200 dark:border-zinc-700' : '' }}">
                            <flux:input
                                wire:model="startReading"
                                type="number"
                                step="0.1"
                                min="0"
                                label="{{ $useManualEntry ? 'Current Reading' : 'Reading (optional)' }}"
                                placeholder="e.g., 45123.5"
                                description="Enter the current odometer or meter reading"
                            />
                        </div>
                    @endif
                </div>

                {{-- Notes --}}
                <flux:textarea
                    wire:model="notes"
                    label="Notes (optional)"
                    placeholder="Any notes about the starting condition..."
                    rows="3"
                />

                {{-- Important reminders --}}
                <flux:callout variant="warning" icon="exclamation-triangle">
                    <flux:callout.heading>Before You Go</flux:callout.heading>
                    <flux:callout.text>
                        <ul class="list-disc list-inside space-y-1">
                            <li>Inspect the {{ strtolower($reservation->resource->type->label()) }} for any existing damage</li>
                            <li>Note the fuel level or battery charge</li>
                            <li>Ensure you have any required keys or equipment</li>
                        </ul>
                    </flux:callout.text>
                </flux:callout>

                {{-- Submit --}}
                <div class="flex gap-3">
                    <flux:button type="submit" variant="primary" class="flex-1">
                        <flux:icon name="check" class="w-5 h-5 mr-2" />
                        Confirm Check Out
                    </flux:button>
                    <flux:button href="{{ route('member.reservations') }}" variant="ghost">
                        Cancel
                    </flux:button>
                </div>
            </form>
        </flux:card>
    @endif
</div>
