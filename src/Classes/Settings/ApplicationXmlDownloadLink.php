<?php

namespace Metaventis\Edusharing\Settings;

use \TYPO3\CMS\Core\Utility\GeneralUtility;
use Metaventis\Edusharing\Settings\Config;

class ApplicationXmlDownloadLink
{
    private $config;

    public function __construct()
    {
        $this->config = GeneralUtility::makeInstance(Config::class);
    }

    public function render(array $parameter)
    {
        $appUrl = $this->config->get(Config::APP_URL);
        $parameter['fieldValue'] = $appUrl . '?eID=edusharing_application_xml';
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

