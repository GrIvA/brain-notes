<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Service for note content encryption and decryption.
 * Uses AES-256-CBC with PBKDF2 for key derivation and HMAC for integrity.
 */
class EncryptionService
{
    private const ALGORITHM = 'aes-256-cbc';
    private const PBKDF2_ITERATIONS = 10000;
    private const SALT_LENGTH = 16;
    private const IV_LENGTH = 16;
    private const HMAC_LENGTH = 32;

    /**
     * Encrypt data using a password.
     * Returns base64 encoded string: salt(16) | iv(16) | hmac(32) | ciphertext
     */
    public function encrypt(string $data, string $password): string
    {
        $salt = random_bytes(self::SALT_LENGTH);
        $iv = random_bytes(self::IV_LENGTH);
        
        $key = hash_pbkdf2('sha256', $password, $salt, self::PBKDF2_ITERATIONS, 32, true);
        
        $ciphertext = openssl_encrypt($data, self::ALGORITHM, $key, OPENSSL_RAW_DATA, $iv);
        
        $hmac = hash_hmac('sha256', $ciphertext, $key, true);
        
        return base64_encode($salt . $iv . $hmac . $ciphertext);
    }

    /**
     * Decrypt data using a password.
     * Validates integrity using HMAC.
     */
    public function decrypt(string $base64Data, string $password): ?string
    {
        $binaryData = base64_decode($base64Data, true);
        if (!$binaryData) {
            return null;
        }

        $minLen = self::SALT_LENGTH + self::IV_LENGTH + self::HMAC_LENGTH;
        if (strlen($binaryData) <= $minLen) {
            return null;
        }

        $salt = substr($binaryData, 0, self::SALT_LENGTH);
        $iv = substr($binaryData, self::SALT_LENGTH, self::IV_LENGTH);
        $hmacProvided = substr($binaryData, self::SALT_LENGTH + self::IV_LENGTH, self::HMAC_LENGTH);
        $ciphertext = substr($binaryData, $minLen);

        $key = hash_pbkdf2('sha256', $password, $salt, self::PBKDF2_ITERATIONS, 32, true);
        
        $hmacCalculated = hash_hmac('sha256', $ciphertext, $key, true);
        
        if (!hash_equals($hmacProvided, $hmacCalculated)) {
            return null;
        }

        $decrypted = openssl_decrypt($ciphertext, self::ALGORITHM, $key, OPENSSL_RAW_DATA, $iv);
        
        return $decrypted !== false ? $decrypted : null;
    }
}
