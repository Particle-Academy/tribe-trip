<?php

use App\Livewire\Admin\ApprovalQueue;
use App\Livewire\Admin\CreateInvitation;
use App\Livewire\Admin\InvitationList;
use App\Livewire\Admin\MemberDetail;
use App\Livewire\Admin\MemberList;
use App\Livewire\Admin\ResourceForm;
use App\Livewire\Admin\ResourceList;
use App\Livewire\Admin\InvoiceList;
use App\Livewire\Admin\UsageLogList;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\PendingApproval;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\RegisterWithInvitation;
use App\Livewire\Member\MyInvoices;
use App\Livewire\Member\MyReservations;
use App\Livewire\Member\UsageHistory;
use App\Livewire\Member\Profile;
use App\Livewire\Member\ResourceCatalog;
use App\Livewire\Member\ResourceDetail;
use App\Livewire\Member\UsageCheckin;
use App\Livewire\Member\UsageCheckout;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| TribeTrip community resource sharing application routes.
|
*/

Route::get('/', function () {
    return view('welcome');
})->name('home');

/*
|--------------------------------------------------------------------------
| Guest Routes (Unauthenticated)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/register', Register::class)->name('register');
    Route::get('/register/invite/{token}', RegisterWithInvitation::class)->name('register.invite');
    Route::get('/login', Login::class)->name('login');
});

Route::get('/register/pending', PendingApproval::class)->name('register.pending');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::post('/logout', function () {
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('home');
    })->name('logout');
});

/*
|--------------------------------------------------------------------------
| Member Routes (Authenticated & Approved)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'approved'])->prefix('member')->group(function () {
    Route::get('/profile', Profile::class)->name('member.profile');

    // Resource browsing and booking
    Route::get('/resources', ResourceCatalog::class)->name('member.resources');
    Route::get('/resources/{resource}', ResourceDetail::class)->name('member.resources.show');

    // Reservations
    Route::get('/reservations', MyReservations::class)->name('member.reservations');

    // Usage logging
    Route::get('/reservations/{reservation}/checkout', UsageCheckout::class)->name('member.usage.checkout');
    Route::get('/usage/{usageLog}/checkin', UsageCheckin::class)->name('member.usage.checkin');

    // Invoices
    Route::get('/invoices', MyInvoices::class)->name('member.invoices');
    Route::get('/invoices/{invoice}/download', [\App\Http\Controllers\InvoiceController::class, 'download'])
        ->name('member.invoices.download');

    // Usage history
    Route::get('/usage-history', UsageHistory::class)->name('member.usage-history');
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/approvals', ApprovalQueue::class)->name('admin.approvals');
    Route::get('/invitations', InvitationList::class)->name('admin.invitations');
    Route::get('/invitations/create', CreateInvitation::class)->name('admin.invitations.create');
    Route::get('/members', MemberList::class)->name('admin.members');
    Route::get('/members/{user}', MemberDetail::class)->name('admin.members.show');

    // Resource management
    Route::get('/resources', ResourceList::class)->name('admin.resources');
    Route::get('/resources/create', ResourceForm::class)->name('admin.resources.create');
    Route::get('/resources/{resource}/edit', ResourceForm::class)->name('admin.resources.edit');

    // Usage logs
    Route::get('/usage-logs', UsageLogList::class)->name('admin.usage-logs');

    // Invoices
    Route::get('/invoices', InvoiceList::class)->name('admin.invoices');
});
