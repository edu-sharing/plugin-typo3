<?php
defined('TYPO3_MODE') || die();

call_user_func(function () {
    $extensionKey = 'edusharing';

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
        $extensionKey,
        'Configuration/TypoScript',
        'Edusharing CSS'
    );
});
