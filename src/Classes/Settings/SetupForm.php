<?php

namespace Metaventis\Edusharing\Settings;

use Metaventis\Edusharing\Settings\Config;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SetupForm
{
    public function render(array $parameter)
    {
        $repoUrl = GeneralUtility::makeInstance(Config::class)->get(Config::REPO_URL);
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
                    $.ajax({
                        url: TYPO3.settings.ajaxUrls['edusharing_setup'],
                        method: 'POST',
                        data: {
                            repoUrl,
                        },
                        success: function(response) {
                            require(['TYPO3/CMS/Backend/Notification'], (Notification) =>
                                Notification.success('Setup successful')
                            );
                            // Changes to the configuration by the 'edusharing_setup' endpoint, are
                            // not reflected in the configuration form shown to the user. If the
                            // user would save the configuration afterwards, the changes would be
                            // overwritten by old values. Therefore, we force a page reload after
                            // successful setup.
                            location.reload();
                        },
                        error: (response, textStatus, error) => {
                            console.log(response);
                            require(['TYPO3/CMS/Backend/Notification'], (Notification) => 
                                Notification.error(
                                    'Setup failed',
                                    response.responseText
                                )
                            );
                        },
                    });
                }
            </script>
            ";
    }
}
