# Changelog

All notable changes to TribeTrip will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

**Test User Seeder (Story 11 - Task 50)**
- Created `TestUserSeeder.php` for browser testing and manual QA
- Test accounts with known credentials:
  - Admin: `admin@test.local` / `testpassword123`
  - Member: `member@test.local` / `testpassword123`
- Both users pre-approved with email verified for immediate login
- Integrated into `DatabaseSeeder` for full database seeding

**PWA Infrastructure (Story 10)**
- Installed `devrabiul/laravel-pwa-kit` v1.1 for Progressive Web App support
- PWA manifest configured with TribeTrip branding:
  - Theme color: `#4A5240` (Dark Olive)
  - Background color: `#F2EDE4` (Cream)
  - App name and description
  - Using existing logo for PWA icon
- Service worker (`public/sw.js`) for offline caching
- Branded offline fallback page (`public/offline.html`)
- PWA directives integrated into all layouts:
  - `app.blade.php` (member layout)
  - `admin.blade.php` (admin layout)
  - `guest.blade.php` (auth pages)
  - `welcome.blade.php` (landing page)
- Livewire integration enabled for SPA-like experience
- Install toast notification customized for TribeTrip
- Camera capture already implemented with HTML5 `capture="environment"` attribute

**Marketing Home Page & Brand Design System**
- New marketing home page replacing Laravel default welcome page
- Hero section with value proposition and CTA buttons
- 6 UVP feature cards highlighting key member benefits:
  - Browse & Discover (Resource Catalog)
  - Easy Reservations (Booking Calendar)
  - Track Your Bookings (My Reservations)
  - Photo Evidence (Usage Logger) - highlighted as key differentiator
  - Fair & Transparent Billing (Automated Invoicing)
  - Mobile-First Experience (PWA)
- "How It Works" section with 4-step visual flow
- Key Benefits section with checklist
- Final CTA section with registration prompt
- Consistent brand color scheme extracted from logo:
  - Dark Olive `#4A5240` - Primary buttons, text logo
  - Forest Green `#3D4A36` - Headings, footer
  - Sage Green `#7A8B6E` - Icon accents, muted text
  - Terra Cotta `#8B5A3C` - Accent color, highlights
  - Cream `#F2EDE4` - Page backgrounds
  - Light Cream `#E8E2D6` - Section backgrounds
- Logo images (`logo1-icon-100.png`, `logo1-full-700.png`) integrated throughout
- Updated all layouts (guest, app, admin) with brand colors
- Full navigation header in app layout with user dropdown menu
- Admin sidebar navigation with resource management links
- Consistent card and button styling across all member pages
- Updated resource catalog, resource detail, my-reservations with brand colors
- Dashboard with quick action cards for common tasks

**User Registration & Approval (Story 1)**
- User model extended with `status` (pending/approved/rejected) and `role` (admin/member) enums
- Phone field added to user registration
- Guest and authenticated layout components with Flux UI
- `Register` Livewire component with form validation
- `Login` Livewire component
- `PendingApproval` page for users awaiting review
- Admin approval queue interface (`ApprovalQueue` component)
- Approve/reject users with optional rejection reason
- `RegistrationReceived` notification to users
- `NewRegistrationAlert` notification to admins
- `AccountApproved` and `AccountRejected` notifications
- Admin middleware to restrict admin routes
- 12 feature tests covering the complete registration flow

**Admin Invitation System (Story 2)**
- `Invitation` model with token generation, expiration, and status tracking
- `InvitationStatus` enum (pending/accepted/revoked/expired)
- `CreateInvitation` admin component with email sending option
- `InvitationList` admin component with filtering and revocation
- `RegisterWithInvitation` component for auto-approved registration
- `InvitationSent` email notification
- Factory with states for pending, accepted, revoked, expired
- 15 feature tests for invitation system

**Admin Member Management (Story 3)**
- `MemberList` admin component with search, status/role filtering
- `MemberDetail` component for viewing and editing member info
- `UserStatus::Suspended` added for account suspension
- User suspend/reactivate methods with reason tracking
- User promote/demote methods for role management
- Stats cards showing member counts by status
- Quick actions (suspend, reactivate) from member list
- Status change modals with reason input
- Role management modals for promote/demote
- Activity summary placeholder (for future usage integration)
- 18 feature tests for member management

**Member Profile Management (Story 4)**
- `Profile` Livewire component for member self-service profile management
- Profile view page showing personal info and membership status
- Edit profile modal for name, email, and phone updates
- Password change modal with current password validation
- Profile photo upload with storage and removal
- Notification preferences with 4 toggleable email settings:
  - Reservation confirmations
  - Reservation reminders
  - Invoice notifications
  - Community announcements
- `EnsureUserIsApproved` middleware for member routes
- Migration adding `profile_photo_path` and `notification_preferences` columns
- User model methods: `getNotificationSetting()`, `setNotificationSetting()`
- Route: `GET /member/profile` → `member.profile`

**Admin Resource Management (Story 5)**
- `Resource` model with type, status, and flexible pricing configuration
- `ResourceImage` model for multiple images per resource
- 4 new enums: `ResourceType`, `ResourceStatus`, `PricingModel`, `PricingUnit`
- Factories with states for vehicles, equipment, pricing models
- `ResourceList` admin component with grid view, search, type/status filters
- `ResourceForm` combined create/edit component with:
  - Basic info: name, description, type, status
  - Pricing: flat fee or per-unit (hour/day/mile/km/trip)
  - Multi-image upload with primary image selection
  - Availability: approval required, max days, advance booking
- Quick status actions: activate, deactivate, maintenance
- Delete confirmation with image cleanup
- Stats cards showing resource counts by type and status
- Routes: `/admin/resources`, `/admin/resources/create`, `/admin/resources/{resource}/edit`

**Resource Reservation System (Story 6)**
- `Reservation` model with status lifecycle (Pending → Confirmed → CheckedOut → Completed)
- `ReservationStatus` enum with blocking/cancellation logic
- Factory with states for all statuses and time scenarios
- `ResourceCatalog` member component for browsing active resources
- `ResourceDetail` component with:
  - Resource info and images gallery
  - Interactive availability calendar with month navigation
  - Visual indicators for days with existing bookings
  - Booking modal with date/time selection
- `MyReservations` dashboard with:
  - Upcoming/Past/Cancelled filter tabs
  - Reservation cancellation with reason
  - Resource info display
- Double-booking prevention via `isSlotAvailable()` checks
- Respects resource settings: requires_approval, max_reservation_days, advance_booking_days
- `ReservationConfirmed` notification to member on booking
- `NewReservationAlert` notification to admins on new reservations
- Relationships added to User and Resource models
- Routes: `/member/resources`, `/member/resources/{resource}`, `/member/reservations`

**Usage Logging System (Story 7)**
- `UsageLog` model tracking check-out/check-in with readings and photos
- `UsageLogStatus` enum with lifecycle: CheckedOut → Completed → Verified
- Factory with states for all statuses and photo scenarios
- `UsageCalculationService` for computing duration, distance, and cost
- Handles flat-fee and per-unit pricing calculation
- `UsageCheckout` member component with:
  - Camera-based photo capture of starting meter/odometer
  - Manual entry fallback for readings
  - Pre-check reminders and validation
- `UsageCheckin` member component with:
  - Ending photo capture and reading input
  - Usage summary calculation display
  - Post-use checklist reminders
- `UsageLogList` admin component with:
  - Filterable list by status, resource, member
  - Photo viewer modal for evidence review
  - Verification modal with approve/dispute actions
  - Stats cards for in-progress, pending, verified, disputed counts
- Updated `MyReservations` with Check Out/Check In action buttons
- Reservation status integration (CheckedOut, Completed)
- Routes: `/member/reservations/{reservation}/checkout`, `/member/usage/{usageLog}/checkin`, `/admin/usage-logs`

**Billing & Invoicing System (Story 8)**
- `Invoice` model with billing period, adjustments, and status tracking
- `InvoiceItem` model linking charges to usage logs and resources
- `InvoiceStatus` enum with lifecycle: Draft → Sent → Paid (+ Overdue, Voided)
- Factories with states for all invoice scenarios
- `InvoiceGenerationService` for automated invoice creation:
  - Aggregates uninvoiced billable usage logs per member
  - Creates itemized line items from usage data
  - Supports manual preview before generation
  - Period-based generation for monthly billing
- Artisan commands:
  - `invoices:generate-monthly` with preview, month selection, and auto-send options
  - `invoices:check-overdue` for daily overdue status updates
- Scheduled tasks in `routes/console.php`:
  - Monthly invoice generation on the 1st at 6 AM
  - Daily overdue check at midnight
- `InvoiceList` admin component with:
  - Stats cards for total, draft, sent, paid, overdue counts
  - Financial summary: outstanding balance, collected this month
  - Search by invoice number or member
  - Status filtering
  - Invoice detail modal with itemized breakdown
  - Generate invoices modal with preview
  - Adjustment modal for credits/discounts
  - Mark as paid confirmation flow
  - Send, void, and status actions
- `InvoiceNotification` with templates for:
  - Sent invoice with summary and due date
  - Payment reminder
  - Overdue notice
- Respects user notification preferences (`email_invoice_notifications`)
- Relationships added to User and UsageLog models
- Route: `/admin/invoices`

**Member Invoice Management (Story 9)**
- `MyInvoices` member component with:
  - Balance summary cards (outstanding/paid totals)
  - Filter tabs: All, Outstanding, Paid
  - Invoice list with status badges and due date highlighting
  - Invoice detail modal with itemized breakdown
- `InvoiceController` for secure invoice access
- Print-friendly invoice view (`invoices/print.blade.php`) with:
  - Professional invoice layout with brand styling
  - Company and billing info sections
  - Line items table with subtotals
  - Browser print/save-as-PDF functionality
- `UsageHistory` member component with:
  - Summary stats: total uses, spent, hours, distance
  - Resource and status filters
  - Usage log cards with photos and readings
  - Detail modal with complete usage breakdown
- Routes: `/member/invoices`, `/member/invoices/{invoice}/download`, `/member/usage-history`
- Brand-consistent styling matching marketing home page

### Planning
- Documented v1.0 MVP roadmap in Tynn project management
- Created 7 feature domains: Auth, Members, Resources, Reservations, Usage Logging, Billing, PWA
- Defined 16 reusable features across the application
- Created 10 user stories with 48 implementation tasks
- Updated PWA implementation to use `devrabiul/laravel-pwa-kit` package

---

## [1.0.0] - TBD

### MVP - Community Resource Sharing

First release enabling small communities to share resources (vehicles, equipment) with member approval, usage tracking, and automated billing.

#### Planned Features

**Authentication & Onboarding**
- User registration with admin approval workflow
- Admin invitation system for direct member onboarding
- Email notifications for registration status

**Member Management**
- Admin member directory with search/filter
- Member profile self-service
- Account status management (active, suspended)

**Resource Management**
- Admin CRUD for community resources
- Configurable pricing: one-off or per-unit (hourly, per mile)
- Resource images and availability settings

**Reservations**
- Resource catalog browse view
- Availability calendar with booking
- My reservations dashboard
- Double-booking prevention

**Usage Logging**
- Check-out/check-in workflow
- Photo capture of odometer/meter readings
- Manual entry fallback
- Usage calculation service

**Billing & Invoicing**
- Automated monthly invoice generation
- Itemized usage charges
- Admin invoice dashboard with adjustments
- Member invoice viewing and PDF download

**PWA Infrastructure** *(using `devrabiul/laravel-pwa-kit`)*
- Package-based manifest and service worker setup
- Livewire integration with install toast notifications
- Offline fallback page
- Mobile-optimized responsive design
- Camera API integration for photo evidence

---

## Project Info

- **Project**: TribeTrip
- **Vision**: Resource sharing app for small communities
- **Stack**: Laravel 12, Livewire 3, Flux UI, Tailwind CSS
- **Management**: Tracked in Tynn (tynn.dev)

