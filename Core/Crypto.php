<?php

/**
 * Chiffrement/déchiffrement des mots de passe des connexions (colonne password_enc).
 *
 * IMPORTANT : la clé ci-dessous est un exemple pour faire tourner le projet en local.
 * En production, définis une variable d'environnement APP_KEY (32 caractères min,
 * aléatoire) et ne la commite jamais dans le repo.
 */
class Crypto
{
    private const CIPHER = 'aes-256-cbc';

    private static function key(): string
    {
        $key = getenv('APP_KEY') ?: 'safebase-dev-key-a-changer-en-prod';
        return substr(hash('sha256', $key, true), 0, 32);
    }

    public static function encrypt(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        $iv = openssl_random_pseudo_bytes($ivLength);
        $encrypted = openssl_encrypt($value, self::CIPHER, self::key(), 0, $iv);

        return base64_encode($iv . $encrypted);
    }

    public static function decrypt(?string $value): string
    {
        if (!$value) {
            return '';
        }

        $raw = base64_decode($value);
        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        $iv = substr($raw, 0, $ivLength);
        $encrypted = substr($raw, $ivLength);

        $decrypted = openssl_decrypt($encrypted, self::CIPHER, self::key(), 0, $iv);
        return $decrypted !== false ? $decrypted : '';
    }
}
