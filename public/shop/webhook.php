<?php
// public/shop/webhook.php
// Stripe sends POST events here. Register this URL in your Stripe dashboard:
// https://dashboard.stripe.com/webhooks → Add endpoint → https://yourdomain.com/shop/webhook.php
// Events to listen for: checkout.session.completed, payment_intent.payment_failed

require_once __DIR__ . '/../../includes/bootstrap.php';

if (!STRIPE_ENABLED) {
    // 503 (not 200) so Stripe records the delivery as failed if a misconfigured
    // endpoint somehow points here. Stripe's retry policy will back off rather
    // than burning attempts.
    http_response_code(503);
    exit('Stripe integration is disabled on this deployment.');
}

// Webhooks must NOT have session started
$payload   = file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

if (!$payload || !$sigHeader) {
    http_response_code(400);
    exit('Missing payload or signature');
}

try {
    $event = StripeHelper::constructWebhookEvent($payload, $sigHeader);
} catch (Exception $e) {
    error_log('Stripe webhook signature failure: ' . $e->getMessage());
    http_response_code(400);
    exit('Webhook signature verification failed');
}

// Idempotency contract:
//   1. Try to claim the event with INSERT (PK on event_id serializes concurrent retries).
//   2. If insert fails with duplicate-key:
//        - row.processed_at IS NOT NULL → real duplicate; ack 200 and stop.
//        - row.processed_at IS NULL     → a previous attempt crashed mid-handler;
//          re-run the handler and stamp processed_at on success.
//   3. Wrap the handler + processed_at update in a transaction so a crash
//      leaves the row in the "claimed but unprocessed" state for the retry.
try {
    Database::insert('stripe_webhook_events', [
        'event_id' => $event->id,
        'type'     => $event->type,
    ]);
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        $existing = Database::fetchOne(
            "SELECT processed_at FROM stripe_webhook_events WHERE event_id = ?",
            [$event->id]
        );
        if ($existing && $existing['processed_at'] !== null) {
            http_response_code(200);
            exit('duplicate-skipped');
        }
        // else: fall through and re-run the handler for the unfinished row.
    } else {
        error_log('Stripe webhook ledger insert failed: ' . $e->getMessage());
        http_response_code(500);
        exit('Webhook ledger error');
    }
}

try {
    Database::transaction(function () use ($event) {
        switch ($event->type) {

            case 'checkout.session.completed':
                handleCheckoutCompleted($event->data->object);
                break;

            case 'payment_intent.payment_failed':
                $pi = $event->data->object;
                Database::query(
                    "UPDATE orders SET status = 'cancelled' WHERE stripe_payment_intent = ?",
                    [$pi->id]
                );
                break;

            default:
                // Ignore other events — but still mark processed so we don't
                // re-fetch them on retry.
                break;
        }

        Database::query(
            "UPDATE stripe_webhook_events SET processed_at = CURRENT_TIMESTAMP WHERE event_id = ?",
            [$event->id]
        );
    });
} catch (\Throwable $e) {
    // Handler crashed — leave processed_at NULL so Stripe's retry re-runs us.
    error_log('Stripe webhook handler failed for ' . $event->id . ': ' . $e->getMessage());
    http_response_code(500);
    exit('Webhook handler error');
}

// Mail goes out AFTER we've ack'd the work, not inside the transaction.
// PHP mail() can take seconds; running it inside the webhook would risk
// exceeding Stripe's 10s deadline and cascading retries.
if ($event->type === 'checkout.session.completed') {
    $session = $event->data->object;
    $order = Database::fetchOne(
        "SELECT * FROM orders WHERE stripe_session_id = ?",
        [$session->id]
    );
    if ($order && $order['status'] === 'paid') {
        try {
            Mailer::notifyOwnerOfSale($order);
            Mailer::sendCustomerReceipt($order);
        } catch (\Throwable $e) {
            error_log('Stripe webhook mail dispatch failed for order ' . $order['id'] . ': ' . $e->getMessage());
        }
    }
}

http_response_code(200);
echo 'ok';

// -----------------------------------------------------------------------

function handleCheckoutCompleted(object $session): void {
    $existing = Database::fetchOne(
        "SELECT id, status FROM orders WHERE stripe_session_id = ?",
        [$session->id]
    );

    if (!$existing) {
        error_log('Stripe webhook: order not found for session ' . $session->id);
        return;
    }

    // Only transition pending orders. If the order is already paid/shipped/refunded,
    // a duplicate event is being delivered for an order whose lifecycle has moved on
    // — do not silently overwrite that state.
    if ($existing['status'] !== 'pending') {
        return;
    }

    $customerDetails  = $session->customer_details ?? null;
    $shippingDetails  = $session->shipping_details ?? null;
    $shippingAddress  = $shippingDetails->address ?? null;

    $updateData = [
        'status'                => 'paid',
        'stripe_payment_intent' => $session->payment_intent ?? null,
        'customer_name'         => $customerDetails->name ?? null,
        'customer_email'        => $customerDetails->email ?? null,
        'shipping_line1'        => $shippingAddress->line1 ?? null,
        'shipping_line2'        => $shippingAddress->line2 ?? null,
        'shipping_city'         => $shippingAddress->city ?? null,
        'shipping_state'        => $shippingAddress->state ?? null,
        'shipping_postal_code'  => $shippingAddress->postal_code ?? null,
        'shipping_country'      => $shippingAddress->country ?? null,
    ];

    Database::update('orders', $updateData, 'stripe_session_id = :stripe_session_id', [
        'stripe_session_id' => $session->id,
    ]);

    // Atomic stock decrement: combining the quantity update and the sold-flag
    // flip into a single statement closes the race where two concurrent
    // checkouts could both leave the product in 'available' status at qty 0.
    // MySQL evaluates SET clauses left-to-right, so the second clause sees the
    // already-decremented quantity.
    $productId = $session->metadata->product_id ?? null;
    $quantity  = (int)($session->metadata->quantity ?? 1);

    if ($productId) {
        Database::query(
            "UPDATE products
                SET quantity = GREATEST(0, quantity - ?),
                    status   = IF(quantity = 0, 'sold', status)
              WHERE id = ?",
            [$quantity, $productId]
        );
    }
}
