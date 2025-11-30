{{-- Member profile view/edit page --}}
<div class="space-y-6">
    {{-- Page header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">My Profile</flux:heading>
            <flux:text class="mt-1 text-zinc-500">Manage your account information and preferences</flux:text>
        </div>
    </div>

    {{-- Flash messages --}}
    @if (session('success'))
        <flux:callout variant="success" icon="check-circle" dismissible>
            {{ session('success') }}
        </flux:callout>
    @endif

    @if (session('error'))
        <flux:callout variant="danger" icon="exclamation-circle" dismissible>
            {{ session('error') }}
        </flux:callout>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Main profile card --}}
        <div class="lg:col-span-2 space-y-6">
            <flux:card>
                <div class="flex items-center justify-between mb-4">
                    <flux:heading size="lg">Personal Information</flux:heading>
                    <flux:button wire:click="openEditModal" variant="ghost" size="sm" icon="pencil">
                        Edit
                    </flux:button>
                </div>

                <dl class="space-y-4">
                    <div class="flex items-center gap-4">
                        {{-- Profile photo --}}
                        <div class="relative group cursor-pointer" wire:click="openPhotoModal">
                            @if ($user->profile_photo_url)
                                <img
                                    src="{{ $user->profile_photo_url }}"
                                    alt="{{ $user->name }}"
                                    class="w-20 h-20 rounded-full object-cover ring-2 ring-zinc-200 dark:ring-zinc-700"
                                />
                            @else
                                <flux:avatar size="xl" name="{{ $user->name }}" />
                            @endif
                            <div class="absolute inset-0 flex items-center justify-center rounded-full bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity">
                                <flux:icon name="camera" class="w-6 h-6 text-white" />
                            </div>
                        </div>
                        <div>
                            <dt class="text-sm text-zinc-500">Full Name</dt>
                            <dd class="font-medium text-lg">{{ $user->name }}</dd>
                        </div>
                    </div>

                    <div>
                        <dt class="text-sm text-zinc-500">Email Address</dt>
                        <dd>
                            <flux:link href="mailto:{{ $user->email }}">{{ $user->email }}</flux:link>
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm text-zinc-500">Phone Number</dt>
                        <dd>{{ $user->phone ?: '—' }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm text-zinc-500">Member Since</dt>
                        <dd>{{ $user->created_at->format('F j, Y') }}</dd>
                    </div>
                </dl>
            </flux:card>

            {{-- Security card --}}
            <flux:card>
                <div class="flex items-center justify-between mb-4">
                    <flux:heading size="lg">Security</flux:heading>
                    <flux:button wire:click="openPasswordModal" variant="ghost" size="sm" icon="key">
                        Change Password
                    </flux:button>
                </div>

                <div class="flex items-center gap-4">
                    <div class="p-3 bg-zinc-100 dark:bg-zinc-800 rounded-lg">
                        <flux:icon name="lock-closed" class="w-6 h-6 text-zinc-600 dark:text-zinc-400" />
                    </div>
                    <div>
                        <flux:text class="font-medium">Password</flux:text>
                        <flux:text size="sm" class="text-zinc-500">
                            Last updated {{ $user->updated_at->diffForHumans() }}
                        </flux:text>
                    </div>
                </div>
            </flux:card>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Membership status card --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">Membership Status</flux:heading>

                <div class="flex items-center gap-3 mb-4">
                    <flux:badge size="lg" :color="$user->status->color()">
                        {{ $user->status->label() }}
                    </flux:badge>
                </div>

                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <flux:text class="text-zinc-500">Role</flux:text>
                        <flux:text class="font-medium">{{ $user->role->label() }}</flux:text>
                    </div>
                    <div class="flex justify-between">
                        <flux:text class="text-zinc-500">Member Since</flux:text>
                        <flux:text class="font-medium">{{ $user->created_at->format('M j, Y') }}</flux:text>
                    </div>
                </div>
            </flux:card>

            {{-- Notification preferences card --}}
            <flux:card>
                <div class="flex items-center justify-between mb-4">
                    <flux:heading size="lg">Notifications</flux:heading>
                    <flux:button wire:click="openNotificationsModal" variant="ghost" size="sm" icon="cog-6-tooth">
                        Manage
                    </flux:button>
                </div>

                <div class="space-y-2 text-sm">
                    <div class="flex items-center gap-2">
                        @if ($user->getNotificationSetting('email_reservation_confirmations'))
                            <flux:icon name="check-circle" class="w-4 h-4 text-green-500" />
                        @else
                            <flux:icon name="x-circle" class="w-4 h-4 text-zinc-400" />
                        @endif
                        <flux:text class="text-zinc-600 dark:text-zinc-300">Reservation confirmations</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($user->getNotificationSetting('email_reservation_reminders'))
                            <flux:icon name="check-circle" class="w-4 h-4 text-green-500" />
                        @else
                            <flux:icon name="x-circle" class="w-4 h-4 text-zinc-400" />
                        @endif
                        <flux:text class="text-zinc-600 dark:text-zinc-300">Reservation reminders</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($user->getNotificationSetting('email_invoice_notifications'))
                            <flux:icon name="check-circle" class="w-4 h-4 text-green-500" />
                        @else
                            <flux:icon name="x-circle" class="w-4 h-4 text-zinc-400" />
                        @endif
                        <flux:text class="text-zinc-600 dark:text-zinc-300">Invoice notifications</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($user->getNotificationSetting('email_community_announcements'))
                            <flux:icon name="check-circle" class="w-4 h-4 text-green-500" />
                        @else
                            <flux:icon name="x-circle" class="w-4 h-4 text-zinc-400" />
                        @endif
                        <flux:text class="text-zinc-600 dark:text-zinc-300">Community announcements</flux:text>
                    </div>
                </div>
            </flux:card>

            {{-- Activity summary (placeholder for future) --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">Activity Summary</flux:heading>

                <div class="space-y-3">
                    <div class="flex justify-between">
                        <flux:text size="sm" class="text-zinc-500">Total Reservations</flux:text>
                        <flux:text size="sm" class="font-medium">—</flux:text>
                    </div>
                    <div class="flex justify-between">
                        <flux:text size="sm" class="text-zinc-500">Upcoming Reservations</flux:text>
                        <flux:text size="sm" class="font-medium">—</flux:text>
                    </div>
                    <div class="flex justify-between">
                        <flux:text size="sm" class="text-zinc-500">Outstanding Balance</flux:text>
                        <flux:text size="sm" class="font-medium">—</flux:text>
                    </div>
                </div>

                <flux:text size="xs" class="text-zinc-400 mt-4">
                    Activity data will appear after making reservations.
                </flux:text>
            </flux:card>
        </div>
    </div>

    {{-- Edit profile modal --}}
    <flux:modal wire:model="showEditModal">
        <div class="space-y-4">
            <flux:heading size="lg">Edit Profile</flux:heading>

            <form wire:submit="saveProfile" class="space-y-4">
                <flux:input
                    wire:model="name"
                    label="Full Name"
                    placeholder="Enter your full name"
                    required
                />

                <flux:input
                    wire:model="email"
                    type="email"
                    label="Email Address"
                    placeholder="your@email.com"
                    required
                />

                <flux:input
                    wire:model="phone"
                    label="Phone Number"
                    placeholder="(555) 123-4567"
                />

                <div class="flex justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <flux:button wire:click="closeEditModal" variant="ghost" type="button">
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        Save Changes
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Change password modal --}}
    <flux:modal wire:model="showPasswordModal">
        <div class="space-y-4">
            <flux:heading size="lg">Change Password</flux:heading>

            <form wire:submit="updatePassword" class="space-y-4">
                <flux:input
                    wire:model="current_password"
                    type="password"
                    label="Current Password"
                    placeholder="Enter your current password"
                    required
                />

                <flux:input
                    wire:model="new_password"
                    type="password"
                    label="New Password"
                    placeholder="Enter new password"
                    required
                />

                <flux:input
                    wire:model="new_password_confirmation"
                    type="password"
                    label="Confirm New Password"
                    placeholder="Confirm new password"
                    required
                />

                <div class="flex justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <flux:button wire:click="closePasswordModal" variant="ghost" type="button">
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        Change Password
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Photo upload modal --}}
    <flux:modal wire:model="showPhotoModal">
        <div class="space-y-4">
            <flux:heading size="lg">Profile Photo</flux:heading>

            <div class="flex flex-col items-center gap-4">
                {{-- Current or new photo preview --}}
                @if ($photo)
                    <img
                        src="{{ $photo->temporaryUrl() }}"
                        alt="Preview"
                        class="w-32 h-32 rounded-full object-cover ring-2 ring-zinc-200 dark:ring-zinc-700"
                    />
                @elseif ($user->profile_photo_url)
                    <img
                        src="{{ $user->profile_photo_url }}"
                        alt="{{ $user->name }}"
                        class="w-32 h-32 rounded-full object-cover ring-2 ring-zinc-200 dark:ring-zinc-700"
                    />
                @else
                    <flux:avatar size="xl" name="{{ $user->name }}" class="w-32 h-32" />
                @endif

                <flux:input
                    wire:model="photo"
                    type="file"
                    accept="image/*"
                    label="Select a new photo"
                    description="JPG, PNG, GIF up to 2MB"
                />

                @error('photo')
                    <flux:text class="text-red-500 text-sm">{{ $message }}</flux:text>
                @enderror
            </div>

            <div class="flex justify-between gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <div>
                    @if ($user->profile_photo_url)
                        <flux:button wire:click="removePhoto" variant="danger" size="sm">
                            Remove Photo
                        </flux:button>
                    @endif
                </div>
                <div class="flex gap-3">
                    <flux:button wire:click="closePhotoModal" variant="ghost">
                        Cancel
                    </flux:button>
                    <flux:button wire:click="savePhoto" variant="primary" :disabled="!$photo">
                        Save Photo
                    </flux:button>
                </div>
            </div>
        </div>
    </flux:modal>

    {{-- Notification preferences modal --}}
    <flux:modal wire:model="showNotificationsModal">
        <div class="space-y-4">
            <flux:heading size="lg">Notification Preferences</flux:heading>

            <flux:text class="text-zinc-500">
                Choose which email notifications you'd like to receive.
            </flux:text>

            <div class="space-y-4">
                <flux:switch
                    wire:model="email_reservation_confirmations"
                    label="Reservation Confirmations"
                    description="Get notified when your reservations are confirmed or cancelled"
                />

                <flux:switch
                    wire:model="email_reservation_reminders"
                    label="Reservation Reminders"
                    description="Receive reminders before your scheduled reservations"
                />

                <flux:switch
                    wire:model="email_invoice_notifications"
                    label="Invoice Notifications"
                    description="Get notified when new invoices are generated"
                />

                <flux:switch
                    wire:model="email_community_announcements"
                    label="Community Announcements"
                    description="Receive important community updates and announcements"
                />
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <flux:button wire:click="closeNotificationsModal" variant="ghost">
                    Cancel
                </flux:button>
                <flux:button wire:click="saveNotifications" variant="primary">
                    Save Preferences
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
