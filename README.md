# Hackazon — Laravel Migration

> **Based on [Hackazon by Rapid7](https://github.com/rapid7/hackazon)** — all credit for the original application, vulnerability design, and training content goes to the Rapid7 team.

Hackazon is an **intentionally vulnerable** e-commerce web application originally built by Rapid7 for offensive security training. This repository is a migration of the original PHP 5.4 / PHPixie codebase to **PHP 8.4 / Laravel 13**, carried out with the assistance of AI (Claude by Anthropic).

> **Migration status:** This migration has not been fully tested. Basic flows (browsing, registration, login, cart, orders) have been verified manually, but many features — GWT helpdesk, AMF/Flash endpoints, REST API, checkout flow, admin panel — may still have bugs. Use at your own risk and report issues.

> **WARNING:** This application contains deliberate security vulnerabilities including SQL Injection, XSS, CSRF, IDOR, Remote File Inclusion, XXE, and OS Command Injection. **Do not deploy on a public server or production environment.**

---

## Stack

| Layer | Technology |
|---|---|
| Language | PHP 8.4+ |
| Framework | Laravel 13 |
| Database | MySQL 8 (same schema as original) |
| Frontend | jQuery + Knockout.js + Bootstrap (unchanged) |
| Special modules | GWT (Google Web Toolkit), AMF/Flash, REST API |

---

## Running with Docker (recommended)

Docker is the recommended way to run Hackazon for security labs. It handles all dependencies automatically and makes resetting the database trivial between student sessions.

### Requirements

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (or Docker Engine + Compose v2)

### Start the lab

```bash
# Clone the repo
git clone <repo-url>
cd hackazon_new

# Build and start all services (first run takes ~2 minutes)
docker compose up -d --build

# OR using Make
make up
```

The app will be available at **http://localhost:8080**

### Resetting the database

Students can trigger XSS payloads, create accounts, place orders, etc. When you need to restore a clean state:

```bash
# Fast reset — only wipes the database, app stays running (~15 seconds)
./docker/reset-db.sh
# OR
make reset

# Full reset — tears down everything and rebuilds from scratch
./docker/reset-db.sh --full
# OR
make reset-full
```

After `reset`, the database is restored to the original demo data: all injected payloads, created accounts, modified records are gone.

### Other useful commands

```bash
make logs      # tail application logs
make shell     # bash shell inside the app container
make db-shell  # MySQL shell (database: hackazon)
make down      # stop containers (data preserved)
```

---

## Manual setup (without Docker)

### Requirements

- PHP 8.4+
- MySQL 8
- Composer

### Installation

```bash
# Install PHP dependencies
composer install

# Copy environment file and set your DB credentials
cp .env.example .env

# Generate application key
php artisan key:generate

# Load the database schema and seed data
mysql -u hackazon -p hackazon < database/hackazon_schema.sql
mysql -u hackazon -p hackazon < database/migration_coupons.sql
mysql -u hackazon -p hackazon < database/migration_credit_card.sql
mysql -u hackazon -p hackazon < database/hackazon_demo_data.sql

# Start the development server
php artisan serve
```

---

## Intentional Vulnerabilities

All vulnerabilities are preserved from the original application and controlled via the VulnModule system at `/admin/vulnerability/context`.

| Vulnerability | Where to trigger |
|---|---|
| SQL Injection | Search bar, wishlist search |
| XSS (Reflected) | Login — username field echoed back |
| XSS (Stored) | Product reviews, contact form, registration |
| CSRF | Checkout, cart, best price form |
| IDOR | Orders (`/account/orders/{id}`), wishlists |
| Remote File Inclusion | Account → Help articles |
| OS Command Injection | Account → Documents |
| XXE | REST API XML endpoint (`/rest/product`) |

---

## Demo Credentials

| Role | Username | Password |
|---|---|---|
| Admin | `admin` | `123456` |
| User | `test_user` | `123456` |

## Admin Interface

- URL: **http://localhost:8080/admin**
- Enable/disable specific vulnerabilities: `/admin/vulnerability/context`

---

## Architecture

```
app/
  Http/Controllers/     # Laravel controllers
  Models/               # Eloquent models
  VulnModule/           # Vulnerability injection system (preserved as-is)
  AmfphpModule/         # AMF/Flash gateway
  GWTModule/            # Google Web Toolkit RPC backend
  SearchFilters/        # Product search filters
  Auth/                 # MD5 auth provider (legacy password hashing)
  Exception/            # Custom exception classes

resources/views/        # Blade templates
routes/web.php          # Frontend routes
routes/api.php          # REST API routes
database/               # SQL schema + seed data
docker/                 # Reset script
public/                 # Static assets, GWT compiled JS, AMF back office
vendor/
  hackazon/amfphp/      # AMF library (vendored)
  gwtphp/gwtphp/        # GWT PHP library (vendored)
```

---

## Notes

- **Password hashing:** MD5 — preserved for compatibility with the original demo data
- **Sessions:** File-based (no `sessions` table required)
- **GWT helpdesk:** `/helpdesk` served correctly by Apache inside Docker; PHP's built-in server (`artisan serve`) intercepts the `public/helpdesk/` directory at that path
- **AMF endpoint:** `/amf` requires a Flash client; back office at `/amf_back_office/`

---

## Credits & Original Project

**Original application:** [Hackazon by Rapid7](https://github.com/rapid7/hackazon)
— PHP 5.4 / PHPixie, all vulnerability design and training content by the Rapid7 team.

**This migration** was performed with the assistance of [Claude Code](https://claude.ai/code) (Anthropic).
The migration preserves all intentional vulnerabilities exactly as designed in the original.

> The migration has not been fully tested and is not guaranteed to be 100% operational.
> Contributions and bug reports are welcome.
