<?php

namespace App\Livewire\Member;

use App\Enums\ResourceType;
use App\Models\Resource;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Member resource catalog component.
 *
 * Displays available community resources for browsing and booking.
 */
#[Layout('components.layouts.app')]
#[Title('Resources - TribeTrip')]
class ResourceCatalog extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $type = '';

    /**
     * Reset pagination when filters change.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $resources = Resource::query()
            ->active()
            ->with(['images' => fn ($q) => $q->where('is_primary', true)->limit(1)])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%");
                });
            })
            ->when($this->type, function ($query) {
                $query->where('type', $this->type);
            })
            ->orderBy('name')
            ->paginate(12);

        // Get counts by type for filter badges
        $typeCounts = Resource::query()
            ->active()
            ->selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        return view('livewire.member.resource-catalog', [
            'resources' => $resources,
            'types' => ResourceType::cases(),
            'typeCounts' => $typeCounts,
        ]);
    }
}
