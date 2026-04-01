---
description: "Use when changing Stripe checkout, order completion, payment redirects, or webhook handling. Covers validation, idempotency, and safe persistence patterns."
name: "Payments And Webhooks"
applyTo:
  - "public/shop/checkout.php"
  - "public/shop/success.php"
  - "public/shop/cancel.php"
  - "public/shop/webhook.php"
  - "includes/Stripe.php"
---
# Payments And Webhooks Guidelines

- Centralize Stripe integration behavior in includes/Stripe.php; avoid duplicating request/signature logic in page controllers.
- Validate all external callback data before DB writes or status transitions.
- Treat webhook handling as potentially repeated and out-of-order. Implement idempotent state updates keyed to Stripe event/session identifiers.
- Use parameterized Database helper calls for all order/payment persistence.
- Keep checkout/success/cancel pages focused on orchestration and user feedback; push reusable payment logic into helpers.
- Avoid exposing secret keys or webhook secrets in logs or responses.
- When mapping order states, preserve existing status semantics unless the migration plan explicitly changes them.
- For runtime configuration constants and credentials usage patterns, follow config/config.php and includes/bootstrap.php.
