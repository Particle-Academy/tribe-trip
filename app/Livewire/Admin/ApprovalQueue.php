<?php

namespace App\Livewire\Admin;

use App\Enums\UserStatus;
use App\Models\User;
use App\Notifications\RegistrationApproved;
use App\Notifications\RegistrationRejected;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Admin approval queue for pending registrations.
 *
 * Lists users with pending status and allows admins to approve or reject them.
 */
#[Layout('components.layouts.admin')]
#[Title('Approval Queue - TribeTrip Admin')]
class ApprovalQueue extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $selectedUserId = null;

    public string $rejectionReason = '';

    public bool $showRejectModal = false;

    /**
     * Approve a pending user registration.
     *
     * Sends approval notification email to the user.
     */
    public function approve(int $userId): void
    {
        $user = User::findOrFail($userId);

        if ($user->isPending()) {
            $user->approve();

            // Send approval notification to user
            $user->notify(new RegistrationApproved);

            session()->flash('success', "User {$user->name} has been approved and notified.");
        }
    }

    /**
     * Open the rejection modal for a user.
     */
    public function openRejectModal(int $userId): void
    {
        $this->selectedUserId = $userId;
        $this->rejectionReason = '';
        $this->showRejectModal = true;
    }

    /**
     * Close the rejection modal.
     */
    public function closeRejectModal(): void
    {
        $this->showRejectModal = false;
        $this->selectedUserId = null;
        $this->rejectionReason = '';
    }

    /**
     * Reject the selected user registration.
     *
     * Sends rejection notification email to the user.
     */
    public function reject(): void
    {
        if (! $this->selectedUserId) {
            return;
        }

        $user = User::findOrFail($this->selectedUserId);

        if ($user->isPending()) {
            $reason = $this->rejectionReason ?: null;
            $user->reject($reason);

            // Send rejection notification to user
            $user->notify(new RegistrationRejected($reason));

            session()->flash('success', "User {$user->name} has been rejected and notified.");
        }

        $this->closeRejectModal();
    }

    /**
     * Reset pagination when search changes.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $pendingUsers = User::query()
            ->pending()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->orderBy('created_at', 'asc')
            ->paginate(10);

        return view('livewire.admin.approval-queue', [
            'pendingUsers' => $pendingUsers,
        ]);
    }
}
