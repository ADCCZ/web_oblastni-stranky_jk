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

### Key Files

**Presenters:**
- `HomePresenter` - Homepage with upcoming events (6 max) and latest news (6 max)
- `SignPresenter` - Login (`actionIn`), Registration (`actionUp`), Logout (`actionOut`)
- `Error4xxPresenter` / `Error5xxPresenter` - Error handling with templates

**Components:**
- `Navbar` - Responsive navigation with animated logo, desktop/mobile menus, dark mode toggle, login state

**Forms:**
- `SignInFormFactory` - Email, password, remember me; validates credentials, manages session expiration
- `SignUpFormFactory` - Full registration with name, email, phone, password confirmation, newsletter, terms

**Repositories:**
- `UserRepository` - User CRUD, email lookup, existence check
- `EventRepository` - Events with filtering (published, upcoming, past, by category), registration count
- `NewsRepository` - News articles with filtering (published, latest)

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

**Colors (CSS variables with dark mode support):**
```css
--primary: #0075b5          /* Pathfinder Blue */
--primary-light: #0fa6db
--primary-dark: #005a8c
--accent: #ffd600           /* Gold */
--forest: #009043           /* Green */
--earth: #764c24            /* Brown */
--bg-primary: #f8fafc       /* Light background */
--text-primary: #1e293b     /* Dark text */
```

**Typography:**
- Navigation: Oswald (Google Fonts)
- Body: Inter (Google Fonts)
- Fluid sizing with `clamp()` (e.g., `clamp(2.5rem, 10vw, 7rem)` for hero titles)

**Responsive:**
- Mobile-first design with CSS clamp() for fluid scaling
- Collapsible hamburger navbar on mobile with slide animation
- Dark mode support (localStorage toggle with sun/moon icons)
- Scroll-reveal animations using IntersectionObserver

**Custom CSS:**
- 1984 lines of inline CSS in `@layout.latte`
- Keyframe animations: logoSlideDown, navbarExpand, heroFadeSlideUp, scrollRevealUp
- Watermark logos (semi-transparent fixed backgrounds)

## Current Implementation Status

### Completed (Working Features)

**Authentication System:**
- User login with email + password (SignInFormFactory)
- User registration with full validation (SignUpFormFactory)
- Password hashing (BCRYPT, cost 12)
- Session management (14 days with "remember me", 20 minutes otherwise)
- Role-based identity (member, leader, admin)
- Auto-login after registration
- Logout with session destroy and flash message
- Active status checking (is_active field)
- Email uniqueness validation

**User Interface:**
- Responsive navbar with animated logo (slides down on page load)
- Desktop navigation with 8 links (AKCE, AKTUALITY, ODDILY, GALERIE, HISTORIE, O NAS, ODKAZY, KONTAKT)
- Mobile hamburger menu with full navigation and 1.5s transition
- Dark mode toggle with localStorage persistence
- Hero section with gradient overlay and background image
- 6 activity cards with scroll-reveal animations
- Upcoming events grid (3 cols desktop, 2 tablet, 1 mobile)
- Latest news section
- CTA section with newsletter signup form (UI only)
- Footer with quick links and contact info
- Flash messages (success, error, warning, info)
- Error pages (404, 403, 410, 500)

**Data Layer:**
- UserRepository: findByEmail, findById, create, update, emailExists, getAll
- EventRepository: findAll, findPublished, findUpcoming, findPast, findById, findBySlug, findByCategory, create, update, delete, getRegistrationCount
- NewsRepository: findAll, findPublished, findLatest, findById, findBySlug, create, update, delete

**Forms:**
- SignInFormFactory: email, password, remember checkbox
- SignUpFormFactory: first/last name, email, phone, password with confirmation, newsletter checkbox, terms checkbox

**Database:**
- 10 tables created (users, events, event_categories, registrations, news, galleries, gallery_images, pages, contacts, audit_logs)
- Default seed data (5 event categories, sample admin user, sample page)
- Foreign keys and indexes properly defined

### In Progress / Planned

**Not Yet Implemented:**
- Event detail pages (`Event:show`)
- Event listing page (`Event:default`)
- Event registration system (schema exists)
- News listing and detail pages
- Admin panel and dashboard
- Gallery system (CRUD, image upload, lightbox)
- Static pages system
- Contact forms
- REST API
- Password reset / email verification
- Social login (Google, Facebook, Discord - buttons exist as placeholders)
- Newsletter subscription backend
- File upload functionality
- Image optimization
- Audit logging (schema exists)

### Implementation Progress

| Module | Status | Notes |
|--------|--------|-------|
| Foundation | 100% | Nette setup, DI, routing |
| Authentication | 90% | Missing password reset, email verification |
| Frontend UI | 85% | Responsive, animated, dark mode |
| Database Schema | 100% | All 10 tables created |
| Repositories | 60% | User, Event, News done; others pending |
| Business Logic | 10% | Only homepage data fetching |
| Admin Panel | 0% | Not started |
| Event System | 20% | Repository done, no pages |
| Gallery System | 0% | Schema only |
| API | 0% | Not started |

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

**10 Tables (defined in `migrations/001_initial_schema.sql`):**

| Table | Key Columns | Purpose |
|-------|-------------|---------|
| `users` | id, email, password_hash, first_name, last_name, role, phone, is_active | User accounts (roles: admin, leader, member) |
| `events` | id, title, slug, description, content, location, start_date, end_date, registration_from/to, capacity, price, category_id, is_published | Event listings |
| `event_categories` | id, name, slug, color, icon | Event types (Schuzka, Vikendovka, Tabor, Soutez, Celorepublikova akce) |
| `registrations` | id, user_id, event_id, status, note | User event sign-ups (status: pending, confirmed, cancelled, waitlist) |
| `news` | id, title, slug, perex, content, is_published, published_at | Articles/announcements |
| `galleries` | id, title, slug, description, event_id, is_published | Photo albums |
| `gallery_images` | id, gallery_id, filename, original_name, alt_text, sort_order | Photos in galleries |
| `pages` | id, title, slug, content, meta_title, meta_description, is_published | Static content pages |
| `contacts` | id, name, role_title, email, phone, photo_path, sort_order | Contact persons |
| `audit_logs` | id, user_id, action, entity_type, entity_id, old_values, new_values, ip_address | Activity logging |

## Commands Reference

```bash
# Install PHP dependencies
cd pathfinder-jk
composer install

# Install Node dependencies
npm install

# Watch CSS changes (development)
npm run dev

# Build production CSS
npm run build

# Clear Nette cache (Windows)
rmdir /s /q temp\cache

# Clear Nette cache (Unix/Mac)
rm -rf temp/cache/*
```

## Troubleshooting

**CSS changes not appearing:**
1. Run `npm run dev` or `npm run build`
2. Hard refresh browser (Ctrl+F5)
3. Check `www/css/style.css` was updated
4. Note: Most custom CSS is inline in `@layout.latte`

**Database connection errors:**
1. Check MySQL is running in XAMPP Control Panel
2. Verify `config/local.neon` credentials
3. Ensure database `pathfinder_jk` exists
4. Run `migrations/001_initial_schema.sql`

**Template errors:**
1. Clear cache: delete contents of `temp/cache/`
2. Check Latte syntax
3. Review Tracy debug bar (bottom of page in dev mode)

**Login not working:**
1. Check user exists in database with correct password_hash
2. Verify is_active = 1 for the user
3. Clear session data in browser

## Next Development Steps

Priority order for completing the project:

1. **Event System** - Create EventPresenter with default (list) and show (detail) actions
2. **News System** - Create NewsPresenter with article listing and detail pages
3. **Event Registration** - Implement registration form and management
4. **Admin Panel** - Create AdminPresenter with CRUD for events, news, users
5. **Gallery System** - Implement photo upload and gallery display
6. **Static Pages** - Create PagePresenter for CMS pages
7. **REST API** - Add API endpoints for events and news
