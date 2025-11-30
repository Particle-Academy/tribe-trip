<?php

namespace App\Livewire\Admin;

use App\Enums\InvitationStatus;
use App\Models\Invitation;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Admin component for managing invitations.
 *
 * Lists all invitations with filtering and revocation actions.
 */
#[Layout('components.layouts.admin')]
#[Title('Invitations - TribeTrip Admin')]
class InvitationList extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    /**
     * Revoke a pending invitation.
     */
    public function revoke(int $invitationId): void
    {
        $invitation = Invitation::findOrFail($invitationId);

        if ($invitation->revoke()) {
            session()->flash('success', "Invitation for {$invitation->email} has been revoked.");
        } else {
            session()->flash('error', 'This invitation cannot be revoked.');
        }
    }

    /**
     * Resend the invitation email.
     */
    public function resend(int $invitationId): void
    {
        $invitation = Invitation::findOrFail($invitationId);

        if (! $invitation->isUsable()) {
            session()->flash('error', 'This invitation is no longer valid.');

            return;
        }

        \Illuminate\Support\Facades\Notification::route('mail', $invitation->email)
            ->notify(new \App\Notifications\InvitationSent($invitation));

        session()->flash('success', "Invitation resent to {$invitation->email}.");
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

    public function render()
    {
        $invitations = Invitation::query()
            ->with(['invitedBy', 'acceptedBy'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('email', 'like', "%{$this->search}%")
                        ->orWhere('name', 'like', "%{$this->search}%");
                });
            })
            ->when($this->status, function ($query) {
                if ($this->status === 'expired') {
                    // Include both explicitly expired and past-due pending
                    $query->where(function ($q) {
                        $q->where('status', InvitationStatus::Expired)
                            ->orWhere(function ($q2) {
                                $q2->where('status', InvitationStatus::Pending)
                                    ->where('expires_at', '<=', now());
                            });
                    });
                } else {
                    $query->where('status', $this->status);
                }
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Get status counts for filters
        $statusCounts = [
            'pending' => Invitation::pending()->where('expires_at', '>', now())->count(),
            'accepted' => Invitation::accepted()->count(),
            'revoked' => Invitation::where('status', InvitationStatus::Revoked)->count(),
            'expired' => Invitation::expired()->count(),
        ];

        return view('livewire.admin.invitation-list', [
            'invitations' => $invitations,
            'statusCounts' => $statusCounts,
        ]);
    }
}
