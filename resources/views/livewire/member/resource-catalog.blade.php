{{-- Member resource catalog browse view --}}
<div class="space-y-6">
    {{-- Page header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl" class="!text-[#3D4A36]">Resources</flux:heading>
            <flux:text class="mt-1 !text-[#5A6350]">Browse available community resources</flux:text>
        </div>
        <flux:button href="{{ route('member.reservations') }}" variant="ghost" icon="calendar-days" class="!text-[#4A5240]">
            My Reservations
        </flux:button>
    </div>

    {{-- Flash messages --}}
    @if (session('success'))
        <flux:callout variant="success" icon="check-circle" dismissible>
            {{ session('success') }}
        </flux:callout>
    @endif

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
            <div class="flex gap-2 flex-wrap">
                <flux:button
                    wire:click="$set('type', '')"
                    :variant="$type === '' ? 'primary' : 'ghost'"
                    size="sm"
                >
                    All
                </flux:button>
                @foreach ($types as $t)
                    <flux:button
                        wire:click="$set('type', '{{ $t->value }}')"
                        :variant="$type === $t->value ? 'primary' : 'ghost'"
                        size="sm"
                    >
                        {{ $t->label() }}
                        @if (isset($typeCounts[$t->value]))
                            <flux:badge size="sm" class="ml-1">{{ $typeCounts[$t->value] }}</flux:badge>
                        @endif
                    </flux:button>
                @endforeach
            </div>
        </div>
    </flux:card>

    {{-- Resource grid --}}
    @if ($resources->count() > 0)
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($resources as $resource)
                <flux:card wire:key="resource-{{ $resource->id }}" class="flex flex-col group hover:shadow-lg transition-shadow">
                    {{-- Image --}}
                    <a href="{{ route('member.resources.show', $resource) }}" class="block">
                        <div class="aspect-video bg-[#E8E2D6] dark:bg-zinc-800 rounded-lg mb-4 overflow-hidden relative">
                            @if ($resource->primaryImageUrl)
                                <img
                                    src="{{ $resource->primaryImageUrl }}"
                                    alt="{{ $resource->name }}"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                />
                            @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <flux:icon name="{{ $resource->type->icon() }}" class="w-12 h-12 text-[#7A8B6E]" />
                                </div>
                            @endif
                            {{-- Type badge --}}
                            <div class="absolute top-2 left-2">
                                <flux:badge size="sm" :color="$resource->type->color()">
                                    {{ $resource->type->label() }}
                                </flux:badge>
                            </div>
                        </div>
                    </a>

                    {{-- Info --}}
                    <div class="flex-1">
                        <a href="{{ route('member.resources.show', $resource) }}" class="hover:underline">
                            <flux:heading size="lg" class="!text-[#3D4A36]">{{ $resource->name }}</flux:heading>
                        </a>

                        @if ($resource->description)
                            <flux:text size="sm" class="!text-[#5A6350] line-clamp-2 mt-2 mb-3">
                                {{ $resource->description }}
                            </flux:text>
                        @endif

                        <div class="flex items-center justify-between mt-auto pt-3">
                            <flux:text class="font-semibold text-lg !text-[#4A5240] dark:!text-[#7A8B6E]">
                                {{ $resource->formatted_price }}
                            </flux:text>
                            @if ($resource->requires_approval)
                                <flux:tooltip content="Requires admin approval">
                                    <flux:badge size="sm" color="amber">
                                        <flux:icon name="shield-check" class="w-3 h-3 mr-1" />
                                        Approval
                                    </flux:badge>
                                </flux:tooltip>
                            @endif
                        </div>
                    </div>

                    {{-- Action --}}
                    <div class="pt-4 mt-4 border-t border-[#D4C9B8] dark:border-zinc-700">
                        <flux:button href="{{ route('member.resources.show', $resource) }}" variant="filled" class="w-full !bg-[#4A5240] hover:!bg-[#3D4A36]">
                            View & Book
                        </flux:button>
                    </div>
                </flux:card>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $resources->links() }}
        </div>
    @else
        <flux:card class="text-center py-12 !bg-white">
            <flux:icon name="cube" class="w-12 h-12 text-[#7A8B6E] mx-auto mb-4" />
            <flux:heading size="lg" class="mb-2 !text-[#3D4A36]">No resources found</flux:heading>
            <flux:text class="!text-[#5A6350]">
                @if ($search || $type)
                    Try adjusting your search or filter criteria.
                @else
                    No resources are currently available. Check back later!
                @endif
            </flux:text>
        </flux:card>
    @endif
</div>
