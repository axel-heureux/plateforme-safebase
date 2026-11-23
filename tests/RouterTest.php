<?php

use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function testNestedApiPrefixIsNormalized(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/plateforme-safebase/public/index.php';

        ob_start();
        $router = new Router();
        $router->get('/api/health', ['DashboardController', 'index']);
        $router->dispatch('GET', '/plateforme-safebase/public/api/health');
        $output = ob_get_clean();

        self::assertStringContainsString('Safebase', $output);
    }

    public function testIndexRouteAcceptsIndexPhpUrl(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/plateforme-safebase/public/index.php';

        ob_start();
        $router = new Router();
        $router->get('/', ['DashboardController', 'index']);
        $router->dispatch('GET', '/plateforme-safebase/public/index.php');
        $output = ob_get_clean();

        self::assertStringContainsString('<title>Safebase', $output);
    }
}
