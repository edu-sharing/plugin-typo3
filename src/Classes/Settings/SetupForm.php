<?php

namespace Metaventis\Edusharing\Settings;

use Metaventis\Edusharing\Settings\Config;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\ViewHelpers\Form\TypoScriptConstantsViewHelper;

class SetupForm
{
    public function render(array $parameter, TypoScriptConstantsViewHelper $viewHelper)
    {
        $requestUrl = GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . 'typo3/?route=%2Fedusharing%2Fsetup';
        $repoUrl = Config::getInstance()->get(Config::REPO_URL);
        if ($repoUrl) {
            $parameter['fieldValue'] = $repoUrl;
        }
        return "<input name=\"$parameter[fieldName]\" value=\"$parameter[fieldValue]\" id=\"em-$parameter[fieldName]\"/ size=\"45\">
            <button onclick=\"edusharingSetup(event)\">Setup repository</button>
            <script>
                function edusharingSetup(event) {
                    event.preventDefault();
                    const repoUrl = $('#em-$parameter[fieldName]')[0].value;
                    if (!confirm(
                        'We will try to fetch repository information from ' + repoUrl + '. '
                        + 'Afterwards, the page will be reloaded. '
                        + 'Please save any changes you want to keep, before pressing \"OK\".'
                    )) {
                        return;
                    }
                    const url = \"$requestUrl\" + \"&repoUrl=\" + encodeURI(repoUrl);
                    $.get(url)
                        .then(() => {
                            require(['TYPO3/CMS/Backend/Notification'], (Notification) => Notification.success('Setup successful'))
                            location.reload();
                        })
                        .catch((e) => {
                            require(['TYPO3/CMS/Backend/Notification'], (Notification) => {
                                Notification.error('Setup failed', e.responseText)
                            });
                        });
                }
            </script>

            ";
    }
}
