<?php
// includes/Mailer.php — Simple PHP mail wrapper for order notifications

class Mailer {

    /**
     * Send order notification to the shop owner.
     */
    public static function notifyOwnerOfSale(array $order): void {
        $to      = setting('contact_email');
        $siteName = setting('site_name', 'My Pottery');

        if (!$to) return; // No email configured

        $subject = "[{$siteName}] New order — {$order['product_name']}";

        $shipping = implode(', ', array_filter([
            $order['shipping_line1'] ?? '',
            $order['shipping_line2'] ?? '',
            $order['shipping_city'] ?? '',
            $order['shipping_state'] ?? '',
            $order['shipping_postal_code'] ?? '',
            $order['shipping_country'] ?? '',
        ]));

        $body = "You have a new order!\n\n"
            . "Order #: {$order['id']}\n"
            . "Item: {$order['product_name']}\n"
            . "Price: $" . number_format($order['product_price'], 2) . "\n"
            . "Qty: {$order['quantity']}\n\n"
            . "Customer: {$order['customer_name']}\n"
            . "Email: {$order['customer_email']}\n"
            . "Ship to: {$shipping}\n\n"
            . "Manage orders: " . SITE_URL . "/admin/orders/index.php\n";

        self::send($to, $subject, $body);
    }

    /**
     * Send receipt to customer.
     */
    public static function sendCustomerReceipt(array $order): void {
        if (empty($order['customer_email'])) return;

        $siteName = setting('site_name', 'My Pottery');
        $subject  = "Your order from {$siteName} — thank you!";

        $body = "Hi {$order['customer_name']},\n\n"
            . "Thank you so much for your order — it means the world!\n\n"
            . "You ordered: {$order['product_name']}\n"
            . "Total paid: $" . number_format($order['product_price'] * $order['quantity'], 2) . "\n\n"
            . "I'll pack it up carefully and send you tracking info once it ships.\n\n"
            . "Questions? Reply to this email or reach me at " . setting('contact_email') . "\n\n"
            . "With gratitude,\n"
            . setting('site_name') . "\n";

        self::send($order['customer_email'], $subject, $body, setting('contact_email'));
    }

    /**
     * Notify customer when order ships.
     */
    public static function notifyShipped(array $order): void {
        if (empty($order['customer_email'])) return;

        $siteName = setting('site_name', 'My Pottery');
        $subject  = "Your order has shipped! — {$siteName}";

        $tracking = '';
        if ($order['tracking_number']) {
            $tracking = "\nTracking: {$order['tracking_carrier']} — {$order['tracking_number']}\n";
        }

        $body = "Hi {$order['customer_name']},\n\n"
            . "Great news — your {$order['product_name']} is on its way!\n"
            . $tracking
            . "\nThank you again for supporting my work.\n\n"
            . setting('site_name') . "\n";

        self::send($order['customer_email'], $subject, $body, setting('contact_email'));
    }

    private static function send(string $to, string $subject, string $body, string $replyTo = ''): void {
        $headers = "From: " . setting('site_name', 'My Pottery') . " <" . setting('contact_email') . ">\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        if ($replyTo) {
            $headers .= "Reply-To: {$replyTo}\r\n";
        }

        // PHP mail() — works on most shared hosts.
        // For production, swap this for a transactional provider like:
        // - Postmark: https://postmarkapp.com ($0 for 100/mo free)
        // - Resend: https://resend.com (3,000/mo free)
        // - Mailgun, SendGrid, etc.
        @mail($to, $subject, $body, $headers);
    }
}
