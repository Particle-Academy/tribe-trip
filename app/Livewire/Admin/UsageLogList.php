<?php

namespace App\Livewire\Admin;

use App\Enums\UsageLogStatus;
use App\Models\Resource;
use App\Models\UsageLog;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Admin component for viewing and verifying usage logs.
 *
 * Lists all usage logs with filtering and verification actions.
 */
#[Layout('components.layouts.admin')]
#[Title('Usage Logs - TribeTrip Admin')]
class UsageLogList extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $resource = '';

    // Verification modal
    public bool $showVerifyModal = false;

    public ?UsageLog $logToVerify = null;

    #[Validate('nullable|string|max:500')]
    public string $adminNotes = '';

    // Photo viewer modal
    public bool $showPhotoModal = false;

    public ?string $photoUrl = null;

    public string $photoType = '';

    /**
     * Reset pagination when filters change.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedResource(): void
    {
        $this->resetPage();
    }

    /**
     * Open photo viewer modal.
     */
    public function viewPhoto(int $logId, string $type): void
    {
        $log = UsageLog::findOrFail($logId);
        $this->photoUrl = $type === 'start' ? $log->start_photo_url : $log->end_photo_url;
        $this->photoType = $type === 'start' ? 'Start Reading' : 'End Reading';
        $this->showPhotoModal = true;
    }

    /**
     * Close photo modal.
     */
    public function closePhotoModal(): void
    {
        $this->showPhotoModal = false;
        $this->photoUrl = null;
    }

    /**
     * Open verification modal.
     */
    public function openVerifyModal(int $logId): void
    {
        $this->logToVerify = UsageLog::with(['user', 'resource', 'reservation'])->findOrFail($logId);
        $this->adminNotes = $this->logToVerify->admin_notes ?? '';
        $this->showVerifyModal = true;
    }

    /**
     * Close verification modal.
     */
    public function closeVerifyModal(): void
    {
        $this->showVerifyModal = false;
        $this->logToVerify = null;
        $this->adminNotes = '';
    }

    /**
     * Verify the usage log.
     */
    public function verify(): void
    {
        if (! $this->logToVerify) {
            return;
        }

        $this->logToVerify->verify(auth()->id(), $this->adminNotes ?: null);

        $this->closeVerifyModal();
        session()->flash('success', 'Usage log verified successfully.');
    }

    /**
     * Mark as disputed.
     */
    public function dispute(): void
    {
        if (! $this->logToVerify) {
            return;
        }

        if (empty($this->adminNotes)) {
            $this->addError('adminNotes', 'Please provide a reason for disputing this log.');

            return;
        }

        $this->logToVerify->dispute($this->adminNotes);

        $this->closeVerifyModal();
        session()->flash('success', 'Usage log marked as disputed.');
    }

    public function render()
    {
        $logs = UsageLog::query()
            ->with(['user', 'resource', 'reservation'])
            ->when($this->search, function ($query) {
                $query->whereHas('user', function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            })
            ->when($this->resource, function ($query) {
                $query->where('resource_id', $this->resource);
            })
            ->orderByDesc('checked_out_at')
            ->paginate(15);

        // Get counts for filter badges
        $counts = [
            'total' => UsageLog::count(),
            'in_progress' => UsageLog::inProgress()->count(),
            'pending' => UsageLog::pendingVerification()->count(),
            'verified' => UsageLog::verified()->count(),
            'disputed' => UsageLog::disputed()->count(),
        ];

        return view('livewire.admin.usage-log-list', [
            'logs' => $logs,
            'counts' => $counts,
            'statuses' => UsageLogStatus::cases(),
            'resources' => Resource::orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
