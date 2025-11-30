<?php

namespace App\Livewire\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Admin component for viewing and editing a member's details.
 *
 * Provides detailed view, editing, and status management.
 */
#[Layout('components.layouts.admin')]
class MemberDetail extends Component
{
    #[Locked]
    public User $user;

    // Editable fields
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|email')]
    public string $email = '';

    #[Validate('nullable|string|max:20')]
    public string $phone = '';

    // Status change modal
    public bool $showStatusModal = false;

    public string $statusAction = '';

    #[Validate('nullable|string|max:500')]
    public string $statusReason = '';

    // Role change modal
    public bool $showRoleModal = false;

    public string $roleAction = '';

    // Edit mode
    public bool $editing = false;

    /**
     * Mount the component with the member.
     */
    public function mount(User $user): void
    {
        $this->user = $user;
        $this->loadUserData();
    }

    /**
     * Load user data into form fields.
     */
    private function loadUserData(): void
    {
        $this->name = $this->user->name;
        $this->email = $this->user->email;
        $this->phone = $this->user->phone ?? '';
    }

    /**
     * Enable edit mode.
     */
    public function startEditing(): void
    {
        $this->editing = true;
    }

    /**
     * Cancel editing and reset fields.
     */
    public function cancelEditing(): void
    {
        $this->editing = false;
        $this->loadUserData();
        $this->resetValidation();
    }

    /**
     * Save edited user information.
     */
    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $this->user->id,
            'phone' => 'nullable|string|max:20',
        ]);

        $this->user->update([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone ?: null,
        ]);

        $this->editing = false;
        session()->flash('success', 'Member information updated successfully.');
    }

    /**
     * Open status change modal.
     */
    public function openStatusModal(string $action): void
    {
        $this->statusAction = $action;
        $this->statusReason = '';
        $this->showStatusModal = true;
    }

    /**
     * Close status modal.
     */
    public function closeStatusModal(): void
    {
        $this->showStatusModal = false;
        $this->statusAction = '';
        $this->statusReason = '';
    }

    /**
     * Confirm status change.
     */
    public function confirmStatusChange(): void
    {
        // Prevent self-modification
        if ($this->user->id === auth()->id()) {
            session()->flash('error', 'You cannot modify your own account status.');
            $this->closeStatusModal();

            return;
        }

        match ($this->statusAction) {
            'suspend' => $this->user->suspend($this->statusReason ?: null),
            'reactivate' => $this->user->reactivate($this->statusReason ?: null),
            'approve' => $this->user->approve($this->statusReason ?: null),
            'reject' => $this->user->reject($this->statusReason ?: null),
            default => null,
        };

        $this->user->refresh();
        $this->closeStatusModal();

        $actionLabel = ucfirst($this->statusAction) . 'd';
        session()->flash('success', "{$this->user->name} has been {$actionLabel}.");
    }

    /**
     * Open role change modal.
     */
    public function openRoleModal(string $action): void
    {
        $this->roleAction = $action;
        $this->showRoleModal = true;
    }

    /**
     * Close role modal.
     */
    public function closeRoleModal(): void
    {
        $this->showRoleModal = false;
        $this->roleAction = '';
    }

    /**
     * Confirm role change.
     */
    public function confirmRoleChange(): void
    {
        // Prevent self-demotion
        if ($this->user->id === auth()->id()) {
            session()->flash('error', 'You cannot change your own role.');
            $this->closeRoleModal();

            return;
        }

        match ($this->roleAction) {
            'promote' => $this->user->promoteToAdmin(),
            'demote' => $this->user->demoteToMember(),
            default => null,
        };

        $this->user->refresh();
        $this->closeRoleModal();

        $actionLabel = $this->roleAction === 'promote' ? 'promoted to Admin' : 'demoted to Member';
        session()->flash('success', "{$this->user->name} has been {$actionLabel}.");
    }

    public function render()
    {
        return view('livewire.admin.member-detail')
            ->title("{$this->user->name} - TribeTrip Admin");
    }
}
