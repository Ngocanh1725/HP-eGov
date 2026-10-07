<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Csrf;
use TestRunner;

class CsrfTest
{
    public function testTokenGeneration(): void
    {
        $token = Csrf::token();
        TestRunner::assertNotNull($token);
        TestRunner::assertEquals(64, strlen($token)); // 32 bytes hex = 64 ký tự
    }

    public function testTokenValidation(): void
    {
        $token = Csrf::token();
        TestRunner::assertTrue(Csrf::validate($token));
        TestRunner::assertFalse(Csrf::validate('invalid-token-123456'));
        TestRunner::assertFalse(Csrf::validate(null));
    }

    public function testFieldGeneration(): void
    {
        $field = Csrf::field();
        TestRunner::assertContains('<input type="hidden" name="_csrf"', $field);
        TestRunner::assertContains(Csrf::token(), $field);
    }
}
