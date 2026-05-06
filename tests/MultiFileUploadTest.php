<?php

namespace Tests;

use MultiFileUpload;
use PHPUnit\Framework\TestCase;

final class MultiFileUploadTest extends TestCase {

    public function test_returns_empty_for_null_input(): void {
        $this->assertSame([], MultiFileUpload::parse(null));
    }

    public function test_returns_empty_for_missing_name_key(): void {
        $this->assertSame([], MultiFileUpload::parse(['type' => []]));
    }

    public function test_returns_empty_when_name_is_empty(): void {
        $this->assertSame([], MultiFileUpload::parse([
            'name'     => [],
            'type'     => [],
            'tmp_name' => [],
            'error'    => [],
            'size'     => [],
        ]));
    }

    public function test_reshapes_parallel_arrays_keyed_by_original_index(): void {
        $files = [
            'name'     => ['a.jpg', 'b.png', 'c.webp'],
            'type'     => ['image/jpeg', 'image/png', 'image/webp'],
            'tmp_name' => ['/tmp/a', '/tmp/b', '/tmp/c'],
            'error'    => [UPLOAD_ERR_OK, UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size'     => [100, 200, 300],
        ];

        $out = MultiFileUpload::parse($files);

        $this->assertCount(3, $out);
        $this->assertSame('a.jpg', $out[0]['name']);
        $this->assertSame('b.png', $out[1]['name']);
        $this->assertSame('c.webp', $out[2]['name']);
        $this->assertSame(200, $out[1]['size']);
    }

    public function test_skips_empty_slots_but_preserves_index_of_remaining(): void {
        // Browsers can post a multi-file input where some slots are blank; the
        // index of surviving entries must be preserved so callers can correlate
        // parallel arrays (e.g. labels[] posted alongside files[]).
        $files = [
            'name'     => ['a.jpg', '', 'c.png'],
            'type'     => ['image/jpeg', '', 'image/png'],
            'tmp_name' => ['/tmp/a', '', '/tmp/c'],
            'error'    => [UPLOAD_ERR_OK, UPLOAD_ERR_NO_FILE, UPLOAD_ERR_OK],
            'size'     => [100, 0, 300],
        ];

        $out = MultiFileUpload::parse($files);

        $this->assertSame([0, 2], array_keys($out));
        $this->assertSame('a.jpg', $out[0]['name']);
        $this->assertSame('c.png', $out[2]['name']);
    }

    public function test_skips_errored_slots(): void {
        $files = [
            'name'     => ['ok.jpg', 'too_big.jpg'],
            'type'     => ['image/jpeg', 'image/jpeg'],
            'tmp_name' => ['/tmp/ok', ''],
            'error'    => [UPLOAD_ERR_OK, UPLOAD_ERR_INI_SIZE],
            'size'     => [100, 0],
        ];

        $out = MultiFileUpload::parse($files);

        $this->assertSame([0], array_keys($out));
        $this->assertSame('ok.jpg', $out[0]['name']);
    }
}
