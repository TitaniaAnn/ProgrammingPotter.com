<?php
// public/shop/webhook.php
// Stripe sends POST events here. Register this URL in your Stripe dashboard:
// https://dashboard.stripe.com/webhooks → Add endpoint → https://yourdomain.com/shop/webhook.php
// Events to listen for: checkout.session.completed, payment_intent.payment_failed

require_once __DIR__ . '/../../includes/bootstrap.php';

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

// Idempotency: every Stripe event has a unique event->id. Inserting it into the
// ledger as a primary key gives us atomic exactly-once processing — duplicate
// retries hit the PK constraint and bail out before any business logic runs.
try {
    Database::insert('stripe_webhook_events', [
        'event_id' => $event->id,
        'type'     => $event->type,
    ]);
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        http_response_code(200);
        exit('duplicate-skipped');
    }
    error_log('Stripe webhook ledger insert failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Webhook ledger error');
}

switch ($event->type) {

    case 'checkout.session.completed':
        $session = $event->data->object;
        handleCheckoutCompleted($session);
        break;

    case 'payment_intent.payment_failed':
        $pi = $event->data->object;
        Database::query(
            "UPDATE orders SET status = 'cancelled' WHERE stripe_payment_intent = ?",
            [$pi->id]
        );
        break;

    default:
        // Ignore other events
        break;
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

    $order = Database::fetchOne(
        "SELECT * FROM orders WHERE stripe_session_id = ?",
        [$session->id]
    );

    if ($order) {
        Mailer::notifyOwnerOfSale($order);
        Mailer::sendCustomerReceipt($order);
    }
}
