# INTERBOARD ISP SAAS — MASTER EXECUTION BIBLE

## Purpose

Single source of execution truth for building the Interboard ISP SaaS platform using the locked architecture and technology stack.

---

## Locked Technology Stack

| Component | Version |
|---|---|
| Laravel | 12 |
| PHP | 8.3+ |
| TALL Stack | Livewire v3 + Alpine.js + Tailwind CSS |
| Database | PostgreSQL |
| Cache / Queue | Redis |
| Queue Dashboard | Laravel Horizon |
| WebSockets | Laravel Reverb |
| Network API | MikroTik RouterOS API |
| Money Arithmetic | BCMath (no floats) |

---

## Global Non-Negotiable Rules

1. **Tenant isolation is mandatory.**
2. **Wallet is NOT accounting.** (Step 16 owns accounting)
3. **No float arithmetic for money.** (BCMath only, scale 4)
4. **Compensating entries instead of destructive financial mutation.**
5. **No hidden balances / ghost credit states.**
6. **Queue-first architecture for external integrations.**
7. **Reuse approved domain services.**
8. **No fake UI placeholders.**
9. **No retroactive mutation into closed periods.** (Step 17 governs)

---

## Architecture Ownership Boundaries

| Owner | Responsibility |
|---|---|
| Step 16 | Accounting (double-entry posting) |
| Step 17 | Financial period governance (locking, close) |
| Step 19 | Statutory tax compliance |
| Step 20 | Cash refunds / credit memo |
| Billing truth | Service entitlement state |

---

## Approved Billing Architecture (Steps 1–21)

### Step 1 — Invoice Engine
Invoice lifecycle, statuses, tax integration, tenant-scoped invoice generation.

### Step 2 — Tax Engine
Operational tax calculation, invoice tax rules, deterministic tax logic.

### Step 3 — Wallet Ledger
Immutable wallet ledger, balance derivation, transaction auditability.

### Step 4 — Payment Allocation
Receipt-to-invoice allocation, deterministic FIFO allocation visibility.

### Step 5 — Billing Scheduler
Recurring invoice generation, billing anchors, automated jobs.

### Step 6 — Suspension / Reactivation
Service suspension and restoration governance.

### Step 7 — Payment Intake / Receipt
Idempotent payment intake, immutable receipt truth.

### Step 8 — Reversal / Adjustment
Compensating reversals, adjustment controls, allocation unwind.

### Step 9 — Gateway Payment Orchestration
Gateway attempts, callbacks, payment state machine.

### Step 10 — Reconciliation / Settlement
Settlement matching and mismatch workflows.

### Step 11 — Collections / Dunning
Overdue collection escalation workflows.

### Step 12 — Reporting / Analytics
Operational billing reporting and KPIs.

### Step 13 — Credit Control
Exposure governance and credit controls.

### Step 14 — Dispute Management
Dispute workflows and resolution audit trails.

### Step 15 — Audit / Compliance Controls
Audit visibility, traceability, compliance governance.

### Step 16 — Accounting Posting
Double-entry accounting ownership. Chart of accounts, journal entries, posting rules.

### Step 17 — Financial Period Close
Period locking and mutation governance.

### Step 18 — Financial Statements
P&L, balance sheet, management reporting.

### Step 19 — Tax Compliance
Statutory liability governance and remittance controls.

### Step 20 — Refund / Credit Memo
Refund orchestration, wallet credits, credit memo governance.

### Step 21 — Plan Change / Proration
Subscription mutation, deterministic proration, no retroactive mutation.

---

## Future Roadmap (Steps 22–30)

### Step 22 — Provisioning Sync
MikroTik queue/profile sync, entitlement enforcement.

### Step 23 — AAA / RADIUS / PPPoE
FreeRADIUS integration, radcheck/radreply/radacct, session management.

### Step 24 — Customer Lifecycle
Onboarding workflow, status transitions, churn management.

### Step 25 — Work Orders
Installation, maintenance, field technician dispatch.

### Step 26 — Inventory
ONU/Router/Cable tracking, stock management.

### Step 27 — Support / Ticketing
Customer tickets, SLA tracking, resolution workflows.

### Step 28 — Notifications
Template-driven SMS/Email/WhatsApp, preference center, delivery tracking.

### Step 29 — CRM
Lead management, sales pipeline, customer engagement.

### Step 30 — SaaS Governance
Tenant plan enforcement, usage metering, SaaS self-billing.

---

## Extended Roadmap (Tier 2 — Post Bible Steps)

| Step | Feature | Depends On |
|---|---|---|
| 31 | OLT / GPON Integration | Step 23 |
| 32 | Field Ops / Technician Dispatch | Step 25 |
| 33 | SSO / OAuth / SAML | Auth module |
| 34 | Promo / Campaign Engine | Step 29 |
| 35 | Affiliate / Referral System | Step 29 |

---

## Tier 3 — Full ERP (Deferred to v2.0)

- Budgeting / Forecasting
- Procurement / Accounts Payable
- Payroll / HR
- Fixed Asset Accounting
- Consolidation / Multi-entity Accounting

> These are enterprise ERP concerns. They should NOT be built until Steps 1–30 are production-stable.

---

## MVP Dashboard Build Roadmap

### Phase 1
- Dashboard shell + Sidebar + Top navbar
- Tenant badge + Auth dropdown
- Customers index
- Subscriptions index
- Invoices index

### Phase 2
- Payment intake
- Wallet UI
- Allocation UI
- Gateway attempts

### Phase 3
- Suspension
- Reactivation
- Reversals
- Customer financial snapshot

### Phase 4
- Monitoring dashboard
- Audit timeline
- Global search
- Realtime notifications (Reverb/Echo)

---

## Browser Testing Checklist

1. Create customer
2. Create plan
3. Assign subscription
4. Generate invoice
5. Receive payment
6. Verify allocation
7. Verify wallet
8. Suspend subscription
9. Reactivate subscription
10. Reverse payment
11. Verify audit
12. Verify dashboard metrics

---

## Execution Rules (for AI Agents)

1. **Implement actual code, not plans.**
2. **Do not redesign approved architecture.**
3. **Do not replace locked stack.**
4. **Preserve tenant scoping on all models/queries.**
5. **Use queue jobs for external integrations.**
6. **Prefer Livewire direct orchestration over SPA patterns.**
7. **BCMath for ALL monetary calculations — no exceptions.**
8. **Every new module must have: Model, Service, Policy, Migration, Livewire UI, Feature Test.**
9. **Read `PROGRESS.md` before starting any step to understand what's done.**
10. **Do not skip steps. Execute sequentially unless told otherwise.**
