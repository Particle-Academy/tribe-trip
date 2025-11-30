<?php

namespace App\Livewire\Member;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Member profile management component.
 *
 * Allows members to view and edit their profile information,
 * upload photos, change password, and manage notification preferences.
 */
#[Layout('components.layouts.app')]
class Profile extends Component
{
    use WithFileUploads;

    // Profile information fields
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|email')]
    public string $email = '';

    #[Validate('nullable|string|max:20')]
    public string $phone = '';

    // Profile photo
    #[Validate('nullable|image|max:2048')]
    public $photo;

    // Password change fields
    #[Validate('required|string|current_password')]
    public string $current_password = '';

    public string $new_password = '';

    public string $new_password_confirmation = '';

    // Notification preferences
    public bool $email_reservation_confirmations = true;

    public bool $email_reservation_reminders = true;

    public bool $email_invoice_notifications = true;

    public bool $email_community_announcements = true;

    // UI state for modals
    public bool $showEditModal = false;

    public bool $showPasswordModal = false;

    public bool $showPhotoModal = false;

    public bool $showNotificationsModal = false;

    /**
     * Mount the component with the authenticated user's data.
     */
    public function mount(): void
    {
        $this->loadUserData();
    }

    /**
     * Load user data into form fields.
     */
    private function loadUserData(): void
    {
        $user = auth()->user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone ?? '';

        // Load notification preferences
        $this->email_reservation_confirmations = $user->getNotificationSetting('email_reservation_confirmations');
        $this->email_reservation_reminders = $user->getNotificationSetting('email_reservation_reminders');
        $this->email_invoice_notifications = $user->getNotificationSetting('email_invoice_notifications');
        $this->email_community_announcements = $user->getNotificationSetting('email_community_announcements');
    }

    /*
    |--------------------------------------------------------------------------
    | Modal Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Open the edit profile modal and prep form fields.
     */
    public function openEditModal(): void
    {
        $this->loadUserData();
        $this->resetValidation();
        $this->showEditModal = true;
    }

    /**
     * Close the edit profile modal.
     */
    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->loadUserData();
        $this->resetValidation();
    }

    /**
     * Open the password change modal.
     */
    public function openPasswordModal(): void
    {
        $this->current_password = '';
        $this->new_password = '';
        $this->new_password_confirmation = '';
        $this->resetValidation(['current_password', 'new_password', 'new_password_confirmation']);
        $this->showPasswordModal = true;
    }

    /**
     * Close the password change modal.
     */
    public function closePasswordModal(): void
    {
        $this->showPasswordModal = false;
        $this->current_password = '';
        $this->new_password = '';
        $this->new_password_confirmation = '';
        $this->resetValidation(['current_password', 'new_password', 'new_password_confirmation']);
    }

    /**
     * Open the photo upload modal.
     */
    public function openPhotoModal(): void
    {
        $this->photo = null;
        $this->resetValidation(['photo']);
        $this->showPhotoModal = true;
    }

    /**
     * Close the photo upload modal.
     */
    public function closePhotoModal(): void
    {
        $this->showPhotoModal = false;
        $this->photo = null;
        $this->resetValidation(['photo']);
    }

    /**
     * Open the notification preferences modal.
     */
    public function openNotificationsModal(): void
    {
        $this->loadUserData();
        $this->showNotificationsModal = true;
    }

    /**
     * Close the notification preferences modal.
     */
    public function closeNotificationsModal(): void
    {
        $this->showNotificationsModal = false;
        $this->loadUserData();
    }

    /*
    |--------------------------------------------------------------------------
    | Save Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Save updated profile information.
     */
    public function saveProfile(): void
    {
        $user = auth()->user();

        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
        ]);

        $user->update([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone ?: null,
        ]);

        $this->closeEditModal();
        session()->flash('success', 'Profile updated successfully.');
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(): void
    {
        $this->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'new_password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ]);

        auth()->user()->update([
            'password' => Hash::make($this->new_password),
        ]);

        $this->closePasswordModal();
        session()->flash('success', 'Password changed successfully.');
    }

    /**
     * Upload and save a new profile photo.
     */
    public function savePhoto(): void
    {
        $this->validate([
            'photo' => 'required|image|max:2048',
        ]);

        $user = auth()->user();

        // Delete old photo if exists
        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        // Store new photo
        $path = $this->photo->store('profile-photos', 'public');

        $user->update([
            'profile_photo_path' => $path,
        ]);

        $this->closePhotoModal();
        session()->flash('success', 'Profile photo updated successfully.');
    }

    /**
     * Remove the current profile photo.
     */
    public function removePhoto(): void
    {
        $user = auth()->user();

        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
            $user->update(['profile_photo_path' => null]);
        }

        $this->closePhotoModal();
        session()->flash('success', 'Profile photo removed.');
    }

    /**
     * Save notification preferences.
     */
    public function saveNotifications(): void
    {
        auth()->user()->update([
            'notification_preferences' => [
                'email_reservation_confirmations' => $this->email_reservation_confirmations,
                'email_reservation_reminders' => $this->email_reservation_reminders,
                'email_invoice_notifications' => $this->email_invoice_notifications,
                'email_community_announcements' => $this->email_community_announcements,
            ],
        ]);

        $this->closeNotificationsModal();
        session()->flash('success', 'Notification preferences saved.');
    }

    public function render()
    {
        return view('livewire.member.profile', [
            'user' => auth()->user(),
        ])->title('My Profile - TribeTrip');
    }
}
