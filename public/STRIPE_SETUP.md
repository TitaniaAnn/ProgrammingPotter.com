# Stripe Setup Guide

## 1. Install the Stripe PHP SDK

If your host supports Composer (most do):
```bash
cd /path/to/your/pottery/
composer install
```

If not, download manually:
1. Go to https://github.com/stripe/stripe-php/releases
2. Download the latest `stripe-php-vX.X.X.zip`
3. Unzip it and rename the folder to `stripe-php`
4. Upload to `pottery/includes/stripe-php/`

---

## 2. Create a Stripe account

1. Sign up at https://stripe.com (free)
2. Complete identity verification to accept real payments

---

## 3. Get your API keys

1. Go to https://dashboard.stripe.com/apikeys
2. Copy **Publishable key** (`pk_test_...`) and **Secret key** (`sk_test_...`)
3. Paste them into `config/config.php`:

```php
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_...');
define('STRIPE_SECRET_KEY',      'sk_test_...');
```

Start with test keys (`pk_test_` / `sk_test_`). Switch to live keys (`pk_live_` / `sk_live_`) when ready.

---

## 4. Set up the webhook

Stripe needs to call your site when a payment succeeds.

1. Go to https://dashboard.stripe.com/webhooks
2. Click **Add endpoint**
3. URL: `https://yourdomain.com/shop/webhook.php`
4. Events to listen for:
   - `checkout.session.completed`
   - `payment_intent.payment_failed`
5. Click **Add endpoint**
6. Copy the **Signing secret** (`whsec_...`)
7. Paste it into `config/config.php`:

```php
define('STRIPE_WEBHOOK_SECRET', 'whsec_...');
```

---

## 5. Add shipping rates (optional but recommended)

Rather than hard-coding rates in code, configure them in Stripe:

1. Go to https://dashboard.stripe.com/shipping-rates
2. Create rates (e.g. "Standard shipping - $8.00", "Express - $18.00")
3. The checkout session will offer these to customers

Or just accept flat rate — edit `includes/Stripe.php` and uncomment the `shipping_options` block.

---

## 6. Test a purchase

1. In your shop, click **Buy Now** on any pot with a price set
2. Use Stripe test card: `4242 4242 4242 4242`, any future expiry, any CVC
3. Complete checkout
4. Check your admin at `/admin/orders/index.php` — the order should appear as **paid**
5. Check that you received an email notification

---

## 7. Go live

1. Swap test keys for live keys in `config/config.php`
2. Update the webhook endpoint signing secret with the live one
3. That's it — real payments will now work

---

## Shipping countries

Currently configured to accept orders from:
US, CA, GB, AU, NZ, DE, FR, NL, IE

To add more, edit the `allowed_countries` array in `includes/Stripe.php`.

---

## Refunds

Process refunds directly in the Stripe dashboard:
https://dashboard.stripe.com/payments

Then manually update the order status in your admin to "refunded".
