<?php

namespace App\Livewire\Admin;

use App\Enums\ResourceStatus;
use App\Enums\ResourceType;
use App\Models\Resource;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Admin component for viewing and managing community resources.
 *
 * Lists all resources with search, filtering, and quick actions.
 */
#[Layout('components.layouts.admin')]
#[Title('Resources - TribeTrip Admin')]
class ResourceList extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $type = '';

    #[Url]
    public string $status = '';

    // Delete confirmation modal
    public bool $showDeleteModal = false;

    public ?Resource $resourceToDelete = null;

    /**
     * Quick activate a resource.
     */
    public function activate(int $resourceId): void
    {
        $resource = Resource::findOrFail($resourceId);
        $resource->activate();
        session()->flash('success', "{$resource->name} has been activated.");
    }

    /**
     * Quick deactivate a resource.
     */
    public function deactivate(int $resourceId): void
    {
        $resource = Resource::findOrFail($resourceId);
        $resource->deactivate();
        session()->flash('success', "{$resource->name} has been deactivated.");
    }

    /**
     * Quick mark as maintenance.
     */
    public function markMaintenance(int $resourceId): void
    {
        $resource = Resource::findOrFail($resourceId);
        $resource->markMaintenance();
        session()->flash('success', "{$resource->name} has been marked for maintenance.");
    }

    /**
     * Open delete confirmation modal.
     */
    public function confirmDelete(int $resourceId): void
    {
        $this->resourceToDelete = Resource::findOrFail($resourceId);
        $this->showDeleteModal = true;
    }

    /**
     * Close delete modal.
     */
    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->resourceToDelete = null;
    }

    /**
     * Delete the resource.
     */
    public function deleteResource(): void
    {
        if ($this->resourceToDelete) {
            $name = $this->resourceToDelete->name;

            // Delete associated images
            foreach ($this->resourceToDelete->images as $image) {
                $image->deleteWithFile();
            }

            $this->resourceToDelete->delete();
            session()->flash('success', "{$name} has been deleted.");
        }

        $this->closeDeleteModal();
    }

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

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $resources = Resource::query()
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
            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            })
            ->orderByDesc('created_at')
            ->paginate(12);

        // Get counts for filter badges
        $counts = [
            'total' => Resource::count(),
            'active' => Resource::active()->count(),
            'inactive' => Resource::where('status', ResourceStatus::Inactive)->count(),
            'maintenance' => Resource::where('status', ResourceStatus::Maintenance)->count(),
            'vehicles' => Resource::vehicles()->count(),
            'equipment' => Resource::equipment()->count(),
        ];

        return view('livewire.admin.resource-list', [
            'resources' => $resources,
            'counts' => $counts,
            'types' => ResourceType::cases(),
            'statuses' => ResourceStatus::cases(),
        ]);
    }
}
