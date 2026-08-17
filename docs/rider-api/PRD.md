# Aangan Dairy — Rider App: Product Requirements Document

This document describes **what the rider mobile app needs to do and why**. For
exact request/response contracts, use the companion
[README.md](./README.md) and the
[Postman collection](./Aangan-Dairy-Rider-API.postman_collection.json) — this
PRD intentionally doesn't repeat that detail.

## 1. Overview

Aangan Dairy is a dairy delivery business running Cash-on-Delivery (COD)
orders through Shopify. Orders sync automatically into the admin system, get
confirmed and assigned to a rider by dispatch staff, and the rider then
carries out the physical delivery using this app.

The rider app is a **field execution tool**, not a general storefront or
admin app. Its entire job is: show a rider what to deliver today, let them
report progress as they work, capture proof it happened, and show them what
cash they're holding.

## 2. Who uses it

Single persona: **the rider**. Using a phone, on the road, often with
imperfect connectivity, moving between deliveries throughout the day. They
don't manage their own schedule — dispatch staff assign work to them from the
admin dashboard. The app should assume the rider is busy and moving, not
sitting at a desk.

## 3. Goals

The app must let a rider:

1. Log in and stay logged in for their shift.
2. See what's been assigned to them today, and open any one for full detail.
3. Move a delivery through its lifecycle as they physically do the work.
4. Prove a delivery happened before it counts as complete.
5. Handle multiple parcels picked up in one trip without repetitive taps.
6. Be found — the app should report location periodically while working.
7. Get notified the moment something new is assigned to them, without
   needing to keep the app open and polling.
8. See their own running cash balance — what they're currently holding of
   the company's money, and what they've earned.

## 4. Core user flows

### 4.1 Login & session

Rider enters phone-registered email + password (real email addresses aren't
always available for every rider — confirm current credential format with
the backend team before building the login screen). On success, the app gets
a token to send on every future request, plus the rider's basic profile.

**Deactivation is immediate.** If dispatch deactivates a rider mid-shift
(suspected issue, end of employment, etc.), their very next API call — not
just their next login — will fail with a 403. Design the app to catch this
gracefully (clear message, force back to login) rather than treating it as a
generic error.

There's no token refresh flow. If any request comes back `401`, send the
rider back to the login screen — don't try to silently recover.

### 4.2 Today's deliveries

A rider only ever sees orders assigned to them — never anyone else's. This
list is populated entirely by dispatch staff; **there is no "claim a
delivery" action** in this app. The rider's job starts once something
appears in their list, not before.

Support filtering by status so the rider can focus on what's actionable
right now (e.g. "show me what I still need to pick up" vs. "what's already
out for delivery").

### 4.3 The delivery lifecycle

```
assigned → picked_up → out_for_delivery → delivered
              ↓              ↓
              ├──→ failed  (retryable — dispatch can reassign)
              └──→ returned (terminal — goods never left intact)
```

Each step is a deliberate action the rider takes, not automatic. Design the
UI so the current stage is always obvious, and only the next valid action(s)
are presented — a rider shouldn't be able to tap "Delivered" on something
that hasn't been picked up yet (the server blocks it, but the app shouldn't
even offer it).

**`failed` vs. `returned` are alternatives, not a sequence.** Both are
available from the same in-progress states. Use `failed` when the delivery
attempt didn't work out but the parcel's fate is still open (customer
unreachable, wrong address — dispatch may send it back out). Use `returned`
when the rider is turning back with the goods intact and nothing further
will happen to this order. Don't build a UI that implies you fail first,
then return — they're two different endings to the same in-progress
delivery.

**Failed is not a dead end.** If dispatch reassigns a failed delivery — to
the same rider or someone else — it comes back into the assigned rider's
list exactly like a brand-new assignment, including a push notification. The
app doesn't need special handling for "this was previously failed" — treat
it the same as any other assigned delivery.

### 4.4 Proof of delivery

Before a delivery can be marked complete, the rider must provide **at least
one** of: a photo, or a captured signature. Design the "Delivered" flow so
this is front-and-center — a rider shouldn't reach a dead-end validation
error after already filling out everything else. Both can be provided if
useful; neither is inherently preferred over the other.

### 4.5 Bulk actions

When a rider collects several parcels from the warehouse in one trip, or
heads out with a batch already picked up, they shouldn't have to tap through
each order individually. Support multi-select for "mark picked up" and "mark
out for delivery" so one action can cover several orders at once. The result
will tell you, per order, whether it succeeded or was skipped (and why) —
surface that clearly rather than assuming an all-or-nothing outcome.

### 4.6 Location tracking

The app should report the rider's location periodically while active on a
delivery — roughly every 30–60 seconds is the target cadence. This powers
"where's my rider" visibility for dispatch, not turn-by-turn navigation for
the rider themselves. Don't over-report faster than this cadence; every
report is retained indefinitely.

### 4.7 Push notifications

Register the device for push right after login. A rider should be notified
the moment dispatch assigns them a delivery — whether it's a brand-new
assignment or a reassignment after a failed attempt, it's the same
notification. Handle the case where re-registering (app reinstall, token
refresh) may silently replace whatever was registered before — a rider is
assumed to use one device for the app.

### 4.8 Wallet

Read-only for the rider — nothing here is something they submit or adjust
themselves. Two things move this balance automatically as they work:
delivering an order with cash owed adds to it (they're now holding company
money), and their per-delivery earning subtracts from it (offsetting what
they owe). **A positive balance means the rider is holding cash they need to
hand over — it is not "money owed to the rider."** Get this framing right in
the UI; a naive "wallet" label with a big positive number could easily read
backwards to a rider glancing at their phone. Consider explicit labels like
"Cash to hand in" rather than a bare balance figure.

Settling that cash, or getting paid out earnings, happens on the admin side
— not from this app. The rider will see those settlements show up in their
transaction history after the fact, but can't initiate them.

## 5. Business rules & edge cases to design for

- **Cancelled orders stop dead.** If an order gets cancelled on the Shopify
  side while it's already assigned to a rider, no further progress is
  allowed on it from this point on — the rider will see it can't be
  advanced. This can happen mid-delivery, not just before assignment.
- **A rider only ever acts on their own deliveries** — every action is
  scoped server-side; there's no way to see or affect another rider's work.
- **Bulk actions are forgiving; single-order actions are strict.** Acting on
  an order that isn't yours: a single-order request errors immediately, a
  bulk request just skips that one item with a reason. Build your error
  handling to match — don't treat a bulk "skipped" entry as a hard failure.

## 6. Explicitly out of scope (don't build for these)

- **Multi-device support** — one FCM token per rider; a second device
  silently takes over from the first. Not solved server-side today.
- **Server-side offline queue** — if the app is offline, queuing and retry
  of status updates / location pings is entirely the app's responsibility.
  The server has no concept of "pending sync."
- **Rider-initiated wallet actions** — no request-payout, no self-adjustment.
  Purely a read-only display.
- **Rider-initiated delivery claiming** — riders never pick their own work;
  it's always assigned to them.
- **Token refresh / long-lived sessions beyond the token itself** — a 401
  means log in again, full stop.

## 7. Reference

- [README.md](./README.md) — endpoint-by-endpoint technical reference,
  request/response shapes, error format, status codes.
- [Postman collection](./Aangan-Dairy-Rider-API.postman_collection.json) —
  importable, runnable examples for every endpoint.
