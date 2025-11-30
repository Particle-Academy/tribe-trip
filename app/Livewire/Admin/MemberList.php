<?php

namespace App\Livewire\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Admin component for viewing and managing community members.
 *
 * Lists all members with search, filtering, and quick actions.
 */
#[Layout('components.layouts.admin')]
#[Title('Members - TribeTrip Admin')]
class MemberList extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $role = '';

    /**
     * Quick suspend a member.
     */
    public function suspend(int $userId): void
    {
        $user = User::findOrFail($userId);

        // Don't allow suspending yourself or other admins
        if ($user->id === auth()->id()) {
            session()->flash('error', 'You cannot suspend your own account.');

            return;
        }

        if ($user->isAdmin()) {
            session()->flash('error', 'Admin accounts cannot be suspended from here.');

            return;
        }

        $user->suspend('Suspended by admin.');
        session()->flash('success', "{$user->name} has been suspended.");
    }

    /**
     * Quick reactivate a suspended member.
     */
    public function reactivate(int $userId): void
    {
        $user = User::findOrFail($userId);

        $user->reactivate('Reactivated by admin.');
        session()->flash('success', "{$user->name} has been reactivated.");
    }

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

    public function updatedRole(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $members = User::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%");
                });
            })
            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            })
            ->when($this->role, function ($query) {
                $query->where('role', $this->role);
            })
            ->orderByDesc('created_at')
            ->paginate(15);

        // Get counts for filter badges
        $counts = [
            'total' => User::count(),
            'approved' => User::approved()->count(),
            'pending' => User::pending()->count(),
            'suspended' => User::suspended()->count(),
            'rejected' => User::rejected()->count(),
            'admins' => User::admins()->count(),
            'members' => User::members()->count(),
        ];

        return view('livewire.admin.member-list', [
            'members' => $members,
            'counts' => $counts,
        ]);
    }
}
