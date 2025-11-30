<?php

namespace App\Livewire\Member;

use App\Enums\UsageLogStatus;
use App\Models\Resource;
use App\Models\UsageLog;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Member usage history component.
 *
 * Shows members their complete usage history across all resources.
 */
#[Layout('components.layouts.app')]
#[Title('Usage History - TribeTrip')]
class UsageHistory extends Component
{
    use WithPagination;

    #[Url]
    public string $resource = '';

    #[Url]
    public string $status = '';

    // Detail modal
    public bool $showDetailModal = false;

    public ?UsageLog $selectedLog = null;

    /**
     * Reset pagination when filters change.
     */
    public function updatedResource(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    /**
     * View usage log details.
     */
    public function viewDetails(int $logId): void
    {
        $this->selectedLog = UsageLog::with(['resource', 'reservation'])
            ->where('user_id', auth()->id())
            ->findOrFail($logId);
        $this->showDetailModal = true;
    }

    /**
     * Close detail modal.
     */
    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->selectedLog = null;
    }

    public function render()
    {
        $logs = UsageLog::query()
            ->with(['resource', 'reservation'])
            ->forUser(auth()->id())
            ->when($this->resource, function ($query) {
                $query->forResource((int) $this->resource);
            })
            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            })
            ->orderByDesc('checked_out_at')
            ->paginate(15);

        // Get summary stats
        $stats = [
            'total_uses' => UsageLog::forUser(auth()->id())->count(),
            'total_cost' => UsageLog::forUser(auth()->id())->sum('calculated_cost'),
            'total_hours' => UsageLog::forUser(auth()->id())->sum('duration_hours'),
            'total_distance' => UsageLog::forUser(auth()->id())->sum('distance_units'),
        ];

        // Get resources the user has used (for filter dropdown)
        $usedResourceIds = UsageLog::forUser(auth()->id())->distinct()->pluck('resource_id');
        $resources = Resource::whereIn('id', $usedResourceIds)->orderBy('name')->pluck('name', 'id');

        return view('livewire.member.usage-history', [
            'logs' => $logs,
            'stats' => $stats,
            'resources' => $resources,
            'statuses' => UsageLogStatus::cases(),
        ]);
    }
}
