# Stock Protection for PayPal Payments

## Problem

When a customer clicks the PayPal button, the shop creates an order and reduces stock immediately.
However, the actual payment only completes after the customer confirms in the PayPal popup or redirect flow.
If the customer abandons the process (browser crash, timeout, back button), the stock remains blocked
by a `NOT_FINISHED` order that will never be paid.

## Solution: OXID Basket Reservation

OXID has a built-in basket reservation mechanism that temporarily reserves stock and automatically
releases it when the reservation expires. This is the recommended approach to prevent stock blockage.

### How it works

- When enabled, stock is reserved (not permanently reduced) during the checkout process
- Reservations expire after a configurable timeout
- Cleanup runs automatically on every shop request via `BasketComponent::init()` — no cron job needed
- Expired reservations restore the stock to its original level

### Activation

1. Go to **Admin → Master Settings → Core Settings → Settings tab → Further settings**
2. Enable **"Use basket reservations"** (`blPsBasketReservationEnabled`)
3. Set the timeout (`iPsBasketReservationTimeout`) — recommended: **1200 seconds** (20 minutes)

This gives customers enough time to complete the PayPal flow while ensuring stock is released
promptly when a checkout is abandoned.

### Interaction with Race Condition Fixes

The PayPal module uses `SELECT ... FOR UPDATE` with database transactions in `finalizeOrder`
to prevent race conditions where two customers purchase the last item simultaneously.
Basket reservation complements this by handling the "abandoned checkout" scenario:

| Scenario                          | Protected by                    |
|-----------------------------------|---------------------------------|
| Two customers buy the last item   | `FOR UPDATE` + DB transaction   |
| Customer abandons PayPal checkout | Basket reservation timeout      |
| Old NOT_FINISHED orders           | Webhook cleanup (`cancelPayPalOrder`) |

## Webhook Cleanup

In addition to basket reservation, the module's webhook handler automatically cleans up
stale `NOT_FINISHED` PayPal orders via `OrderRepository::cleanUpNotFinishedOrders()`.

This uses `cancelPayPalOrder()` which provides:
- **Safety guard**: Never cancels orders that are already successfully paid (`oxtransid` set)
- **Hard delete**: Orders without an order number are deleted entirely (reduces clutter)
- **Status marking**: Orders with an order number are marked as `oxtransstatus = 'ERROR'`
- **Session cleanup**: Clears the PayPal session data

The cleanup is triggered on every webhook call and can be configured in the module settings:
- Enable/disable automatic cleanup
- Configure the minimum age (in minutes) before an order is considered stale
