<?php

use PHPUnit\Framework\TestCase;

final class ConnectionHostTest extends TestCase
{
    public function testNamedDatabaseHostIsPreserved(): void
    {
        self::assertSame('db', ConnectionHost::resolve('db'));
        self::assertSame('database.example.test', ConnectionHost::resolve('database.example.test'));
    }
}
