<p align="center">
  <img src="public/images/logo1-full-700.png" width="400" alt="TribeTrip Logo">
</p>

<p align="center">
  <strong>Community Resource Sharing Made Simple</strong>
</p>

<p align="center">
  <a href="#features">Features</a> •
  <a href="#tech-stack">Tech Stack</a> •
  <a href="#requirements">Requirements</a> •
  <a href="#installation">Installation</a> •
  <a href="#configuration">Configuration</a> •
  <a href="#running-the-application">Running</a> •
  <a href="#testing">Testing</a> •
  <a href="#contributing">Contributing</a>
</p>

---

> [!IMPORTANT]
> **Self-Hosting Requirement:** This project uses [Flux UI Pro](https://fluxui.dev), a paid component library. You'll need to purchase a Flux Pro license (~$149) to self-host TribeTrip.
>
> **Hosted Service Coming Soon:** Don't want to manage your own server? We're building a hosted version of TribeTrip where communities can sign up and get started in minutes — no technical setup required. [Join the waitlist →](https://tribetrip.app)

<p align="center">
  <a href="preview/README.md"><strong>📸 View Screenshot Gallery →</strong></a>
</p>

---

## About TribeTrip

TribeTrip is an open-source web application that enables small communities to share vehicles, equipment, and spaces with transparent tracking and fair billing. Whether you're managing a homeowner's association, co-op, church group, or any tight-knit community, TribeTrip provides the tools to coordinate shared resources effortlessly.

### The Problem

Small communities often share resources informally—sign-up sheets on clipboards, text message chains, or honor-system donations. This leads to:
- Scheduling conflicts and double-bookings
- Unclear accountability for damage or excessive use
- Difficult or unfair cost allocation
- No visibility into resource availability

### The Solution

TribeTrip provides a complete resource-sharing platform with:
- **Visual calendar booking** with conflict prevention
- **Photo-based check-out/check-in** for accountability
- **Usage tracking** (mileage, hours, etc.)
- **Automated monthly invoicing** based on actual use
- **Mobile-first PWA** that works offline

---

## Features

### For Community Members
- 📋 **Resource Catalog** — Browse shared vehicles, equipment, and spaces with photos and descriptions
- 📅 **Easy Reservations** — Visual calendar shows availability; book time slots instantly
- 📱 **Mobile Check-out/in** — Capture odometer readings and condition photos on your phone
- 💰 **My Invoices** — View itemized monthly charges and download PDF invoices
- 📊 **Usage History** — Track all your past reservations and usage

### For Administrators
- ✅ **Member Approval Queue** — Review and approve new member registrations
- ✉️ **Invitation System** — Send email invitations with pre-approved registration links
- 🛠️ **Resource Management** — Add/edit resources with multiple images, pricing models, and booking rules
- 📝 **Usage Log Verification** — Review and verify member-submitted usage logs
- 📈 **Invoice Generation** — Automated monthly invoice creation with manual generation option

### Pricing Models
TribeTrip supports flexible pricing for different resource types:
- **Flat Fee** — Single charge per reservation (e.g., meeting room rental)
- **Per Hour** — Hourly rate (e.g., equipment rental)
- **Per Day** — Daily rate (e.g., vehicle rental)
- **Per Mile/Kilometer** — Distance-based (e.g., shared car programs)

---

## Tech Stack

| Layer             | Technology                                                                     |
| ----------------- | ------------------------------------------------------------------------------ |
| **Framework**     | [Laravel 12](https://laravel.com)                                              |
| **Frontend**      | [Livewire 3](https://livewire.laravel.com) + [Alpine.js](https://alpinejs.dev) |
| **UI Components** | [Flux UI](https://fluxui.dev)                                                  |
| **Styling**       | [Tailwind CSS 4](https://tailwindcss.com)                                      |
| **Database**      | SQLite (default) / MySQL / PostgreSQL                                          |
| **Testing**       | [Pest](https://pestphp.com)                                                    |
| **PWA**           | [Laravel PWA Kit](https://github.com/niceplugin/laravel-pwa-kit)               |

---

## Requirements

- **PHP** ≥ 8.2
- **Composer** ≥ 2.0
- **Node.js** ≥ 18.0
- **npm** ≥ 9.0

### Optional
- MySQL 8.0+ or PostgreSQL 15+ (if not using SQLite)

---

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/your-org/tribetrip.git
cd tribetrip
```

### 2. Install Dependencies

```bash
composer install
npm install
```

### 3. Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Database Setup

**For SQLite (default, simplest option):**
```bash
touch database/database.sqlite
php artisan migrate
```

**For MySQL/PostgreSQL:**
Update your `.env` file with your database credentials, then:
```bash
php artisan migrate
```

### 5. Build Frontend Assets

```bash
npm run build
```

### 6. Create Admin User

The easiest way to get started is to seed the test users:

```bash
php artisan db:seed --class=TestUserSeeder
```

This creates:
| Role   | Email             | Password |
| ------ | ----------------- | -------- |
| Admin  | admin@test.local  | password |
| Member | member@test.local | password |

**For production**, create your admin user manually or via tinker:

```bash
php artisan tinker
```

```php
use App\Models\User;
use App\Enums\UserRole;
use App\Enums\UserStatus;

User::create([
    'name' => 'Admin Name',
    'email' => 'admin@yourdomain.com',
    'password' => bcrypt('your-secure-password'),
    'role' => UserRole::Admin,
    'status' => UserStatus::Approved,
    'status_changed_at' => now(),
]);
```

### 7. (Optional) Seed Sample Data

For development/demo purposes, populate the database with sample resources and usage history:

```bash
php artisan db:seed --class=SampleDataSeeder
```

---

## Configuration

### Essential Environment Variables

```env
APP_NAME=TribeTrip
APP_URL=http://localhost:8000

# Database (SQLite is default)
DB_CONNECTION=sqlite
# DB_DATABASE=/absolute/path/to/database.sqlite

# Mail (for notifications)
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### Flux UI License

This project uses [Flux UI Pro](https://fluxui.dev) for its component library. To install Flux UI:

1. Purchase a license at [fluxui.dev](https://fluxui.dev)
2. Add your Flux auth credentials to `auth.json`:

```json
{
    "http-basic": {
        "composer.fluxui.dev": {
            "username": "your-email@example.com",
            "password": "your-license-key"
        }
    }
}
```

3. Run `composer install` (or `composer update livewire/flux-pro`)

---

## Running the Application

### Development Mode

The project includes a convenient dev script that runs all services concurrently:

```bash
composer run dev
```

This starts:
- Laravel development server (`http://localhost:8000`)
- Queue worker (for background jobs)
- Log viewer (Laravel Pail)
- Vite dev server (for hot reloading)

### Production Mode

```bash
# Build optimized assets
npm run build

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run with your preferred web server (nginx, Apache, etc.)
```

### Accessing the Application

- **Homepage:** `http://localhost:8000`
- **Login:** `http://localhost:8000/login`
- **Register:** `http://localhost:8000/register`
- **Admin Dashboard:** `http://localhost:8000/admin/approvals` (admin login required)

---

## Testing

TribeTrip uses [Pest](https://pestphp.com) for testing.

### Run All Tests

```bash
php artisan test
```

### Run Specific Tests

```bash
# Run a specific test file
php artisan test tests/Feature/ReservationTest.php

# Filter by test name
php artisan test --filter="can create reservation"
```

### Code Formatting

The project uses [Laravel Pint](https://laravel.com/docs/pint) for code style:

```bash
# Fix all files
./vendor/bin/pint

# Check only changed files
./vendor/bin/pint --dirty
```

---

## Project Structure

```
app/
├── Enums/              # Status and type enumerations
├── Http/
│   ├── Controllers/    # Traditional controllers (invoices, etc.)
│   └── Middleware/     # Auth and approval checks
├── Livewire/
│   ├── Admin/          # Admin panel components
│   ├── Auth/           # Authentication components
│   └── Member/         # Member-facing components
├── Models/             # Eloquent models
├── Notifications/      # Email notifications
└── Services/           # Business logic services

resources/views/
├── components/         # Blade components
├── layouts/            # App layouts
└── livewire/           # Livewire component views

tests/
├── Feature/            # Integration tests
└── Unit/               # Unit tests
```

---

## Contributing

We welcome contributions! Here's how to get started:

1. **Fork the repository**
2. **Create a feature branch:** `git checkout -b feature/amazing-feature`
3. **Make your changes**
4. **Run tests:** `php artisan test`
5. **Run Pint:** `./vendor/bin/pint`
6. **Commit your changes:** `git commit -m 'Add amazing feature'`
7. **Push to the branch:** `git push origin feature/amazing-feature`
8. **Open a Pull Request**

### Guidelines

- Write tests for new features
- Follow existing code conventions
- Update documentation as needed
- Keep PRs focused and atomic

---

## Roadmap

- [ ] Multi-community support (SaaS mode)
- [ ] Payment gateway integration (Stripe)
- [ ] Resource damage reporting
- [ ] Calendar integrations (Google Calendar, iCal)
- [ ] Push notifications
- [ ] API for third-party integrations

---

## License

TribeTrip is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

## Acknowledgments

- [Laravel](https://laravel.com) — The PHP framework for web artisans
- [Livewire](https://livewire.laravel.com) — Full-stack framework for Laravel
- [Flux UI](https://fluxui.dev) — Beautiful UI components for Livewire
- [Tailwind CSS](https://tailwindcss.com) — Utility-first CSS framework
- [Pest](https://pestphp.com) — Elegant PHP testing framework

---

<p align="center">
  Built with ❤️ for communities everywhere
</p>
