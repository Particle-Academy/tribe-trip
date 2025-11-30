{{-- Admin resource create/edit form --}}
<div class="space-y-6">
    {{-- Page header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="{{ route('admin.resources') }}">Resources</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $isEdit ? 'Edit' : 'Create' }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <flux:heading size="xl" class="mt-2">
                {{ $isEdit ? "Edit {$resource->name}" : 'Create Resource' }}
            </flux:heading>
        </div>
        <flux:button href="{{ route('admin.resources') }}" variant="ghost" icon="arrow-left">
            Back to Resources
        </flux:button>
    </div>

    <form wire:submit="save">
        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Main form --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Basic info card --}}
                <flux:card>
                    <flux:heading size="lg" class="mb-4">Basic Information</flux:heading>

                    <div class="space-y-4">
                        <flux:input
                            wire:model="name"
                            label="Resource Name"
                            placeholder="e.g., Community Van"
                            required
                        />

                        <flux:textarea
                            wire:model="description"
                            label="Description"
                            placeholder="Describe the resource, its features, and any usage notes..."
                            rows="4"
                        />

                        <div class="grid gap-4 sm:grid-cols-2">
                            <flux:select wire:model="type" label="Type" required>
                                @foreach ($types as $t)
                                    <option value="{{ $t->value }}">{{ $t->label() }}</option>
                                @endforeach
                            </flux:select>

                            <flux:select wire:model="status" label="Status" required>
                                @foreach ($statuses as $s)
                                    <option value="{{ $s->value }}">{{ $s->label() }}</option>
                                @endforeach
                            </flux:select>
                        </div>
                    </div>
                </flux:card>

                {{-- Pricing card --}}
                <flux:card>
                    <flux:heading size="lg" class="mb-4">Pricing Configuration</flux:heading>

                    <div class="space-y-4">
                        <flux:radio.group wire:model.live="pricing_model" label="Pricing Model">
                            @foreach ($pricingModels as $model)
                                <flux:radio value="{{ $model->value }}" label="{{ $model->label() }}" description="{{ $model->description() }}" />
                            @endforeach
                        </flux:radio.group>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <flux:input
                                wire:model="rate"
                                type="number"
                                step="0.01"
                                min="0"
                                label="Rate ($)"
                                placeholder="0.00"
                                required
                            />

                            @if ($pricing_model === 'per_unit')
                                <flux:select wire:model="pricing_unit" label="Per Unit" required>
                                    <option value="">Select unit...</option>
                                    @foreach ($pricingUnits as $unit)
                                        <option value="{{ $unit->value }}">{{ $unit->label() }}</option>
                                    @endforeach
                                </flux:select>
                            @endif
                        </div>

                        {{-- Price preview --}}
                        <flux:callout variant="info" icon="banknotes">
                            <flux:callout.heading>Price Preview</flux:callout.heading>
                            <flux:callout.text>
                                @if ($pricing_model === 'flat_fee')
                                    ${{ number_format((float) $rate, 2) }} per reservation
                                @elseif ($pricing_unit)
                                    ${{ number_format((float) $rate, 2) }} per {{ \App\Enums\PricingUnit::tryFrom($pricing_unit)?->label() ?? 'unit' }}
                                @else
                                    ${{ number_format((float) $rate, 2) }} per unit (select unit type)
                                @endif
                            </flux:callout.text>
                        </flux:callout>
                    </div>
                </flux:card>

                {{-- Images card --}}
                <flux:card>
                    <flux:heading size="lg" class="mb-4">Images</flux:heading>

                    {{-- Existing images --}}
                    @if (count($existingImages) > 0)
                        <div class="mb-4">
                            <flux:text size="sm" class="text-zinc-500 mb-2">Current Images</flux:text>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                @foreach ($existingImages as $img)
                                    <div class="relative group" wire:key="existing-{{ $img['id'] }}">
                                        <img
                                            src="{{ $img['url'] }}"
                                            alt="{{ $img['filename'] }}"
                                            class="aspect-square w-full rounded-lg object-cover ring-2 {{ $img['is_primary'] ? 'ring-blue-500' : 'ring-zinc-200 dark:ring-zinc-700' }}"
                                        />
                                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg flex items-center justify-center gap-2">
                                            @if (!$img['is_primary'])
                                                <flux:button wire:click="setPrimaryImage({{ $img['id'] }})" variant="ghost" size="sm" class="!bg-white/20 !text-white">
                                                    Primary
                                                </flux:button>
                                            @endif
                                            <flux:button wire:click="markImageForDeletion({{ $img['id'] }})" variant="ghost" size="sm" class="!bg-red-500/80 !text-white">
                                                <flux:icon name="trash" class="w-4 h-4" />
                                            </flux:button>
                                        </div>
                                        @if ($img['is_primary'])
                                            <flux:badge class="absolute top-1 left-1" size="sm" color="blue">Primary</flux:badge>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Upload new images --}}
                    <div class="space-y-4">
                        <flux:input
                            wire:model="images"
                            type="file"
                            accept="image/*"
                            multiple
                            label="Upload Images"
                            description="JPG, PNG, GIF up to 2MB each. You can select multiple images."
                        />

                        @error('images.*')
                            <flux:text class="text-red-500 text-sm">{{ $message }}</flux:text>
                        @enderror

                        {{-- Preview new uploads --}}
                        @if (count($images) > 0)
                            <div>
                                <flux:text size="sm" class="text-zinc-500 mb-2">New Images to Upload</flux:text>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                    @foreach ($images as $index => $image)
                                        <div class="relative group" wire:key="new-{{ $index }}">
                                            <img
                                                src="{{ $image->temporaryUrl() }}"
                                                alt="Preview"
                                                class="aspect-square w-full rounded-lg object-cover ring-2 ring-zinc-200 dark:ring-zinc-700"
                                            />
                                            <button
                                                type="button"
                                                wire:click="removeImage({{ $index }})"
                                                class="absolute top-1 right-1 p-1 bg-red-500 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity"
                                            >
                                                <flux:icon name="x-mark" class="w-4 h-4" />
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </flux:card>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- Availability settings --}}
                <flux:card>
                    <flux:heading size="lg" class="mb-4">Availability Settings</flux:heading>

                    <div class="space-y-4">
                        <flux:switch
                            wire:model="requires_approval"
                            label="Require Approval"
                            description="Reservations must be approved by an admin"
                        />

                        {{-- Multi-day booking setting: 0 = single day only, number = max days, empty = unlimited --}}
                        <flux:input
                            wire:model="max_reservation_days"
                            type="number"
                            min="0"
                            max="365"
                            label="Max Booking Duration"
                            placeholder="Unlimited"
                            description="0 = single day only, 1-365 = max days allowed, empty = no limit"
                        />

                        <flux:input
                            wire:model="advance_booking_days"
                            type="number"
                            min="1"
                            max="365"
                            label="Advance Booking Days"
                            placeholder="No limit"
                            description="How far ahead members can book"
                        />
                    </div>
                </flux:card>

                {{-- Save actions --}}
                <flux:card>
                    <div class="space-y-4">
                        <flux:button type="submit" variant="primary" class="w-full">
                            {{ $isEdit ? 'Update Resource' : 'Create Resource' }}
                        </flux:button>

                        <flux:button href="{{ route('admin.resources') }}" variant="ghost" class="w-full">
                            Cancel
                        </flux:button>
                    </div>

                    @if ($isEdit)
                        <flux:separator class="my-4" />
                        <flux:text size="sm" class="text-zinc-500">
                            Created {{ $resource->created_at->format('M j, Y') }}
                            @if ($resource->creator)
                                by {{ $resource->creator->name }}
                            @endif
                        </flux:text>
                    @endif
                </flux:card>
            </div>
        </div>
    </form>
</div>
