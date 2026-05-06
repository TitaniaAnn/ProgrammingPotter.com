<?php

namespace Tests;

use Auth;
use PHPUnit\Framework\TestCase;

/**
 * Covers the proxy-aware secure-cookie detection added to Auth::isSecureRequest.
 * Behind a TLS-terminating reverse proxy (Bluehost, Cloudflare, etc.) the PHP
 * server only sees plain HTTP — the proxy advertises the real scheme via the
 * X-Forwarded-Proto header. The previous check `isset($_SERVER['HTTPS'])` would
 * mark the session cookie as non-secure in those environments.
 */
final class AuthSecureRequestTest extends TestCase {

    private array $serverBackup;

    protected function setUp(): void {
        $this->serverBackup = $_SERVER;
        unset(
            $_SERVER['HTTPS'],
            $_SERVER['HTTP_X_FORWARDED_PROTO'],
            $_SERVER['SERVER_PORT']
        );
    }

    protected function tearDown(): void {
        $_SERVER = $this->serverBackup;
    }

    public function test_returns_false_for_plain_http(): void {
        $this->assertFalse(Auth::isSecureRequest());
    }

    public function test_returns_true_when_https_server_var_set(): void {
        $_SERVER['HTTPS'] = 'on';
        $this->assertTrue(Auth::isSecureRequest());
    }

    public function test_returns_false_when_https_explicitly_off(): void {
        // Some SAPIs set HTTPS to the literal string "off" rather than unsetting it.
        $_SERVER['HTTPS'] = 'off';
        $this->assertFalse(Auth::isSecureRequest());
    }

    public function test_returns_true_when_proxy_terminates_tls(): void {
        // The fix this test exists for: HTTPS is unset because PHP only sees
        // the proxy's plain HTTP forward, but X-Forwarded-Proto reflects the
        // browser-facing scheme.
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $this->assertTrue(Auth::isSecureRequest());
    }

    public function test_x_forwarded_proto_check_is_case_insensitive(): void {
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'HTTPS';
        $this->assertTrue(Auth::isSecureRequest());
    }

    public function test_returns_true_on_port_443_fallback(): void {
        $_SERVER['SERVER_PORT'] = '443';
        $this->assertTrue(Auth::isSecureRequest());
    }

    public function test_does_not_treat_proxy_http_as_secure(): void {
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'http';
        $this->assertFalse(Auth::isSecureRequest());
    }
}
