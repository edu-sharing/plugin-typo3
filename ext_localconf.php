<?php
defined('TYPO3_MODE') or die();

/*$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all']['edusharing'] =
    \metaVentis\edusharing\Hook\ContentPostProcAll::class . '->replaceContent';
*/

/*Configure CKEditor*/
$GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['edusharing'] = 'EXT:edusharing/Configuration/RTE/edusharing.yaml';


/*Register hooks*/
$GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['extkey'] = \metaVentis\edusharing\Hook\TCEmainHook::class;
$GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['extkey'] = \metaVentis\edusharing\Hook\TCEmainHook::class;


/*Add JS to FE pages*/
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
    'jquery', 'setup',
    'page.includeJS.jquery = https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
    'edusharing', 'setup',
    'page.includeJS.edusharing = EXT:edusharing/Resources/Public/JavaScript/edu.js'
);

/*Register AJAX endpoint*/
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['edusharing_proxy'] = 'EXT:edusharing/Classes/Ajax/Proxy.php';

/*Register reurl*/
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['edusharing_populate'] = 'EXT:edusharing/Classes/Ajax/Populate.php';