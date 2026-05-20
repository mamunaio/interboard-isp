# INTERBOARD ISP SAAS — PROGRESS TRACKER

> Last updated: 2026-05-20
> Reference: `MASTER_BIBLE.md`

---

## Stack Status

| Component | Bible (Locked) | Actual (composer.json) | Status |
|---|---|---|---|
| Laravel | 12 | ^13.8 | :warning: DRIFT — needs decision |
| PHP | 8.3+ | 8.4.21 | :white_check_mark: Compatible |
| Livewire | v3 | ^4.3 | :warning: DRIFT — needs decision |
| Alpine.js | latest | via CDN/Vite | :white_check_mark: |
| Tailwind CSS | latest | ^4.0.0 | :white_check_mark: |
| PostgreSQL | default | .env default=sqlite | :warning: Switch .env default to pgsql |
| Redis | required | configured | :white_check_mark: |
| Horizon | required | ^5.46 | :white_check_mark: |
| Reverb | required | ^1.0 | :white_check_mark: |
| Sanctum | required | ^4.0 | :white_check_mark: |
| Vite | stable | ^8.0.0 (invalid) | :x: Should be ^6.0 |

### Decision Required
> Laravel 13 + Livewire 4 is what's currently in composer.json.
> Bible says Laravel 12 + Livewire 3.
> **Owner must decide**: update Bible to match current code, OR downgrade code to match Bible.

---

## Approved Billing Steps (1–21)

| Step | Name | Status | Evidence |
|---|---|---|---|
| 1 | Invoice Engine | :white_check_mark: DONE | `Invoice`, `InvoiceLineItem`, `InvoiceService`, `invoice_number_sequences`, `InvoiceTest` |
| 2 | Tax Engine | :white_check_mark: DONE | `TaxRule`, `TaxCalculationService`, `TaxRuleService`, `TaxEngineTest` |
| 3 | Wallet Ledger | :white_check_mark: DONE | `Wallet`, `LedgerEntry`, `WalletService`, `WalletLedgerTest` |
| 4 | Payment Allocation | :white_check_mark: DONE | `PaymentAllocation`, `PaymentAllocationService` (FIFO), `PaymentAllocationTest` |
| 5 | Billing Scheduler | :white_check_mark: DONE | `GenerateInvoicesCommand`, `BillingGenerationService`, `BillingSchedulerTest` |
| 6 | Suspension / Reactivation | :white_check_mark: DONE | `SuspensionEvent`, `SuspensionEvaluationService`, `SuspensionActionService`, `SuspensionReactivationTest` |
| 7 | Payment Intake / Receipt | :white_check_mark: DONE | `PaymentReceipt`, `PaymentReceiptService`, `PaymentIntake` Livewire, `PaymentReceiptTest` |
| 8 | Reversal / Adjustment | :white_check_mark: DONE | `ReversalEvent`, `ReversalAdjustmentService`, `PaymentAllocationReversal`, `ReversalAdjustmentTest` |
| 9 | Gateway Orchestration | :white_check_mark: DONE | `GatewayPaymentAttempt`, `GatewayOrchestrationService`, bKash/Nagad/SSLCommerz drivers, `GatewayOrchestrationTest` |
| **10** | **Reconciliation / Settlement** | :x: PENDING | No service, no model, no migration |
| **11** | **Collections / Dunning** | :x: PENDING | Nothing exists |
| **12** | **Reporting / Analytics** | :yellow_circle: PARTIAL | Dashboard Livewire exists, dedicated KPI report module missing |
| **13** | **Credit Control** | :x: PENDING | No exposure/credit-limit logic |
| **14** | **Dispute Management** | :x: PENDING | Nothing exists |
| **15** | **Audit / Compliance** | :yellow_circle: PARTIAL | `AuditLog`, `LogsActivity` trait, `AuditTimeline` — compliance controls missing |
| **16** | **Accounting Posting** | :x: PENDING | No chart of accounts, no journal entries, no posting rules |
| **17** | **Financial Period Close** | :x: PENDING | No period lock mechanism |
| **18** | **Financial Statements** | :x: PENDING | No P&L, no balance sheet |
| **19** | **Tax Compliance** | :x: PENDING | Tax engine exists but statutory liability/remittance missing |
| **20** | **Refund / Credit Memo** | :x: PENDING | `TransactionType::REFUND_CREDIT` enum exists — no service |
| **21** | **Plan Change / Proration** | :yellow_circle: PARTIAL | `SubscriptionScheduledChange` + `ServicePlan` exist — proration calculator missing |

### Summary: 9 done, 3 partial, 9 pending

---

## Future Roadmap Steps (22–30)

| Step | Name | Status | Evidence |
|---|---|---|---|
| 22 | Provisioning Sync | :white_check_mark: DONE | `MikroTikApiService`, `ProcessProvisioningJob`, `ServiceProvisioningEvent` |
| 23 | AAA / RADIUS / PPPoE | :white_check_mark: DONE | `radcheck`/`radacct`/`radreply` tables, `SessionManagementService`, `NetworkAaaIntegrationTest` |
| **24** | **Customer Lifecycle** | :yellow_circle: PARTIAL | Customer module exists — lifecycle workflow/state machine missing |
| **25** | **Work Orders** | :x: PENDING | Nothing exists |
| **26** | **Inventory** | :x: PENDING | Nothing exists |
| **27** | **Support / Ticketing** | :x: PENDING | Nothing exists |
| **28** | **Notifications** | :yellow_circle: PARTIAL | `NotificationRouter` + Email/SMS/WhatsApp adapters — templates/preferences missing |
| **29** | **CRM** | :x: PENDING | Nothing exists |
| **30** | **SaaS Governance** | :x: PENDING | Nothing exists |

### Summary: 2 done, 2 partial, 5 pending

---

## MVP Dashboard Phases

| Phase | Items | Status |
|---|---|---|
| 1 | Shell, sidebar, navbar, tenant badge, auth dropdown, Customers, Subscriptions, Invoices | :white_check_mark: DONE |
| 2 | Payment intake, Wallet UI, Allocation UI, Gateway attempts | :white_check_mark: DONE |
| 3 | Suspension, Reactivation, Reversals, Customer financial snapshot | :white_check_mark: DONE |
| 4 | Monitoring dashboard, Audit timeline, Search, Realtime notifications | :yellow_circle: MOSTLY DONE (Reverb wiring needs verification) |

---

## Core Infrastructure

| Component | Status | Evidence |
|---|---|---|
| Tenant Isolation | :white_check_mark: DONE | `BelongsToTenant` trait, `TenantScope`, `EnforceTenantIsolation` middleware |
| RBAC | :white_check_mark: DONE | `Role`, `Permission`, `ChecksRbac`, `PermissionCacheService` |
| Audit Logging | :white_check_mark: DONE | `AuditLog`, `LogsActivity` trait, `ProcessAuditLogJob` |
| Webhook System | :white_check_mark: DONE | `WebhookConfig`, `WebhookDispatcher`, `ProcessWebhookJob` |
| Settings Registry | :white_check_mark: DONE | `SettingsRegistry`, `Setting` model |
| OTP / Auth | :white_check_mark: DONE | `OtpService`, `OtpVerification`, `TrustedDevice`, Sanctum API |
| Notification Adapters | :white_check_mark: DONE | Email, SMS, WhatsApp adapters + `NotificationRouter` |

---

## Known Issues / Tech Debt

| # | Issue | Severity | Location |
|---|---|---|---|
| 1 | Scratch/debug files committed to repo root | Medium | `scratch_dump_livewire.php`, `scratch_read_db.php`, `resolve_debug.txt` |
| 2 | `seed_demo_tenant.php` with hardcoded `admin1234` password in root | High | Root |
| 3 | Duplicate migration timestamp `2026_05_19_000010_*` | Medium | `Billing/database/migrations/` |
| 4 | Root `database/migrations/` has provisioning+radius tables (should be in modules) | Low | `database/migrations/` |
| 5 | Web login flow missing (API-only auth, no Livewire login) | Medium | `routes/web.php` |
| 6 | `vite ^8.0.0` in package.json (nonexistent version) | Low | `package.json` |

---

## Recommended Execution Order (Next Steps)

```
IMMEDIATE (resolve before proceeding):
  - Stack drift decision (Laravel 12 vs 13, Livewire 3 vs 4)
  - Fix vite version in package.json
  - Remove scratch files / convert seed to proper seeder

THEN (Bible Steps sequential):
  Step 10 → 11 → 12 → 13 → 14 → 15(complete) → 16 → 17 → 18 → 19 → 20 → 21
  Step 24 → 25 → 26 → 27 → 28 → 29 → 30
```

---

## How to Use This File

**For AI Agents (Gemini / Claude / ChatGPT):**
1. Read `MASTER_BIBLE.md` first for rules and architecture.
2. Read this file to understand current state.
3. Pick the next `:x: PENDING` step in order.
4. Implement: Migration → Model → Service → Policy → Livewire UI → Feature Test.
5. Update this file after completing each step.

**For Human Review:**
- Any step marked `:yellow_circle: PARTIAL` needs completion before moving forward.
- Stack drift decisions block all future work.
