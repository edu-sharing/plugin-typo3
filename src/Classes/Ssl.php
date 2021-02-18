<?php

namespace Metaventis\Edusharing;

use Metaventis\Edusharing\Settings\Config;

class Ssl
{
    private static $instance = null;

    public static function getInstance(): Ssl
    {
        if (self::$instance == null) {
            self::$instance = new Ssl();
        }
        return self::$instance;
    }

    public function getPublicKey(): string
    {
        $publicKey = Config::getInstance()->get(Config::APP_PUBLIC_KEY);
        return $this->formatPemKey($publicKey);
    }

    public function sign(string $data): string
    {
        $privateKey = Config::getInstance()->get(Config::APP_PRIVATE_KEY);
        $privKeyId = openssl_get_privatekey($this->formatPemKey($privateKey), null);
        openssl_sign($data, $signature, $privKeyId);
        openssl_free_key($privKeyId);
        return $signature;
    }

    public function encrypt(string $data): string
    {
        $config = Config::getInstance();
        $publicKey = $this->formatPemKey($config->get(Config::REPO_PUBLIC_KEY));
        $publicKeyId = openssl_get_publickey($publicKey);
        $ciphertext = '';
        if (!openssl_public_encrypt($data, $ciphertext, $publicKeyId)) {
            throw new \Exception('Failed to encrypt');
        }
        return $ciphertext;
    }


    /*
     * Fix whitespace in an PEM-encoded SSL key.
     */
    private function formatPemKey(string $key): string
    {
        $valid = preg_match('/(-----BEGIN (?:PUBLIC|PRIVATE) KEY-----)(.*)(-----END (?:PUBLIC|PRIVATE) KEY-----)/s', $key, $matches);
        if (!$valid) {
            throw new \Exception('Invalid key format');
        }
        $body = preg_replace('/\s/', '', $matches[2]);
        return $matches[1] . "\n"
            . chunk_split($body, 64, "\n")
            . $matches[3] . "\n";
    }

    private function __construct()
    { }
}
