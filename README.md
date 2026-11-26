# CargoTrace — Global Shipment Visibility & Tracking Platform

Web Programming 2 · UAS final project. A Laravel application that lets logistics companies and their customers
manage shipments, follow a tracking timeline on a map, detect delays with deterministic rules and manage exceptions.

## Problem
Shipment information is scattered, customers cannot see the latest status/history, delays and exceptions are not
visible in one place, and operators need a dashboard to prioritise follow-ups.

## What's new in the redesign
* New navy/cyan logistics UI (responsive sidebar, toasts, confirmation dialogs, loading states, dropdowns).
* Login page with logistics background, show/hide password, remember me, forgot/reset password, login with email **or** username.
* **Sea / Air / Land freight** with mode-specific data (vessel + container, flight + airline, vehicle + driver) and mode filters.
* **Tracking** page with a large Leaflet world map, status/mode filters, search, fullscreen, per-mode marker icons, status colours and a detail panel. Positions are **last reported locations** (from tracking events), never fake live GPS; reported path (solid) and estimated route (dashed) are distinguished.
* 8-stage journey stepper (never marks *Delivered* unless the status is DELIVERED).
* **Global Weather** (OpenWeatherMap when `OPENWEATHER_API_KEY` is set, otherwise Open-Meteo) and **Currency Exchange** (ECB rates via Frankfurter) with real APIs, caching and error states.
* Transport Management, Customers and Settings pages. See [UPGRADE.md](UPGRADE.md) to update an existing install.

## Features
* **Role-based access** (server-side): Administrator, Logistics Operator, Customer.
* **Shipment lifecycle** `CREATED → BOOKED → PICKED_UP → IN_TRANSIT → DELIVERED` (+ `CANCELLED`), validated in the backend state machine (`App\Enums\ShipmentStatus`). Terminal statuses cannot be reopened.
* **Tracking events** (append-only history), chronological timeline, exception events highlighted, current location = latest event with coordinates, time-order validation.
* **ETA / delay rules** (`EtaService`): delay hours, configurable group threshold, severity LOW/MEDIUM/HIGH, with the reason and values shown.
* **Exception management**: 6 types, severity, OPEN → INVESTIGATING → RESOLVED, detection/resolution times.
* **Maps**: Leaflet + OpenStreetMap (origin, destination, tracking points, popups, attribution).
* **External APIs** (Laravel HTTP Client): OpenStreetMap Nominatim geocoding (locations form) and Open-Meteo weather (shipment page). Timeouts/errors/empty data are handled; weather is informational only.
* **Dashboard** (all numbers from the DB), Chart.js charts, **search / filter / sort / server-side pagination**, CSV export, reports per carrier.
* **In-app notifications** and **audit log** (actor, action, time, entity, old/new values).
* **REST API** with Sanctum, consistent JSON errors, pagination, Postman collection.

## Tech stack
PHP 8.2+ (8.3 recommended) · Laravel 12 · MySQL 8 · Blade · Tailwind CSS (Play CDN) · Vanilla JS · Chart.js · Leaflet · Sanctum · PHPUnit · Docker (optional) · GitHub Actions.

## Setup
```bash
composer install
cp .env.example .env
php artisan key:generate
# create an empty MySQL database "cargotrace" and set DB_* in .env
php artisan migrate --seed
php artisan serve        # http://localhost:8000
# Windows PowerShell (older versions): run the commands one per line instead of chaining with &&
```
Demo accounts (password `password`): `admin`, `operator`, `customer` (usernames) or `admin@cargotrace.test`, `operator@cargotrace.test`, `customer@cargotrace.test`.
Forgot password in local mode writes the reset link to `storage/logs/laravel.log` (`MAIL_MAILER=log`).

Docker alternative: set `DB_HOST=db`, `DB_PASSWORD=secret` in `.env`, then `docker compose up --build` and run `docker compose exec app php artisan migrate --seed`.

> The UI loads Tailwind, Chart.js, Leaflet and icons from public CDNs, so no `npm` build step is needed (internet access required in the browser).

## Group scenario (unique parameters)
Edit `.env` (see `config/cargotrace.php`): `DELAY_THRESHOLD_HOURS` (12/18/24 …), severity bands, `GROUP_NAME`, `GROUP_KPI`.
Seed data (`DatabaseSeeder`) uses a Southeast Asia → Europe sea-freight scenario; change carriers/locations/routes there for your group.

## Tests
```bash
php artisan test
```
Covers authentication, shipment CRUD + validation + authorization, tracking events and invalid status transitions,
ETA/delay rules (thresholds, severity bands), exceptions, role access to pages, and external-API failure handling
(`Http::fake`). Tests use in-memory SQLite.

## API
See [docs/API.md](docs/API.md) and `postman/CargoTrace.postman_collection.json` (login first; the token is stored automatically).

## Documentation
[ERD & design decisions](docs/ERD.md) · [Data dictionary](docs/DATA_DICTIONARY.md) · [Architecture](docs/ARCHITECTURE.md) · [Attribution](docs/ATTRIBUTION.md)

## Deployment (VPS / cloud)
1. Provision PHP 8.3 + MySQL 8 + Nginx/Apache (document root = `public/`).
2. `composer install --no-dev --optimize-autoloader`, create `.env` with `APP_ENV=production`, `APP_DEBUG=false`, real DB credentials, `php artisan key:generate`.
3. `php artisan migrate --force --seed` (seed only for the demo), `php artisan config:cache route:cache view:cache`.
4. Make `storage/` and `bootstrap/cache/` writable. Put the live URL and demo account here: **<LIVE URL>** .

## Security
Password hashing, auth + role middleware, policies on every shipment endpoint, FormRequest validation, CSRF (web), Blade escaping
and XSS-safe JS data injection, Eloquent bound queries (no SQL injection), mass-assignment protection (`status`, delay fields and
`role` are not fillable), login/API rate limiting, CSV formula-injection guard, no stack traces in API errors, `.env` ignored by git.

## Known limitations / future work
* Tailwind and JS libraries come from CDNs (compile with Vite for production hardening).
* No email notifications (in-app only), no "reopen delivered shipment" special procedure.
* Dashboard map shows up to 12 active shipments; reports are computed in PHP (fine for demo-size data, move to SQL aggregates for scale).
* Nominatim/OSM tiles are for light usage only; use a commercial provider for production traffic.
* The code was generated with AI assistance: review, run and be able to explain every module (see `docs/ATTRIBUTION.md`).
