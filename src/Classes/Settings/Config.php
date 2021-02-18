<?php

namespace Metaventis\Edusharing\Settings;

use \TYPO3\CMS\Core\Utility\GeneralUtility;
use \TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

/*
 * Handle extension configuration.
 * 
 * Should be used as singleton, but allows instantiation with `new` for compatibility.
 * 
 * Usage example: `GeneralUtility::makeInstance(Config::class)->get(Config::APP_PRIVATE_KEY)`
 */

class Config implements \TYPO3\CMS\Core\SingletonInterface
{
    private const EXTENSION_KEY = 'edusharing';

    public const APP_ID = 'app_id';
    public const APP_URL = 'app_url';
    public const APP_PUBLIC_KEY = 'app_public_key';
    public const APP_PRIVATE_KEY = 'app_private_key';
    public const REPO_ID = 'repo_id';
    public const REPO_URL = 'repo_url';
    public const REPO_GUEST_USER = 'repo_guest_user';
    public const REPO_PUBLIC_KEY = 'repo_public_key';

    private $extensionConfiguration;

    function __construct()
    {
        $this->extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
    }


    /*
     * Initialize empty values.
     */
    public function setup(): void
    {
        if ($this->isEmpty($this::APP_ID)) {
            $this->set($this::APP_ID, $this->generateAppId());
        }
        if ($this->isEmpty($this::APP_URL)) {
            $this->set($this::APP_URL, $this->getSiteUrl());
        }
        if ($this->isEmpty($this::APP_PUBLIC_KEY) || $this->isEmpty($this::APP_PRIVATE_KEY)) {
            $keyPair = $this->generateKeyPair();
            $this->set($this::APP_PUBLIC_KEY, $keyPair['public']);
            $this->set($this::APP_PRIVATE_KEY, $keyPair['private']);
        }
    }

    private function isEmpty(string $key): bool
    {
        return empty($this->extensionConfiguration->get($this::EXTENSION_KEY, $key));
    }

    public function get(string $key): string
    {
        return $this->extensionConfiguration->get($this::EXTENSION_KEY, $key);
    }

    public function set(string $key, string $value): void
    {
        $this->extensionConfiguration->set($this::EXTENSION_KEY, $key, $value);
    }

    private function generateAppId(): string
    {
        return 'typo3_' . rand();
    }

    private function getSiteUrl(): string
    {
        return GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
    }

    private function generateKeyPair(): array
    {
        $key = openssl_pkey_new();
        $privateKey = '';
        openssl_pkey_export($key, $privateKey);
        $publicKey = openssl_pkey_get_details($key)['key'];
        return array(
            'private' => $privateKey,
            'public' => $publicKey
        );
    }
}
