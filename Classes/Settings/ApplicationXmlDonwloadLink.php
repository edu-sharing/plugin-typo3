<?php

namespace Metaventis\Edusharing\Settings;

use \TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extensionmanager\ViewHelpers\Form\TypoScriptConstantsViewHelper;

class ApplicationXmlDownloadLink
{
    public function render(array $parameter, TypoScriptConstantsViewHelper $viewHelper)
    {
        $parameter['fieldValue'] = GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . '?eID=edusharing_application_xml';
        return "<input name=\"$parameter[fieldName]\" value=\"$parameter[fieldValue]\" id=\"em-$parameter[fieldName]\" readonly/  size=\"45\">
            <button onclick=\"copyXmlLink(event)\">Copy to clipboard</button>
            <script>
                function copyXmlLink(event) {
                    event.preventDefault();
                    el = document.getElementById(\"em-$parameter[fieldName]\");
                    el.select();
                    document.execCommand('copy');
                    require(['TYPO3/CMS/Backend/Notification'], (Notification) => Notification.notice(null, 'Copied to clipboard'))
                }
            </script>";
    }
}
