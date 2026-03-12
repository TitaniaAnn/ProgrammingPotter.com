<?php
// includes/Stripe.php
// Thin wrapper around the Stripe PHP SDK for checkout sessions

class StripeHelper {

    private static function init(): void {
        // Support both Composer autoload and manual SDK drop-in
        $composerAutoload = ROOT_PATH . '/vendor/autoload.php';
        $manualAutoload   = ROOT_PATH . '/includes/stripe-php/init.php';

        if (file_exists($composerAutoload)) {
            require_once $composerAutoload;
        } elseif (file_exists($manualAutoload)) {
            require_once $manualAutoload;
        } else {
            throw new RuntimeException(
                'Stripe PHP SDK not found. Run: composer require stripe/stripe-php  ' .
                'OR download from https://github.com/stripe/stripe-php/releases and unzip to /includes/stripe-php/'
            );
        }

        \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
    }

    /**
     * Create a Stripe Checkout Session for a single pot purchase.
     */
    public static function createCheckoutSession(array $product, int $quantity = 1): string {
        self::init();

        if ($product['status'] !== 'available') {
            throw new RuntimeException('This product is not available for purchase.');
        }
        if ($product['quantity'] < $quantity) {
            throw new RuntimeException('Not enough stock available.');
        }

        $unitAmount = (int) round($product['price'] * 100); // Stripe uses cents

        $lineItem = [
            'price_data' => [
                'currency'     => SHOP_CURRENCY,
                'unit_amount'  => $unitAmount,
                'product_data' => [
                    'name'        => $product['name'],
                    'description' => $product['description'] ?? null,
                ],
            ],
            'quantity' => $quantity,
        ];

        // Add product image if available
        if (!empty($product['image_path'])) {
            $lineItem['price_data']['product_data']['images'] = [
                SITE_URL . '/uploads/' . $product['image_path']
            ];
        }

        $params = [
            'payment_method_types' => ['card'],
            'line_items'           => [$lineItem],
            'mode'                 => 'payment',
            'success_url'          => SITE_URL . '/shop/success.php?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'           => SITE_URL . '/shop/cancel.php?product_id=' . $product['id'],
            'metadata'             => [
                'product_id' => $product['id'],
                'quantity'   => $quantity,
            ],
        ];

        // Collect shipping address
        $params['shipping_address_collection'] = [
            'allowed_countries' => ['US', 'CA', 'GB', 'AU', 'NZ', 'DE', 'FR', 'NL', 'IE'],
        ];

        // Optional: add shipping rate options (flat rate)
        // You can configure these in the Stripe dashboard instead
        // $params['shipping_options'] = [...];

        // Allow customer to enter email for receipt
        $params['customer_creation'] = 'always';

        $session = \Stripe\Checkout\Session::create($params);

        // Store pending order in DB
        Database::insert('orders', [
            'stripe_session_id' => $session->id,
            'product_id'        => $product['id'],
            'product_name'      => $product['name'],
            'product_price'     => $product['price'],
            'quantity'          => $quantity,
            'status'            => 'pending',
        ]);

        return $session->url;
    }

    /**
     * Handle a Stripe webhook event.
     * Returns the event object or throws on failure.
     */
    public static function constructWebhookEvent(string $payload, string $sigHeader): object {
        self::init();
        return \Stripe\Webhook::constructEvent($payload, $sigHeader, STRIPE_WEBHOOK_SECRET);
    }

    /**
     * Retrieve a checkout session with expanded line items.
     */
    public static function retrieveSession(string $sessionId): object {
        self::init();
        return \Stripe\Checkout\Session::retrieve([
            'id'     => $sessionId,
            'expand' => ['customer', 'line_items', 'payment_intent'],
        ]);
    }
}
