<?php

namespace Tests;

use Mailer;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class MailerTest extends TestCase {

    private function sanitize(string $value): string {
        $m = new ReflectionMethod(Mailer::class, 'sanitizeHeader');
        $m->setAccessible(true);
        return $m->invoke(null, $value);
    }

    public function test_strips_crlf_from_header_values(): void {
        // Classic email-header injection: CRLF lets an attacker inject a Bcc:
        // and silently fan an order email out to a third party.
        $value = "Jane Doe\r\nBcc: attacker@evil.com";
        $this->assertSame('Jane Doe Bcc: attacker@evil.com', $this->sanitize($value));
    }

    public function test_strips_lone_lf_and_cr(): void {
        $this->assertSame('a b', $this->sanitize("a\nb"));
        $this->assertSame('a b', $this->sanitize("a\rb"));
    }

    public function test_strips_other_control_characters(): void {
        $this->assertSame('clean', $this->sanitize("clean\x00"));
        $this->assertSame('a b', $this->sanitize("a\x01b"));
    }

    public function test_trims_leading_and_trailing_whitespace(): void {
        $this->assertSame('value', $this->sanitize("   value   "));
    }
}
