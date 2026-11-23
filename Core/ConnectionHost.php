<?php

class ConnectionHost
{
    public static function resolve(string $host): string
    {
        if (is_file('/.dockerenv') && in_array(strtolower($host), ['localhost', '127.0.0.1', '::1'], true)) {
            return 'host.docker.internal';
        }

        return $host;
    }
}
