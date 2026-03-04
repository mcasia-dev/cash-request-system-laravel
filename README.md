# McAsia Foodtrade Cash Request System

A Laravel 11 + Filament 3 internal system for managing cash requests from submission to approval, release, and liquidation.

## Table of Contents
- [System Scope](#system-scope)
- [Core Features](#core-features)
- [Technology Stack](#technology-stack)
- [System Architecture](#system-architecture)
- [Main Modules](#main-modules)
- [Cash Request Workflow](#cash-request-workflow)
- [Status and Remarks](#status-and-remarks)
- [Local Development Setup](#local-development-setup)
- [Operations](#operations)
- [Seeders and Initial Data](#seeders-and-initial-data)
- [Troubleshooting](#troubleshooting)
- [Roadmap / Notes](#roadmap--notes)

## System Scope
The system handles:
- User registration and user approval
- Cash request creation (`petty cash`, `cash advance`)
- Dynamic approval routing based on amount and request nature
- Finance verification and payment processing
- Treasury release scheduling and release confirmation
- Liquidation submission with receipt uploads
- Notifications (database + email)
- Activity and authentication logs

## Core Features
- Filament admin portal as the main application UI
- Multi-role approval matrix (`approval_rules` + `approval_rule_steps`)
- Role and permission management via Spatie Permission
- Request tracking and lifecycle transitions
- Automatic cancellation of unclaimed release windows
- Automatic liquidation aging updates

## Technology Stack
- PHP `^8.2`
- Laravel `^11`
- Filament `^3.3`
- MySQL (recommended for production)
- Vite + TailwindCSS
- Spatie packages for permissions and activity logs

## System Architecture
The project follows standard Laravel structure with Filament resources for most business operations:
- `app/Filament/Resources`: core business modules and admin UI
- `app/Services`: workflow logic (approval flow, liquidation, cancellation, remarks/status resolver)
- `app/Models`: domain entities (cash request, approvals, liquidation, release, payment, user, etc.)
- `routes/console.php`: scheduled automation jobs
- `database/migrations` + `database/seeders`: schema and initial data

## Main Modules
Key resources in `app/Filament/Resources`:
- `CashRequestResource`
- `ForApprovalRequestResource`
- `ForFinanceVerificationResource`
- `PaymentProcessResource`
- `ForCashReleaseResource`
- `ForLiquidationResource`
- `ApprovalRuleResource`
- `UserResource`
- `UserApprovalResource`
- `DepartmentResource`
- `ActivityListResource`

## Cash Request Workflow
High-level flow:

1. User registers account.
2. Super admin / Department Head (based on what department selected during registration) approves/disapproves registration.
3. Approved user creates cash request.
4. System resolves matching approval rule by:
   - `nature_of_request`
   - `requesting_amount` range
5. Assigned approver roles process approval/rejection.
6. Approved requests move into finance verification (if the nature of request is cash_advance) and payment processing queues.
7. Treasury schedules and releases cash.
8. Request moves to liquidation queue.
9. Requester uploads liquidation receipts.
10. Treasury/finance processes completion until liquidated.

## Status and Remarks
### Main `status` values
- `pending`
- `in progress`
- `approved`
- `rejected`
- `cancelled`
- `released`
- `liquidated`

### Common `status_remarks` values
- `Request Submitted`
- `For Finance Verification`
- `For Payment Processing`
- `For Releasing`
- `For Liquidation`
- `Liquidation Receipt Submitted`
- `Liquidated`
- Role-based approved/rejected remarks (department head, president, treasury, etc.)

## Local Development Setup
### 1. Prerequisites
- PHP 8.2+
- Composer
- Node.js + npm
- MySQL

### 2. Install dependencies
Clone this repository
```bash
git clone git@github.com:mcasia-dev/cash-request-system-laravel.git

cd cash-request-system-laravel

composer install
npm install
```

### 3. Environment
```bash
cp .env.example .env
php artisan key:generate
```

Update database and mail settings in `.env`.

### 4. Database
```bash
php artisan migrate
php artisan db:seed
php artisan storage:link
```

### 5. Run development servers
Option A: run all with Composer script
```bash
composer run dev
```

Option B: run manually in separate terminals
```bash
php artisan serve
php artisan queue:listen --tries=1
npm run dev
```

## Operations
### Queue worker
Emails and some async actions depend on queue processing:
```bash
php artisan queue:work
```
or you can change the value of `QUEUE_CONNECTION` in .env into `sync`

### Scheduler
Two key scheduled jobs are configured in `routes/console.php`:
- Daily `00:05` (Asia/Manila): update liquidation aging
- Every 5 minutes: auto-cancel unclaimed releases past release window

For production, configure cron:
```bash
* * * * * php /path/to/project/artisan schedule:run >> /dev/null 2>&1
```

### Logs
- Laravel logs: `storage/logs/laravel.log`
- Activity logs: `activity_log` table
- Authentication logs: `authentication_log` table
- Email logs: `filament_email_log` table

## Seeders and Initial Data
`DatabaseSeeder` loads:
- `DepartmentSeeder`
- `RoleSeeder`
- `ApprovalRuleSeeder`
- `PermissionSeeder`

Default seeded roles:
- `super_admin`
- `department_head`
- `president`
- `sales_channel_manager`
- `national_sales_manager`
- `treasury_manager`
- `treasury_staff`
- `finance_staff`

## Troubleshooting
- If emails are not sending:
  - verify queue worker is running
  - verify `MAIL_*` settings in `.env`
- If approvals do not route:
  - verify active rules in `approval_rules`
  - verify corresponding role steps in `approval_rule_steps`
- If dashboard/assets look broken:
  - run `npm run dev` (local) or `npm run build` (prod)
- If scheduled automations do not execute:
  - confirm server cron is calling `artisan schedule:run`

## Roadmap / Notes
- README previously referenced OCR and SAP integrations; current codebase does not show active service integration classes for these yet.
- If these integrations are planned, add dedicated docs under a future `/docs` folder (for API contracts, credentials, retry strategy, and failure handling).
