# Witbo — Business Management Platform

> A multi-tenant SaaS platform for Cyprus-based small and medium businesses.  
> Built with Laravel 12, Vue 3, and Inertia.js.

---

## Overview

Witbo is a full-featured business management platform with a strong focus on **Cyprus compliance** —
including local payroll calculations, VAT invoicing, and PSD2 banking integrations with Bank of Cyprus and Eurobank.

Each business operates in an isolated **workspace** (tenant), with role-based access, tier-based feature gating, and a billing system powered by myPOS.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.4 · Laravel 12 |
| Frontend | Vue 3 · Inertia.js · TypeScript |
| Build | Vite 7 |
| Database | MySQL (Eloquent ORM) |
| Auth | Laravel Breeze + Sanctum (API tokens) |
| PDF | barryvdh/laravel-dompdf |
| Queues | Laravel Queue (database driver) |
| Banking | Bank of Cyprus PSD2 + Eurobank PSD2 + myPOS |

---

## Features

### 💼 Invoicing & Quotes
- Create, edit, void and issue credit notes on invoices
- Serial number integrity (void preserves the number sequence)
- PDF generation & email delivery
- Public shareable invoice links
- Quote-to-invoice conversion
- Multi-currency support

### 📊 Expenses & Payroll (Cyprus)
Full Cyprus payroll calculation engine:
- **Social Insurance (SI)** — employee & employer contributions
- **GESI** — General Health System contributions
- **Provident Fund** — configurable per-employee rates
- **Social Cohesion Fund**
- **Redundancy Fund & Training Fund**
- **Holiday Fund**
- **Union contributions**
- **Income tax (PAYE)**
- Payslip PDF generation per employee

### 👤 Staff Management
- Staff profiles with full HR data (ID, tax, IBAN, SI number)
- Leave management (annual, sick, other) with approval workflow
- Document vault (securely stored, downloadable)
- Leave balance tracking

### 🏦 Banking Integrations
- **Bank of Cyprus PSD2** — OAuth2 account + transaction sync
- **Eurobank PSD2** — OAuth2 account + transaction sync
- **myPOS** — POS transaction retrieval
- Automated sync scheduled at 07:00 and 14:00 daily (queue worker)

### 📦 Inventory & Products
- Product catalogue with categories
- Stock management with movement history
- Physical and service product types
- VAT-aware pricing (net/gross)

### 👥 CRM
- Contact management (individuals & companies)
- Call log / communication history
- Follow-up reminders
- Quote and invoice association

### 🛡️ Multi-tenant Architecture
- Each business is an isolated **workspace**
- All data is scoped by `workspace_id` — no cross-tenant data leakage
- **Tier system** with feature gating:

| Feature | Starter (€20/mo) | Professional (€50/mo) | Enterprise (€200/mo) |
|---|---|---|---|
| Max staff | 1 | 5 | Unlimited |
| Max users | 1 | 3 | 99 |
| API tokens | 0 | 2 | 999 |
| Banking | Basic | Multi-currency | Multi-currency |
| Audit logs | ❌ | ❌ | ✅ |

### 🔐 Admin Console
- Super-admin panel for managing all workspaces and users
- Workspace activation/suspension
- Manual subscription payment recording
- Tier management

---

## Local Development Setup

### Prerequisites
- PHP 8.2+
- Composer
- Node.js 20+
- MySQL 8+

### Install

```bash
git clone https://github.com/ChrysostomosPogiatzis/app-invoice.git
cd app-invoice

# Backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed  # optional demo data

# Frontend
npm install
npm run dev
```

### Environment

Key `.env` values to configure:

```env
APP_URL=https://your-domain.com
APP_ENV=production
APP_DEBUG=false

DB_DATABASE=your_db
DB_USERNAME=your_user
DB_PASSWORD=your_password

# Banking (PSD2)
BOC_CLIENT_ID=
BOC_CLIENT_SECRET=
EUROBANK_CLIENT_ID=
EUROBANK_CLIENT_SECRET=

# myPOS Billing
MYPOS_CLIENT_ID=
MYPOS_CLIENT_SECRET=
MYPOS_PARTNER_CLIENT_ID=
MYPOS_PARTNER_CLIENT_SECRET=

# Email
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=noreply@yourdomain.com
```

---

## Production Deployment

```bash
# Install dependencies
composer install --no-dev --optimize-autoloader
npm install && npm run build

# Laravel optimizations
php artisan migrate --force
php artisan optimize   # caches config, routes, views

# Queue worker (use supervisor in production)
php artisan queue:work --sleep=3 --tries=3 --daemon
```

### Supervisor config (recommended)

```ini
[program:witbo-worker]
command=php /var/www/html/artisan queue:work --sleep=3 --tries=3 --daemon
autostart=true
autorestart=true
stderr_logfile=/var/log/witbo-worker.err.log
stdout_logfile=/var/log/witbo-worker.out.log
```

---

## Security

- All database queries scoped by `workspace_id` — IDOR-proof
- CSRF protection enabled on all state-changing routes
- Mass assignment protected via `$fillable` on all models
- API tokens tier-locked and workspace-scoped
- `APP_DEBUG=false` enforced in production
- Dependencies audited: `composer audit` + `npm audit` → 0 vulnerabilities

---

## Running Tests

```bash
php artisan test
```

---

## License

Proprietary — © Witbo / Chrysostomos Pogiatzis. All rights reserved.
