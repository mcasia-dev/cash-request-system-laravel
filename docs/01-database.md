# Database Structure Documentation

## 1. Overview
This document describes the database structure of the McAsia Foodtrade Cash Request System based on the current Laravel migrations and models.

It covers:
- Business tables used by core cash request workflows
- Package/framework tables used by authentication, queues, media, permissions, and logs
- Relationships and constraints
- Data flow across modules

## 2. Database Platform and Conventions
- Framework: Laravel 11 migrations
- Default local connection in `.env.example`: `sqlite` (production commonly uses MySQL)
- ID style: mostly `BIGINT UNSIGNED` auto-increment (`id`)
- Timestamps: most tables use `created_at` and `updated_at`
- Workflow states are stored using `enum` or `string` columns (for status/status remarks)

## 3. Core Business Tables

### 3.1 `users`
Primary user account table (extended from Laravel default).

| Column | Type | Nullable | Key | Notes |
|---|---|---|---|---|
| id | bigint | No | PK | Auto-increment |
| control_no | varchar | Yes | Unique | Format generated in model: `MCA-YYYY-####` |
| name | varchar | No |  | Auto-computed as first + last name |
| first_name | varchar | No |  |  |
| middle_name | varchar | Yes |  |  |
| last_name | varchar | No |  |  |
| position | varchar | Yes |  |  |
| email | varchar | No | Unique | Login email |
| email_verified_at | timestamp | Yes |  | Laravel default |
| contact_number | varchar | Yes |  |  |
| department_id | bigint unsigned | Yes |  | Logical FK to `departments.id` |
| signature_number | varchar | Yes |  | Random 12-char value generated in model |
| account_status | enum | Yes |  | `active`, `blocked`, `suspended` |
| status | enum | No |  | `pending`, `approved`, `disapproved` |
| review_by | bigint unsigned | Yes |  | Reviewer user id (logical self-reference) |
| review_at | datetime | Yes |  | Account review timestamp |
| reason_for_rejection | text | Yes |  | Reason for disapproved registration |
| password | varchar | No |  | Hashed |
| remember_token | varchar | Yes |  | Laravel default |
| created_at | timestamp | Yes |  |  |
| updated_at | timestamp | Yes |  |  |

### 3.2 `departments`
Department master list used by users.

| Column | Type | Nullable | Key | Notes |
|---|---|---|---|---|
| id | bigint | No | PK |  |
| department_name | varchar | No | Unique |  |
| department_head | varchar | Yes |  | Free-text name (not yet FK to users) |
| added_by | varchar | Yes |  | Creator/auditor text field |
| created_at | timestamp | Yes |  |  |
| updated_at | timestamp | Yes |  |  |

### 3.3 `cash_requests`
Central transaction table used across request, approval, finance, treasury, and liquidation modules.

| Column | Type | Nullable | Key | Notes |
|---|---|---|---|---|
| id | bigint | No | PK |  |
| user_id | bigint unsigned | No |  | Logical FK to `users.id` |
| request_no | varchar | No | Unique | Generated format: `REQ-YYYY-####` |
| activity_name | varchar | Yes |  | Initially non-null, later changed nullable |
| activity_date | date | Yes |  |  |
| activity_venue | text | Yes |  |  |
| purpose | text | Yes |  |  |
| nature_of_request | enum | Yes |  | `petty cash`, `cash advance` |
| requesting_amount | decimal | No |  | Requested amount |
| nature_of_payment | varchar | Yes |  |  |
| payee | varchar | Yes |  |  |
| payment_to | varchar | Yes |  |  |
| voucher_no | varchar | Yes |  | Added in finance/payment process flow |
| disbursement_type | enum | Yes |  | `check`, `payroll` |
| check_branch_name | varchar | Yes |  |  |
| check_no | varchar | Yes |  |  |
| cut_off_date | date | Yes |  | Payroll path |
| payroll_credit | integer | Yes |  | Payroll path |
| payroll_date | date | Yes |  | Payroll path |
| disbursement_added_by | bigint unsigned | Yes |  | Logical FK to `users.id` |
| bank_account_no | varchar | Yes |  |  |
| bank_name | varchar | Yes |  |  |
| account_type | varchar | Yes |  |  |
| cc_holder_name | varchar | Yes |  |  |
| cc_number | varchar | Yes |  |  |
| cc_type | varchar | Yes |  |  |
| cc_expiration | varchar | Yes |  |  |
| due_date | date | Yes |  | Liquidation due date |
| date_liquidated | datetime | Yes |  |  |
| date_released | datetime | Yes |  |  |
| status | enum | Yes |  | `pending`, `in progress`, `approved`, `rejected`, `cancelled`, `liquidated`, `released` |
| is_override | boolean | Yes |  | Manual override flag |
| is_approved_by_treasury_manager | boolean | No |  | Treasury approval marker |
| status_remarks | varchar | Yes |  | Detailed workflow state |
| reason_for_rejection | text | Yes |  |  |
| reason_for_cancelling | text | Yes |  |  |
| created_at | timestamp | Yes |  |  |
| updated_at | timestamp | Yes |  |  |

### 3.4 `cash_request_approvals`
Stores per-role approval actions for each cash request.

| Column | Type | Nullable | Key | Notes |
|---|---|---|---|---|
| id | bigint | No | PK |  |
| cash_request_id | bigint unsigned | No |  | Logical FK to `cash_requests.id` |
| step_order | integer | Yes |  | Supports sequential routing |
| role_name | varchar | Yes |  | Target approver role |
| approved_by | varchar | Yes |  | User id stored as string |
| status | enum | No |  | `pending`, `approved`, `declined` |
| acted_at | datetime | Yes |  | Approve/reject timestamp |
| created_at | timestamp | Yes |  |  |
| updated_at | timestamp | Yes |  |  |

### 3.5 `approval_rules`
Approval matrix header table.

| Column | Type | Nullable | Key | Notes |
|---|---|---|---|---|
| id | bigint | No | PK |  |
| nature | enum | No |  | `petty cash`, `cash advance` |
| min_amount | float | Yes |  | Range lower bound |
| max_amount | float | Yes |  | Range upper bound |
| is_active | boolean | No |  | Rule enabled flag |
| created_at | timestamp | Yes |  |  |
| updated_at | timestamp | Yes |  |  |

### 3.6 `approval_rule_steps`
Role steps linked to each approval rule.

| Column | Type | Nullable | Key | Notes |
|---|---|---|---|---|
| id | bigint | No | PK |  |
| approval_rule_id | bigint unsigned | No |  | Logical FK to `approval_rules.id` |
| role_name | varchar | Yes |  | Role slug (for example `department_head`) |
| step_order | integer | Yes |  | Default `1` |
| created_at | timestamp | Yes |  |  |
| updated_at | timestamp | Yes |  |  |

### 3.7 `for_cash_releases`
Release scheduling and release execution table.

| Column | Type | Nullable | Key | Notes |
|---|---|---|---|---|
| id | bigint | No | PK |  |
| cash_request_id | bigint unsigned | No |  | Logical FK to `cash_requests.id` |
| released_by | bigint unsigned | Yes |  | Logical FK to `users.id` |
| processed_by | bigint unsigned | Yes |  | Logical FK to `users.id` |
| remarks | text | Yes |  | Release notes |
| releasing_date | date | Yes |  |  |
| releasing_time_from | time | Yes |  |  |
| releasing_time_to | time | Yes |  | Used by auto-cancel job |
| date_processed | datetime | Yes |  |  |
| date_released | datetime | Yes |  |  |
| date_edited | datetime | Yes |  |  |
| edited_by | bigint unsigned | Yes |  | Logical FK to `users.id` |
| created_at | timestamp | Yes |  |  |
| updated_at | timestamp | Yes |  |  |

### 3.8 `for_liquidations`
Aggregated liquidation record per cash request.

| Column | Type | Nullable | Key | Notes |
|---|---|---|---|---|
| id | bigint | No | PK |  |
| cash_request_id | bigint unsigned | No |  | Logical FK to `cash_requests.id` |
| receipt_amount | integer | Yes |  | Aggregate receipts (legacy field name) |
| remarks | text | Yes |  |  |
| total_user | integer | Yes |  | Optional metric |
| total_liquidated | float | Yes |  | Sum of receipt amounts |
| total_change | float | Yes |  | Excess amount |
| missing_amount | float | Yes |  | Short amount |
| aging | integer | Yes |  | Days overdue |
| is_override | boolean | No |  | Override marker |
| created_at | timestamp | Yes |  |  |
| updated_at | timestamp | Yes |  |  |

### 3.9 `liquidation_receipts`
Individual receipt lines uploaded under a liquidation record.

| Column | Type | Nullable | Key | Notes |
|---|---|---|---|---|
| id | bigint | No | PK |  |
| liquidation_id | bigint unsigned | No | FK | References `for_liquidations.id`, cascade delete |
| receipt_amount | float | Yes |  |  |
| remarks | text | Yes |  |  |
| created_at | timestamp | Yes |  |  |
| updated_at | timestamp | Yes |  |  |

### 3.10 `activity_lists`
Supporting activity/history records tied to user and request.

| Column | Type | Nullable | Key | Notes |
|---|---|---|---|---|
| id | bigint | No | PK |  |
| user_id | bigint unsigned | No |  | Logical FK to `users.id` |
| cash_request_id | bigint unsigned | Yes |  | Logical FK to `cash_requests.id` |
| control_no | varchar | Yes |  | User control number snapshot |
| activity_name | varchar | Yes |  |  |
| activity_date | date | Yes |  |  |
| activity_venue | varchar | Yes |  |  |
| purpose | text | Yes |  |  |
| nature_of_request | varchar | Yes |  |  |
| requesting_amount | integer | No |  |  |
| status | varchar | No |  | Default `pending` |
| rejection_remarks | text | Yes |  |  |
| created_at | timestamp | Yes |  |  |
| updated_at | timestamp | Yes |  |  |

## 4. Authorization and Access Control Tables (Spatie Permission)
- `permissions`
- `roles`
- `model_has_permissions`
- `model_has_roles`
- `role_has_permissions`

Important notes:
- `roles.name + guard_name` is unique.
- `permissions.name + guard_name` is unique.
- Pivot tables use composite primary keys.
- Foreign keys to `roles` and `permissions` are enforced in pivots.

## 5. Media and File Metadata

### `media` (Spatie Media Library)
Stores file metadata for:
- User avatars (`profile` collection)
- Cash request attachments (`attachments` collection)
- Liquidation receipt files (`liquidation-receipts` collection)

Key columns:
- `model_type`, `model_id` (polymorphic owner)
- `collection_name`, `file_name`, `disk`, `size`, `mime_type`
- JSON columns: `manipulations`, `custom_properties`, `generated_conversions`, `responsive_images`

## 6. Notifications, Logs, and Auditing

### `notifications`
Laravel database notifications.
- Primary key: UUID `id`
- Morph target: `notifiable_type`, `notifiable_id`
- Payload: `data` (text JSON)
- Read marker: `read_at`

### `activity_log`
Spatie activity logs (with custom extra columns from migrations):
- Base columns include `description`, `subject_*`, `causer_*`, `properties`
- Added columns:
  - `event` (string)
  - `batch_uuid` (uuid)

### `authentication_log`
Authentication log entries from `rappasoft/laravel-authentication-log`.
Key fields:
- Polymorphic authenticatable target
- `ip_address`, `user_agent`, `location` JSON
- `login_at`, `logout_at`, `login_successful`, `cleared_by_user`

### `filament_email_log`
Email log table for Filament Email plugin.
Key fields:
- Sender/recipients: `from`, `to`, `cc`, `bcc`
- Content: `subject`, `text_body`, `html_body`, `raw_body`
- Debug info: `sent_debug_info`
- Added fields:
  - `attachments` (json)
  - `team_id` (nullable foreign id field, no FK constraint in migration)

## 7. Laravel Framework Support Tables

### Authentication/session tables
- `password_reset_tokens`
- `sessions`

### Cache tables
- `cache`
- `cache_locks`

### Queue tables
- `jobs`
- `job_batches`
- `failed_jobs`

These tables are standard Laravel operational infrastructure and are required by configured drivers (`SESSION_DRIVER=database`, `QUEUE_CONNECTION=database`, `CACHE_STORE=database` by default in `.env.example`).

## 8. Settings Table

### `general_settings`
Stores dynamic app/site settings from Filament General Settings plugin.

Key groups:
- Branding: `site_name`, `site_description`, `site_logo`, `site_favicon`, `theme_color`
- Support: `support_email`, `support_phone`
- SEO/Tracking: `google_analytics_id`, `posthog_html_snippet`, `seo_title`, `seo_keywords`, `seo_metadata`
- Email config: `email_settings`, `email_from_address`, `email_from_name`
- Social/extra: `social_network`, `more_configs`

## 9. Relationships Map

### Enforced foreign keys at database level
- `liquidation_receipts.liquidation_id` -> `for_liquidations.id` (cascade delete)
- Spatie permission pivot FKs:
  - `model_has_permissions.permission_id` -> `permissions.id`
  - `model_has_roles.role_id` -> `roles.id`
  - `role_has_permissions.permission_id` -> `permissions.id`
  - `role_has_permissions.role_id` -> `roles.id`

### Logical (application-level) relationships without explicit FK constraints
- `users.department_id` -> `departments.id`
- `cash_requests.user_id` -> `users.id`
- `cash_requests.disbursement_added_by` -> `users.id`
- `cash_request_approvals.cash_request_id` -> `cash_requests.id`
- `approval_rule_steps.approval_rule_id` -> `approval_rules.id`
- `for_cash_releases.cash_request_id` -> `cash_requests.id`
- `for_cash_releases.released_by|processed_by|edited_by` -> `users.id`
- `for_liquidations.cash_request_id` -> `cash_requests.id`
- `activity_lists.user_id` -> `users.id`
- `activity_lists.cash_request_id` -> `cash_requests.id`

## 10. Key Workflow Data Path
1. User account is created in `users` (initial `status = pending`).
2. User submits request to `cash_requests`.
3. Rule is resolved via `approval_rules` + `approval_rule_steps`.
4. Approval actions are saved in `cash_request_approvals`.
5. Finance/payment updates are written back to `cash_requests`.
6. Release schedule and release actions are saved in `for_cash_releases`.
7. Liquidation aggregate is saved in `for_liquidations`.
8. Individual receipt lines go to `liquidation_receipts` (+ files in `media`).
9. Notifications and audit trail are written to `notifications` and `activity_log`.

## 11. Data Integrity and Improvement Notes
- Many business relationships are not enforced with DB-level foreign keys.
- Consider adding explicit FKs for core tables to prevent orphan records.
- `cash_request_approvals.approved_by` is `string`; consider `unsignedBigInteger` for consistency with user IDs.
- Amount columns mix `integer`, `float`, and `decimal`; consider standardizing financial amounts to `decimal(15,2)`.
- Add indexes for frequently filtered fields:
  - `cash_requests.status`
  - `cash_requests.status_remarks`
  - `cash_requests.user_id`
  - `cash_request_approvals.cash_request_id`
  - `for_cash_releases.cash_request_id`
  - `for_liquidations.cash_request_id`
