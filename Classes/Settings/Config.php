<?php

namespace Metaventis\Edusharing\Settings;

use TYPO3\CMS\Core\Utility\ArrayUtility;
use \TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/*
 * Handle extension configuration.
 * 
 * Should be used as singleton, but allows instantiation with `new` for compatability.
 * 
 * Usage example: `Config::getInstance()->get(Config::APP_PRIVATE_KEY)`
 */

class Config
{

    private static $instance = null;

    private const EXTENSION_KEY = 'edusharing';

    public const APP_ID = 'app_id';
    public const APP_URL = 'app_url';
    public const APP_PUBLIC_KEY = 'app_public_key';
    public const APP_PRIVATE_KEY = 'app_private_key';
    public const REPO_ID = 'repo_id';
    public const REPO_URL = 'repo_url';
    public const REPO_GUEST_USER = 'repo_guest_user';
    public const REPO_PUBLIC_KEY = 'repo_public_key';

    private $configurationUtility;

    public static function getInstance(): Config
    {
        if (self::$instance == null) {
            self::$instance = new Config();
        }
        return self::$instance;
    }

    function __construct()
    {
        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ObjectManager::class);
        $this->configurationUtility = $objectManager->get(ConfigurationUtility::class);
        $this->configurationUtility->injectObjectManager($objectManager);
    }

    public function onInstallExtension($extname = null): void
    {
        if ($extname == $this::EXTENSION_KEY) {
            $this->setup();
        }
    }

    /*
     * Initialize empty values.
     */
    private function setup(): void
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
        return empty($this->configurationUtility->getCurrentConfiguration($this::EXTENSION_KEY)[$key]['value']);
    }

    public function get(string $key): string
    {
        $result = $this->configurationUtility->getCurrentConfiguration($this::EXTENSION_KEY)[$key]['value'];
        return $result;
    }

    public function set(string $key, string $value): void
    {
        $var = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this::EXTENSION_KEY]);
        $var[$key] = $value;
        $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this::EXTENSION_KEY] = serialize($var);
        $configuration = $this->configurationUtility->getCurrentConfiguration($this::EXTENSION_KEY);
        // ArrayUtility::mergeRecursiveWithOverrule($configuration, $var);
        // $configuration[$key]['value'] = $value;
        $nestedConfiguration = $this->configurationUtility->convertValuedToNestedConfiguration($configuration);
        // $nestedConfiguration[$key] = $value;
        $this->configurationUtility->writeConfiguration($nestedConfiguration, $this::EXTENSION_KEY);
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
