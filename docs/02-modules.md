# Modules Structure Documentation

## 1. Overview
This document describes the module structure of the system, focused on:
- Filament resources/pages/widgets (application UI modules)
- Domain services and queue jobs (business/process modules)
- Integration modules (mail, notifications, logs, scheduler)

The system is primarily a Filament admin application where each functional area is represented by a Resource and its Pages.

## 2. High-Level Module Layers

### 2.1 Presentation Layer
- `app/Filament/Resources/*`
- `app/Filament/Pages/*`
- `app/Filament/Widgets/*`

### 2.2 Domain/Application Layer
- `app/Services/*`
- `app/Enums/*`
- `config/approval_remarks.php`

### 2.3 Async/Integration Layer
- `app/Jobs/*`
- `app/Mail/*`
- Database notifications (`notifications` table)
- Activity logs (`activity_log`, `authentication_log`, `filament_email_log`)
- Scheduled tasks (`routes/console.php`)

## 3. Filament Panel Composition

Defined in `AdminPanelProvider`:
- Panel path: `/`
- Auth pages: custom `Login`, custom `Register`, custom `EditProfile`
- Dashboard page: `App\Filament\Pages\Dashboard`
- Core dashboard widgets:
  - `RequestCountOverviewStats`
  - `ReleaseAmountSummaryStats`
  - `MyReleaseNaturePercentageChart`
  - `MyApprovalDecisionPieChart`
  - `UnliquidatedCashRequestsTable`
  - `NotesWidget` (plugin)
- Plugins:
  - Spatie roles/permissions plugin
  - Filament Notes
  - Filament Apex Charts
  - Filament General Settings
  - Activity Log plugin
  - Authentication Log plugin
  - Filament Email plugin
- Middleware includes `ForceLogoutAfterRegistration` to block non-approved users.

## 4. Core Functional Modules

### 4.1 Cash Request Submission Module
Primary classes:
- `ActivityListResource` (not in nav)
- `ActivityListResource\Pages\CreateActivityListWithTable`
- `CashRequestResource` (entry and tracking)

Responsibilities:
- Build draft request and multiple activity items before final submission
- Validate amount cap for petty cash from active approval rule
- Prevent duplicate active request by same nature
- Submit request by setting:
  - `cash_requests.status = pending`
  - `cash_requests.status_remarks = Request Submitted`
- Initialize approval rows via `CashRequestApprovalFlowService`
- Notify approvers (department head + super admins)

Data modules used:
- `cash_requests`
- `activity_lists`
- `cash_request_approvals`
- media attachments (`media`)

### 4.2 Approval Queue Module
Primary classes:
- `ForApprovalRequestResource`
- `ForApprovalRequestResource\Pages\ViewForApprovalRequest`
- `CashRequestApprovalFlowService`

Responsibilities:
- Show only requests pending for current user's role(s)
- Apply step-by-step approval/rejection decisions
- Resolve next status remarks based on role and nature
- On final approval, dispatch email job and route to next queue
- Allow per-activity rejection inside a request and recalculate total

State effects:
- Approval row updated in `cash_request_approvals`
- `cash_requests` status/remarks and rejection reason updated
- Activity audit logs written

### 4.3 Finance Verification Module
Primary classes:
- `ForFinanceVerificationResource`
- `ForFinanceVerificationResource\Pages\ViewForFinanceVerification`

Responsibilities:
- Queue filter: `status = in progress` + `status_remarks = For Finance Verification`
- Require `voucher_no` on approval
- Move approved request to `For Payment Processing`
- Reject request with finance rejection remark
- Support activity-level rejection with amount recomputation

State effects:
- Approval path: remains `in progress`, remarks to payment processing
- Rejection path: status to `rejected`

### 4.4 Payment Processing (Treasury Processing) Module
Primary classes:
- `PaymentProcessResource`
- `PaymentProcessResource\Pages\ViewPaymentProcess`

Responsibilities:
- Queue filter: `status = in progress` + `status_remarks = For Payment Processing`
- Treasury workflow with role-sensitive actions:
  - override
  - treasury manager approval
  - treasury staff final approval with release schedule
- Disbursement setup (`check` or `payroll`) for cash advance
- Calculate due date using General Settings (`aging_field`, fallback 3 days)
- Create `for_cash_releases` row
- Dispatch `ApproveCashRequestByTreasuryJob`
- Notify treasury manager/staff and requestor

Permission-sensitive controls:
- `can-override-payment-process-request`
- role checks for treasury manager/staff

### 4.5 Cash Release Module
Primary classes:
- `ForCashReleaseResource`
- `ForCashReleaseResource\Pages\ViewForCashRelease`

Responsibilities:
- Queue source: `for_cash_releases` where linked request is approved for releasing
- Release action:
  - updates release record (`released_by`, `date_released`)
  - updates request status to `released`
  - creates `for_liquidations` record
  - dispatches `ReleaseCashRequestByTreasuryJob`
- Reject action:
  - sets request to `rejected` with treasury remark/reason
  - dispatches rejection job
- Supports activity-level rejection and recalculation

### 4.6 Liquidation Module
Primary classes:
- `CashRequestResource` liquidation action (requestor submission)
- `LiquidationService`
- `ForLiquidationResource`
- `ForLiquidationResource\Pages\ViewForLiquidation`

Responsibilities:
- Requestor submits liquidation receipts from own request list
- Service stores liquidation line items and media uploads
- Sets request remark to `Liquidation Receipt Submitted`
- Notifies treasury team
- Treasury liquidation page handles:
  - override
  - liquidate (final)
  - reject liquidation
- Final liquidation updates:
  - `status = liquidated`
  - `status_remarks = Liquidated`
  - `date_liquidated`

Permission-sensitive controls:
- `can-override-liquidation-receipt`

### 4.7 User Registration Approval Module
Primary classes:
- `Auth\Register`
- `CustomLogin`
- `UserApprovalResource`
- `UserApprovalResource\Pages\ViewUserApproval`
- `ForceLogoutAfterRegistration` middleware

Responsibilities:
- Accept registration with company email validation (`@mcasiafoodtrade.ph`)
- Hold user as pending until reviewed
- Approve/disapprove users with reason + audit logs
- Dispatch registration email jobs (confirmation/approved/rejected)
- Restrict login for non-approved users

### 4.8 Administration Modules

`DepartmentResource`
- Manage departments and department heads

`ApprovalRuleResource`
- Manage amount-based and nature-based approval matrix
- Manage approver roles per rule (`approval_rule_steps`)

`UserResource`
- Full admin user management (profile, status, roles, account status)

`AuthenticationLogResource`
- Read-only auth logs
- Visible to super admin only

## 5. Resource Registry

| Resource | Model | Navigation Group | Primary Purpose |
|---|---|---|---|
| `CashRequestResource` | `CashRequest` | Cash Requests | Requestor list/tracking/liquidation entry |
| `ForApprovalRequestResource` | `ForApprovalRequest` (`cash_requests`) | Cash Requests | Role-filtered approval queue |
| `ForFinanceVerificationResource` | `ForFinanceVerification` (`cash_requests`) | Cash Requests | Finance verification queue |
| `PaymentProcessResource` | `PaymentProcess` (`cash_requests`) | For Approval | Treasury payment/disbursement processing |
| `ForCashReleaseResource` | `ForCashRelease` | For Approval | Release scheduling and release actions |
| `ForLiquidationResource` | `ForLiquidation` | Cash Requests | Liquidation review/finalization |
| `ActivityListResource` | `ActivityList` | Hidden | Draft activity line-item builder |
| `UserApprovalResource` | `User` | Administrator | Pending registration approvals |
| `UserResource` | `User` | Administrator | Admin user management |
| `DepartmentResource` | `Department` | Administrator | Department management |
| `ApprovalRuleResource` | `ApprovalRule` | Administrator | Approval matrix management |
| `AuthenticationLogResource` | plugin model | Administrator | Authentication log viewer |

## 6. Services Modules

### `CashRequestApprovalFlowService`
Responsibilities:
- Resolve best approval rule by amount + nature
- Initialize `cash_request_approvals`
- Filter queue by approver role
- Apply approval/rejection transactionally
- Resolve remarks via `StatusRemarkResolver`

### `StatusRemarkResolver` and `ApprovalStatusResolver`
Responsibilities:
- Map permissions/roles to standardized status remarks
- Read mapping from `config/approval_remarks.php`
- Provide contextual remarks for approval, finance, treasury, and release

### `CancellationService`
Responsibilities:
- Cancel request from requestor side
- Persist cancellation reason
- Write activity log + UI notification

### `LiquidationService`
Responsibilities:
- Process liquidation receipts transactionally
- Update totals (`total_liquidated`, `total_change`, `missing_amount`)
- Store receipt files in media collection
- Notify treasury users

## 7. Queue Job Modules

| Job | Trigger Area | Purpose |
|---|---|---|
| `ConfirmRegistrationJob` | Registration | Send pending-registration confirmation email |
| `ApproveUserRegistrationJob` | User approval | Send account-approved email |
| `RejectUserRegistrationJob` | User approval | Send account-rejected email |
| `ApproveCashRequestJob` | Final approval step | Send cash request approved email |
| `RejectCashRequestJob` | Approval/finance/treasury rejection | Send request rejected email |
| `ApproveCashRequestByTreasuryJob` | Payment processing | Send approved-for-release email; set remark to `For Releasing` |
| `ReleaseCashRequestByTreasuryJob` | Cash release | Send released email; set remark to `For Liquidation` |

## 8. Mail Modules
Mailables in `app/Mail`:
- `ConfirmRegistrationMail`
- `UserApprovedMail`
- `UserRejectedMail`
- `ApproveCashRequestMail`
- `RejectCashRequestMail`
- `ApproveCashRequestByTreasuryMail`
- `ReleaseCashRequestByTreasuryMail`

These are dispatched through queue jobs and logged via Filament Email plugin.

## 9. Dashboard and Analytics Modules

### `RequestCountOverviewStats`
- Request totals by status with 12-month trends
- Scoped to all data for super admin/admin; personal scope otherwise

### `ReleaseAmountSummaryStats`
- Donut summary of released amount split (liquidated vs unliquidated)

### `MyReleaseNaturePercentageChart`
- Bar chart by nature (cash advance vs petty cash)
- Supports period filters (day/week/month/quarter/year)

### `MyApprovalDecisionPieChart`
- Approved vs rejected counts from `cash_request_approvals`

### `UnliquidatedCashRequestsTable`
- Tabular view of released but not liquidated requests with due aging

## 10. Scheduler Modules (Automation)
Defined in `routes/console.php`:

1. `update-for-liquidation-aging`
- Runs daily at `00:05` Asia/Manila
- Recomputes overdue `for_liquidations.aging`

2. `cancel-unclaimed-release-requests`
- Runs every 5 minutes
- Auto-cancels approved requests not claimed within release window
- Sets `status = cancelled`, `status_remarks = Unclaimed`

## 11. Cross-Cutting Modules

### Notification Module
- Uses Filament database notifications heavily between modules
- Includes actionable links to specific resource view/track pages

### Audit and Logging Module
- Activity logs for major transitions (approved/rejected/released/liquidated/override)
- Authentication logs for sign-in events
- Email logs for outbound communications

### Authorization Module
- Spatie roles/permissions
- `Gate::before` in `AuthServiceProvider` allows super admin override behavior
- Resource visibility/action availability depends on role and permission checks

## 12. Module Dependency Flow
1. Registration module creates pending users.
2. User approval module activates accounts.
3. Submission module creates and submits cash requests.
4. Approval module drives multi-step role approvals.
5. Finance verification module validates/forwards.
6. Payment processing module sets disbursement and release plan.
7. Cash release module releases funds and creates liquidation entry.
8. Liquidation module receives receipts and finalizes.
9. Dashboard modules read across workflow tables for analytics.

## 13. Notes and Technical Observations
- Several resources (`ForApprovalRequest`, `ForFinanceVerification`, `PaymentProcess`) are separate module views over the same `cash_requests` table.
- Workflow behavior is controlled more by `status` + `status_remarks` than by separate workflow tables.
- Module actions are tightly integrated with queue jobs and database notifications for user feedback and process continuity.

