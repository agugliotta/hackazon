# Hackazon — Laravel Migration

Hackazon is an **intentionally vulnerable** e-commerce web application originally built by Rapid7 for offensive security training. This repository is a migration of the original PHP 5.4/PHPixie codebase to **PHP 8.2+ / Laravel 13**.

> **WARNING:** This application contains deliberate security vulnerabilities including SQL Injection, XSS, CSRF, IDOR, Remote File Inclusion, XXE, and OS Command Injection. **Do not deploy on a public server or production environment.**

---

## Stack

| Layer | Technology |
|---|---|
| Language | PHP 8.2+ |
| Framework | Laravel 13 |
| Database | MySQL 8 (same schema as original) |
| Frontend | jQuery + Knockout.js + Bootstrap (unchanged) |
| Special modules | GWT (Google Web Toolkit), AMF/Flash, REST API |

---

## Setup

### Requirements

- PHP 8.2+
- MySQL 8
- Composer
- Node.js (optional, for asset building)

### Installation

```bash
# Install PHP dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure database in .env
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=hackazon
# DB_USERNAME=hackazon
# DB_PASSWORD=your_password

# Load the database schema and seed data
mysql -u hackazon -p hackazon < database/db.sql
mysql -u hackazon -p hackazon < database/demo_database.sql

# Apply migrations
mysql -u hackazon -p hackazon < database/migrations/2014.10.10_1_coupons.sql
mysql -u hackazon -p hackazon < database/migrations/2014.11.07_1_credit_card.sql

# Start the development server
php artisan serve
```

---

## Intentional Vulnerabilities

All vulnerabilities are preserved from the original application and controlled via the VulnModule system at `/admin/vulnerability`.

| Vulnerability | Location |
|---|---|
| SQL Injection | Search, login, wishlist search |
| XSS (Reflected) | Login username echo, search results |
| XSS (Stored) | Reviews, registration, contact form |
| CSRF | Checkout, cart, best price |
| IDOR | Orders, wishlists, product access |
| Remote File Inclusion | Account help articles |
| OS Command Injection | Account documents |
| XXE | REST API XML endpoint |

---

## Architecture

```
app/
  Http/Controllers/     # Laravel controllers (migrated from PHPixie)
  Models/               # Eloquent models (migrated from PHPixie ORM)
  VulnModule/           # Vulnerability injection system (preserved as-is)
  AmfphpModule/         # AMF/Flash gateway
  GWTModule/            # Google Web Toolkit RPC backend
  SearchFilters/        # Product search filter classes
  Auth/                 # Custom MD5 auth provider (legacy password hashing)
  Exception/            # Custom exception classes

resources/views/        # Blade templates (migrated from PHPixie views)
routes/
  web.php               # All frontend routes
  api.php               # REST API routes
database/               # Legacy SQL schema (not Laravel migrations)
public/                 # Static assets, GWT compiled JS, AMF back office
vendor/
  hackazon/amfphp/      # AMF library (vendored, not on Packagist)
  gwtphp/gwtphp/        # GWT PHP library (vendored, not on Packagist)
```

---

## Admin Interface

- URL: `/admin`
- Default credentials are set in `database/demo_database.sql`
- Vulnerability configuration: `/admin/vulnerability/context`

---

## Notes

- **Password hashing:** Legacy MD5 (preserved for compatibility with existing demo data)
- **Sessions:** File-based by default (no `sessions` table required)
- **GWT helpdesk:** `/helpdesk` — the GWT compiled JS lives in `public/helpdesk/`; use Apache/Nginx in production (PHP built-in server intercepts the directory)
- **AMF endpoint:** `/amf` — requires a Flash client; `/amf_back_office/` for the admin interface

---

## Original Project

[Hackazon by Rapid7](https://github.com/rapid7/hackazon) — PHP 5.4 / PHPixie original source.
