# CLAUDE.md

This file provides guidance to Claude Code when working with code in this repository.

## Project Overview

**Pathfinder JK** is a modern website for the "Jizni Kriz" (South Cross) region of Klub Pathfinder - a Czech scouting organization. The application provides an information portal for events, registration system, photo galleries, and news/updates.

**Tech Stack:**
- PHP 8.2+ with Nette Framework 3.2+
- MySQL/MariaDB database via Nette Database Explorer
- Latte 3.1+ templating engine
- Tailwind CSS 3.4+ (utility-first CSS)
- Vite 6.3+ (build tool)
- Vanilla JavaScript

**Target Users:**
- Leaders (scoutmasters) - manage events, content
- Parents - view events, register children
- Members - browse, register for events
- Administrators - full system access

## Architecture

### Nette MVC Structure

The application follows Nette's MVP (Model-View-Presenter) pattern:

```
pathfinder-jk/
├── app/
│   ├── Bootstrap.php           # DI container setup
│   ├── Presentation/           # Presenters (controllers)
│   │   ├── @layout.latte       # Master layout template
│   │   ├── BasePresenter.php   # Abstract base with navbar
│   │   ├── Home/               # Homepage module
│   │   ├── Sign/               # Authentication
│   │   └── Error/              # Error pages (4xx, 5xx)
│   ├── Components/             # Reusable UI components
│   │   └── Navbar/             # Navigation component
│   ├── Model/Repository/       # Database access layer
│   ├── Forms/                  # Form factories
│   ├── Security/               # Authenticator
│   └── Core/                   # RouterFactory
├── config/
│   ├── common.neon             # Shared configuration
│   ├── services.neon           # DI service definitions
│   └── local.neon              # Local overrides (gitignored)
├── www/                        # Public document root
│   ├── index.php               # Single entry point
│   ├── css/style.css           # Compiled Tailwind CSS
│   └── images/                 # Static assets
├── assets/                     # Frontend source files
│   └── css/input.css           # Tailwind input
└── migrations/                 # Database SQL scripts
```

### Database Connection

Configured in `config/common.neon`:
- Database: `pathfinder_jk`
- Uses Nette Database Explorer with prepared statements
- Repository pattern for data access

### Routing

Routes defined in `app/Core/RouterFactory.php`:
- Single entry point: `www/index.php`
- URL pattern: `/<presenter>/<action>[/<id>]`
- Default: `Home:default`

### Component Pattern

Reusable UI components use factory pattern:
```php
// Factory interface for DI
interface NavbarFactory {
    public function create(): Navbar;
}

// Component renders its own Latte template
class Navbar extends Control {
    public function render(): void {
        $this->template->render(__DIR__ . '/Navbar.latte');
    }
}
```

## Development Environment

**Server:** XAMPP (Apache + MySQL) on Windows
**Document Root:** `pathfinder-jk/www/`
**PHP Version:** 8.2+

### Running the Application

```bash
# Start XAMPP Apache + MySQL
# Navigate to: http://localhost/web_oblastni-stranky_jk/pathfinder-jk/www/

# Or use PHP built-in server:
cd pathfinder-jk
php -S localhost:8000 -t www
```

### Building CSS

```bash
cd pathfinder-jk

# Development (watch mode)
npm run dev

# Production (minified)
npm run build
```

### Database Setup

1. Create database `pathfinder_jk`
2. Run `migrations/001_initial_schema.sql`
3. Update `config/local.neon` with credentials:
```neon
database:
    dsn: 'mysql:host=localhost;dbname=pathfinder_jk'
    user: root
    password: ''
```

## Key Conventions

### Presenters

- Located in `app/Presentation/{Module}/`
- Named as `{Name}Presenter.php`
- Extend `BasePresenter` for shared functionality
- Actions: `action{Name}()` for logic, `render{Name}()` for view data
- Templates in same directory as presenter

### Templates (Latte)

- Master layout: `app/Presentation/@layout.latte`
- Auto-escaping enabled (XSS protection)
- Blocks: `{block content}`, `{block scripts}`
- Components: `{control navbar}`

### Forms

- Created via factory classes in `app/Forms/`
- CSRF protection built-in via `FormFactory`
- Validation on both client and server side
- Success handlers redirect with flash messages

### Repositories

- Located in `app/Model/Repository/`
- Handle all database operations
- Return typed results
- Example methods: `findAll()`, `findById()`, `create()`, `update()`

## Security

**Implemented:**
- XSS Protection: Latte auto-escaping
- SQL Injection: Prepared statements via Nette Database
- CSRF: Nette Forms token validation
- Password Hashing: bcrypt (cost 12)
- Session Security: Nette session framework

**Authentication Flow:**
1. User submits email + password
2. `Authenticator` verifies against database
3. Creates `SimpleIdentity` with roles
4. Session stored securely

**Roles:**
- `admin` - Full access
- `leader` - Event/content management
- `member` - Basic access

## Design System

**Colors (Tailwind custom):**
```
primary: #0075b5 (Pathfinder Blue)
primary-light: #0fa6db
primary-dark: #005a8c
accent: #ffd600 (Gold)
forest: #009043 (Green)
earth: #764c24 (Brown)
```

**Typography:**
- Headings: Montserrat (Google Fonts)
- Body: Inter (Google Fonts)

**Responsive:**
- Mobile-first with Tailwind breakpoints
- Collapsible navbar on mobile
- Dark mode support (localStorage toggle)

## Current Implementation Status

**Completed:**
- Nette Framework setup with DI
- User authentication (login/register/logout)
- Database schema (9 tables)
- Homepage with hero section
- Responsive navbar component
- Form factories with validation
- Repository pattern
- Tailwind CSS integration
- Dark mode toggle

**In Progress / Planned:**
- Event detail pages
- Event registration system
- Admin panel (CRUD)
- News/articles system
- Photo galleries
- Static pages
- Contact forms
- REST API

## Code Style

**PHP:**
- PSR-12 coding standard
- Strict types enabled
- Type hints for parameters and returns
- Czech comments acceptable

**Templates:**
- Indentation: tabs
- Latte syntax for logic
- Tailwind classes for styling

**Git Commits:**
- Format: `feature-description_&_additional-info`
- Lowercase with underscores and ampersands
- Example: `add-event-registration_&_form-validation`

## Database Schema

**Main Tables:**
- `users` - User accounts with roles
- `events` - Event listings with registration
- `event_categories` - Event types (camp, meeting, etc.)
- `registrations` - User event sign-ups
- `news` - Articles/announcements
- `galleries` - Photo albums
- `gallery_images` - Photos
- `pages` - Static content
- `contacts` - Contact persons

## Commands Reference

```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Watch CSS changes
npm run dev

# Build production CSS
npm run build

# Clear Nette cache
rm -rf temp/cache/*
```

## Troubleshooting

**CSS changes not appearing:**
1. Run `npm run dev` or `npm run build`
2. Hard refresh browser (Ctrl+F5)
3. Check `www/css/style.css` was updated

**Database connection errors:**
1. Check MySQL is running
2. Verify `config/local.neon` credentials
3. Ensure database exists

**Template errors:**
1. Clear cache: `rm -rf temp/cache/*`
2. Check Latte syntax
3. Review Tracy debug bar
