{{-- Admin resource list view --}}
<div class="space-y-6">
    {{-- Page header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">Resources</flux:heading>
            <flux:text class="mt-1 text-zinc-500">Manage community vehicles, equipment, and spaces</flux:text>
        </div>
        <flux:button href="{{ route('admin.resources.create') }}" variant="primary" icon="plus">
            Add Resource
        </flux:button>
    </div>

    {{-- Flash messages --}}
    @if (session('success'))
        <flux:callout variant="success" icon="check-circle" dismissible>
            {{ session('success') }}
        </flux:callout>
    @endif

    {{-- Stats cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <flux:card class="!p-4">
            <flux:text size="sm" class="text-zinc-500">Total</flux:text>
            <flux:heading size="lg">{{ $counts['total'] }}</flux:heading>
        </flux:card>
        <flux:card class="!p-4">
            <flux:text size="sm" class="text-zinc-500">Active</flux:text>
            <flux:heading size="lg" class="text-green-600">{{ $counts['active'] }}</flux:heading>
        </flux:card>
        <flux:card class="!p-4">
            <flux:text size="sm" class="text-zinc-500">Inactive</flux:text>
            <flux:heading size="lg" class="text-zinc-500">{{ $counts['inactive'] }}</flux:heading>
        </flux:card>
        <flux:card class="!p-4">
            <flux:text size="sm" class="text-zinc-500">Maintenance</flux:text>
            <flux:heading size="lg" class="text-amber-600">{{ $counts['maintenance'] }}</flux:heading>
        </flux:card>
        <flux:card class="!p-4">
            <flux:text size="sm" class="text-zinc-500">Vehicles</flux:text>
            <flux:heading size="lg" class="text-blue-600">{{ $counts['vehicles'] }}</flux:heading>
        </flux:card>
        <flux:card class="!p-4">
            <flux:text size="sm" class="text-zinc-500">Equipment</flux:text>
            <flux:heading size="lg" class="text-amber-600">{{ $counts['equipment'] }}</flux:heading>
        </flux:card>
    </div>

    {{-- Filters --}}
    <flux:card>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
            <div class="flex-1">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    icon="magnifying-glass"
                    placeholder="Search resources..."
                />
            </div>
            <div class="flex gap-4">
                <flux:select wire:model.live="type" class="w-40">
                    <option value="">All Types</option>
                    @foreach ($types as $t)
                        <option value="{{ $t->value }}">{{ $t->label() }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="status" class="w-40">
                    <option value="">All Statuses</option>
                    @foreach ($statuses as $s)
                        <option value="{{ $s->value }}">{{ $s->label() }}</option>
                    @endforeach
                </flux:select>
            </div>
        </div>
    </flux:card>

    {{-- Resource grid --}}
    @if ($resources->count() > 0)
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($resources as $resource)
                <flux:card wire:key="resource-{{ $resource->id }}" class="flex flex-col">
                    {{-- Image --}}
                    <div class="aspect-video bg-zinc-100 dark:bg-zinc-800 rounded-lg mb-4 overflow-hidden relative">
                        @if ($resource->primaryImageUrl)
                            <img
                                src="{{ $resource->primaryImageUrl }}"
                                alt="{{ $resource->name }}"
                                class="w-full h-full object-cover"
                            />
                        @else
                            <div class="w-full h-full flex items-center justify-center">
                                <flux:icon name="{{ $resource->type->icon() }}" class="w-12 h-12 text-zinc-400" />
                            </div>
                        @endif
                        {{-- Status badge --}}
                        <div class="absolute top-2 right-2">
                            <flux:badge size="sm" :color="$resource->status->color()">
                                {{ $resource->status->label() }}
                            </flux:badge>
                        </div>
                    </div>

                    {{-- Info --}}
                    <div class="flex-1">
                        <div class="flex items-start justify-between mb-2">
                            <flux:heading size="lg">{{ $resource->name }}</flux:heading>
                            <flux:badge size="sm" :color="$resource->type->color()">
                                {{ $resource->type->label() }}
                            </flux:badge>
                        </div>

                        @if ($resource->description)
                            <flux:text size="sm" class="text-zinc-500 line-clamp-2 mb-3">
                                {{ $resource->description }}
                            </flux:text>
                        @endif

                        <div class="flex items-center justify-between mb-4">
                            <flux:text class="font-semibold text-lg">{{ $resource->formatted_price }}</flux:text>
                            @if ($resource->requires_approval)
                                <flux:tooltip content="Requires approval">
                                    <flux:icon name="shield-check" class="w-5 h-5 text-amber-500" />
                                </flux:tooltip>
                            @endif
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex gap-2 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                        <flux:button href="{{ route('admin.resources.edit', $resource) }}" variant="ghost" size="sm" icon="pencil" class="flex-1">
                            Edit
                        </flux:button>

                        <flux:dropdown align="end">
                            <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />

                            <flux:menu>
                                @if ($resource->isActive())
                                    <flux:menu.item wire:click="deactivate({{ $resource->id }})" icon="pause">
                                        Deactivate
                                    </flux:menu.item>
                                    <flux:menu.item wire:click="markMaintenance({{ $resource->id }})" icon="wrench">
                                        Mark Maintenance
                                    </flux:menu.item>
                                @else
                                    <flux:menu.item wire:click="activate({{ $resource->id }})" icon="play">
                                        Activate
                                    </flux:menu.item>
                                @endif

                                <flux:menu.separator />

                                <flux:menu.item wire:click="confirmDelete({{ $resource->id }})" variant="danger" icon="trash">
                                    Delete
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </div>
                </flux:card>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $resources->links() }}
        </div>
    @else
        <flux:card class="text-center py-12">
            <flux:icon name="cube" class="w-12 h-12 text-zinc-400 mx-auto mb-4" />
            <flux:heading size="lg" class="mb-2">No resources found</flux:heading>
            <flux:text class="text-zinc-500 mb-4">
                @if ($search || $type || $status)
                    Try adjusting your search or filter criteria.
                @else
                    Get started by adding your first community resource.
                @endif
            </flux:text>
            @if (!$search && !$type && !$status)
                <flux:button href="{{ route('admin.resources.create') }}" variant="primary" icon="plus">
                    Add Resource
                </flux:button>
            @endif
        </flux:card>
    @endif

    {{-- Delete confirmation modal --}}
    <flux:modal wire:model="showDeleteModal">
        <div class="space-y-4">
            <flux:heading size="lg">Delete Resource</flux:heading>

            @if ($resourceToDelete)
                <flux:text>
                    Are you sure you want to delete <strong>{{ $resourceToDelete->name }}</strong>?
                    This action cannot be undone and will also delete all associated images.
                </flux:text>
            @endif

            <div class="flex justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <flux:button wire:click="closeDeleteModal" variant="ghost">
                    Cancel
                </flux:button>
                <flux:button wire:click="deleteResource" variant="danger">
                    Delete Resource
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
