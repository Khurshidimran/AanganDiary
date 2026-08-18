# Aangan Dairy — Rider Mobile App API

This is the API for the rider delivery mobile app. It's a separate, token-based
API from the admin web dashboard — rider accounts cannot log into the admin
portal, and admin accounts cannot log into this API.

**Postman collection**: [`Aangan-Dairy-Rider-API.postman_collection.json`](./Aangan-Dairy-Rider-API.postman_collection.json)
— import directly into Postman. Every request is documented inline (Postman's
"Documentation" tab per request) with example bodies/responses.

## Base URL

All endpoints are under `/api/v1/rider`. Set the `base_url` collection
variable to wherever the app is deployed, e.g. `https://admin.aangandairy.com`.

## Authentication

Bearer token via [Laravel Sanctum](https://laravel.com/docs/sanctum). Flow:

1. `POST /login` with `email` + `password` → returns a `token`
2. Send that token on every subsequent request: `Authorization: Bearer <token>`
3. `POST /logout` revokes it

Tokens don't expire on a timer — they're valid until the rider logs out (or an
admin removes the rider). There's no refresh-token flow; if a request ever
comes back `401 Unauthenticated`, send the user back to the login screen.

Only accounts with a rider profile can obtain a token here — an Admin's
credentials will be rejected with `422` and a clear message, not `401`.

## Error format

Standard Laravel JSON error shape throughout:

```json
{ "message": "Human-readable error." }
```

Validation errors additionally include an `errors` object keyed by field:

```json
{
  "message": "The latitude field must be between -90 and 90.",
  "errors": { "latitude": ["The latitude field must be between -90 and 90."] }
}
```

| Status | Meaning |
|---|---|
| 401 | Missing/invalid/revoked token |
| 403 | Authenticated, but not a rider account, a rider account that's been deactivated, or a delivery that isn't assigned to you |
| 422 | Validation failure, or the delivery isn't in the right state for that action (e.g. marking "delivered" before "out for delivery") |
| 429 | Rate limited (login only — 5 attempts/minute per IP) |

Deactivation takes effect immediately — if a rider's account is set to
inactive while they still hold a valid token, their very next request gets a
403, not just their next login attempt.

## Rider status: online, check-in, and the main-screen dashboard

Three related but **independent** concepts — don't conflate them:

- **Online/offline** (`POST /online`, `POST /offline`) — a simple availability
  flag for the main-screen switch. No location involved, doesn't affect
  anything else server-side. A rider can be online without being checked in.
- **Check-in/check-out** (`POST /check-in`, `POST /check-out`) — this is what
  actually matters for getting work. Check-in sends the device's current GPS
  coordinates; the server verifies you're within **200 meters** of your
  assigned warehouse before accepting it. Only checked-in riders show up on
  the dispatch board for new assignments — if a rider isn't checked in,
  dispatch staff can't assign anything to them at all. Check-out has no
  location requirement and doesn't affect deliveries you already have.
- **Dashboard** (`GET /dashboard`) — one call for everything the main
  screen's summary cards need: `is_online`, `is_checked_in`, `wallet_balance`,
  `assigned_orders_count` (orders in the `assigned` state specifically, not
  the broader in-progress set), and `done_today_count` (delivered today,
  server date). Call this on app open and after check-in/check-out to refresh
  the cards.

A rider must physically be at the warehouse to check in — this can't be
faked from across town, so build the check-in flow assuming GPS accuracy
matters (request high-accuracy location, not a cached/low-power fix).

## Delivery status lifecycle

```
assigned → picked_up → out_for_delivery → delivered
              ↓              ↓
              └────→ failed ←┘  → (reassigned) → assigned again
              └────→ returned  [terminal]
```

A dispatcher assigns orders to you from the admin dashboard — that's the only
way a delivery enters your list; there's no "claim a delivery" endpoint. From
there, you drive it forward through `picked-up` → `out-for-delivery` →
`delivered`.

At any point from `assigned`, `picked_up`, or `out_for_delivery`, you can
instead call `failed` (with a reason) or `returned` — these are **alternatives
to each other, not a sequence**:
- `failed` — the delivery attempt didn't succeed but the parcel may still be
  out with you; not terminal — a dispatcher can reassign it, which puts it
  straight back to `assigned` in your list and sends you the same push
  notification as a brand-new assignment.
- `returned` — you're turning back with the goods intact (releases the
  allocated stock back to inventory); this is terminal, no further action is
  possible on this order after `returned`. It's not reachable from `failed` —
  it's called directly from the in-progress states instead.

## Endpoints at a glance

| Method | Path | Purpose |
|---|---|---|
| POST | `/login` | Get a token |
| POST | `/logout` | Revoke the current token |
| GET | `/dashboard` | Main-screen summary: online/check-in status, wallet, counts |
| POST | `/online` / `/offline` | Toggle the main-screen availability switch |
| POST | `/check-in` | Location-verified — required to be assignable on the dispatch board |
| POST | `/check-out` | Stop being assignable; existing deliveries unaffected |
| GET | `/deliveries` | List your assigned deliveries (`?status=` to filter) |
| GET | `/deliveries/{order}` | One delivery's full detail |
| POST | `/deliveries/checkout` | Record your planned delivery route order — `{"order_ids": [...]}` |
| POST | `/deliveries/{order}/picked-up` | Mark picked up |
| POST | `/deliveries/{order}/out-for-delivery` | Mark en route |
| POST | `/deliveries/picked-up/bulk` | Mark several picked up in one call — `{"order_ids": [...]}` |
| POST | `/deliveries/out-for-delivery/bulk` | Mark several en route in one call — `{"order_ids": [...]}` |
| POST | `/deliveries/{order}/delivered` | Mark delivered — requires at least one of `photo`/`signature` |
| POST | `/deliveries/{order}/failed` | Mark failed — requires `reason` |
| POST | `/deliveries/{order}/returned` | Mark returned — goods intact, releases allocated stock |
| GET | `/wallet` | Balance + transaction ledger |
| POST | `/location` | Send a GPS ping |
| POST | `/device-token` | Register/update the FCM push token |

## Bulk picked-up / out-for-delivery

If a rider collects several parcels from the warehouse in one trip, don't call
`picked-up` once per order — use `POST /deliveries/picked-up/bulk` with
`{"order_ids": ["uuid1", "uuid2", ...]}` instead (same idea for
`/deliveries/out-for-delivery/bulk` when leaving with a batch already marked
picked up). Both always return `200` and account for every id you sent,
partitioned into two arrays — nothing is ever silently dropped:

```json
{
  "succeeded": [ { "id": "uuid1", "order_number": "#1001", "delivery_status": "picked_up", "...": "...full order object..." } ],
  "skipped": [
    { "id": "uuid2", "reason": "currently delivered" },
    { "id": "uuid3", "reason": "not found or not assigned to you" }
  ]
}
```

`succeeded` entries are full order objects (same shape as Get Delivery Detail)
so you can update local state directly without a follow-up `GET` per order.
Check `skipped` to know which ones didn't go through and why — don't infer
success purely from the `200` status.

## Checkout — arranging your delivery route

Once you've got your assigned orders loaded and decided what order you want
to deliver them in, call `POST /deliveries/checkout` with `order_ids` listing
every order in your chosen sequence:

```json
{ "order_ids": ["uuid-stop-1", "uuid-stop-2", "uuid-stop-3"] }
```

This **only records the sequence** — it does not touch `delivery_status`. You
still call Mark Picked Up / Mark Out For Delivery / Mark Delivered yourself
on each order as you actually work through it; checkout just tells the app
(and the admin dashboard) what order you planned. Response:

```json
{
  "route": [ { "...": "full order objects, sorted by route_sequence" } ],
  "start_order": { "...": "route[0] — your first stop" }
}
```

Every order object everywhere in this API (list, detail, bulk responses)
also carries its `route_sequence` from now on, so you can re-render the
planned order without calling checkout again. All submitted ids must belong
to you — if any don't, the whole request is rejected with a 422 rather than
partially applying.

## Notes for the mobile team

- **Proof of delivery** (`/delivered`): send `photo` and/or `signature` as
  `multipart/form-data` files, not base64 in JSON. **At least one of the two
  is required** — the request is rejected with a 422 if both are missing.
  Photo max 5MB (jpeg/png), signature max 2MB — export your signature-pad
  canvas as a PNG blob and upload it like a photo.
- **COD and earnings are automatic** — marking an order `delivered` credits
  your wallet for any COD it was carrying and your per-delivery rate. There's
  nothing to submit manually for that; just check `/wallet` to see it reflected.
- **Location pings**: ping every 30–60 seconds while active. Every ping is
  retained (full history, not just "last known position"), so don't ping much
  more frequently than that or the data volume grows unnecessarily. This is
  the same endpoint for both general background tracking and building a
  during-delivery trail — there's no separate per-delivery location endpoint;
  pings aren't tagged to a specific order, they're just a continuous history
  for this rider.
- **Push notifications**: register your FCM token via `/device-token` right
  after login, and again whenever Firebase's SDK issues a refreshed token.
  You'll receive a push whenever a dispatcher assigns you a delivery — both a
  brand-new assignment and a reassignment after a `failed` attempt trigger the
  same push (`data.type: "delivery_assigned"`, `data.order_id` in the payload).
  Only one device token is stored per rider — registering a new one replaces
  the old, so push stops on the previous device.
- **No offline queue on the server side** — if the app goes offline, queue
  status-transition and location-ping requests client-side and retry when
  connectivity returns. The API has no concept of "pending sync."
