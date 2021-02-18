<?php
defined('TYPO3') or die();

(function () {
    /*Configure CKEditor*/
    $GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['edusharing'] = 'EXT:edusharing/Configuration/RTE/edusharing.yaml';
    $GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['default'] = 'EXT:edusharing/Configuration/RTE/edusharing.yaml';

    /*Register hooks*/
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['extkey'] = \Metaventis\Edusharing\Hook\TCEmainHook::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['extkey'] = \Metaventis\Edusharing\Hook\TCEmainHook::class;

    /*Add JS to FE pages*/
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
        'jquery',
        'setup',
        'page.includeJS.jquery = https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js'
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
        'edusharing',
        'setup',
        'page.includeJS.edusharing = EXT:edusharing/Resources/Public/JavaScript/edu.js'
    );

    /*Register AJAX endpoint*/
    $GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['edusharing_proxy'] = \Metaventis\Edusharing\Ajax\Proxy::class;
    $GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['edusharing_application_xml'] = \Metaventis\Edusharing\Settings\ApplicationXml::class . '::printXml';
})();
