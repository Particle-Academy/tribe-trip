# TribeTrip v1.0 - Development TODO

> **Status**: Planning Complete | **Version**: 1.0 MVP  
> **Tynn Project**: TribeTrip | **Full details**: See Tynn dashboard

---

## Overview

| Stories | Tasks | Domains | Features |
|---------|-------|---------|----------|
| 10 | 48 | 7 | 16 |

---

## Stories & Tasks

### Story #1: User Registration & Approval Flow
> As a prospective member, I want to sign up and have my application reviewed by an admin.

- [ ] Create User model with approval status
- [ ] Build registration form component  
- [ ] Implement registration confirmation email
- [ ] Build pending approval status page
- [ ] Create admin approval queue interface
- [ ] Send approval/rejection notification emails

---

### Story #2: Admin Invitation System
> As an admin, I want to directly invite people to join without going through approval.

- [ ] Create Invitation model and migration
- [ ] Build admin invitation creation interface
- [ ] Create invitation registration flow
- [ ] Build invitation management list

---

### Story #3: Admin Member Management
> As an admin, I want to view and manage all community members.

- [ ] Build member list with search and filters
- [ ] Create member detail view
- [ ] Implement member edit and status actions

---

### Story #4: Member Profile Management
> As a member, I want to manage my profile information.

- [ ] Build profile view page
- [ ] Create profile edit form
- [ ] Implement notification preferences

---

### Story #5: Admin Resource Management
> As an admin, I want to define and configure community resources with pricing.

- [ ] Create Resource model with pricing
- [ ] Build resource CRUD interface
- [ ] Implement pricing configuration
- [ ] Add resource image upload
- [ ] Build resource usage statistics view

---

### Story #6: Resource Reservation System
> As a member, I want to request and reserve time slots for using resources.

- [ ] Create Reservation model
- [ ] Build resource catalog browse view
- [ ] Implement availability calendar
- [ ] Create reservation booking flow
- [ ] Build my reservations dashboard
- [ ] Send reservation confirmation notifications

---

### Story #7: Usage Logging System
> As a member, I want to log my resource usage with photo evidence.

- [ ] Create UsageLog model
- [ ] Build check-out interface
- [ ] Build check-in interface
- [ ] Implement usage calculation service
- [ ] Build admin usage log viewer

---

### Story #8: Billing & Invoicing System
> As an admin, I want automatic monthly invoices based on member usage.

- [ ] Create Invoice model
- [ ] Build invoice generation service
- [ ] Create monthly invoice scheduler
- [ ] Build admin invoice dashboard
- [ ] Implement invoice preview and adjustment
- [ ] Add mark as paid functionality
- [ ] Send invoice notification emails

---

### Story #9: Member Invoice Management
> As a member, I want to view and manage my invoices.

- [ ] Build member invoice list
- [ ] Create invoice detail view
- [ ] Implement PDF invoice download
- [ ] Build member usage history view

---

### Story #10: PWA Infrastructure
> As a member, I want to use the app on my mobile device like a native app.
> 
> **Package**: `devrabiul/laravel-pwa-kit`

- [ ] Install and configure laravel-pwa-kit (`composer require devrabiul/laravel-pwa-kit`)
- [ ] Integrate PWA directives in layout (`PwaKit::head()`, `PwaKit::scripts()`)
- [ ] Create app icons for PWA (512x512, 192x192, etc.)
- [ ] Optimize for mobile performance
- [ ] Implement camera API integration

---

## Domains

| Domain | Slug | Purpose |
|--------|------|---------|
| Auth | `auth` | Registration, approval, invitations |
| Members | `members` | Profiles, account management |
| Resources | `resources` | Resource definitions, pricing |
| Reservations | `reservations` | Booking, scheduling |
| Usage Logging | `usage-logging` | Time/mileage tracking, photos |
| Billing | `billing` | Invoices, payments |
| PWA | `pwa` | Offline, mobile optimization |

---

## Features

| Feature | Slug | Domain |
|---------|------|--------|
| Registration Form | `registration-form` | Auth |
| Email Notifications | `email-notifications` | Auth |
| Approval Queue | `approval-queue` | Auth |
| Invitation Manager | `invitation-manager` | Auth |
| Member Directory | `member-directory` | Members |
| Profile Editor | `profile-editor` | Members |
| Resource Manager | `resource-manager` | Resources |
| Resource Catalog | `resource-catalog` | Resources |
| Booking Calendar | `booking-calendar` | Reservations |
| My Reservations | `my-reservations` | Reservations |
| Usage Logger | `usage-logger` | Usage Logging |
| Photo Capture | `photo-capture` | Usage Logging |
| Invoice Dashboard | `invoice-dashboard` | Billing |
| Invoice Generator | `invoice-generator` | Billing |
| My Invoices | `my-invoices` | Billing |
| PWA Shell | `pwa-shell` | PWA |

---

## Development Notes

### Tech Stack
- **Backend**: Laravel 12, PHP 8.4
- **Frontend**: Livewire 3, Flux UI (Pro), Tailwind CSS
- **PWA**: devrabiul/laravel-pwa-kit
- **Testing**: PHPUnit
- **Linting**: Laravel Pint

### Guidelines
- Use Flux UI components where possible
- Optimize for accessibility and low bandwidth
- Colorblind-friendly UI elements
- Mobile-first responsive design

---

*Last updated: 2024-11-30*

