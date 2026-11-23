<?php

use PHPUnit\Framework\TestCase;

final class CryptoTest extends TestCase
{
    public function testEncryptAndDecryptPassword(): void
    {
        $encrypted = Crypto::encrypt('test-password');

        self::assertNotSame('test-password', $encrypted);
        self::assertSame('test-password', Crypto::decrypt($encrypted));
    }

    public function testEmptyPasswordRemainsEmpty(): void
    {
        self::assertSame('', Crypto::encrypt(''));
        self::assertSame('', Crypto::decrypt(null));
    }
}
