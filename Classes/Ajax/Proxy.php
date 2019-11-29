<?php

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use Metaventis\Edusharing\Settings\Config;
use Metaventis\Edusharing\Ssl;

//secure

class renderProxy
{

    public function getContenturl($eduObj, $displayMode = 'inline')
    {
        $config = Config::getInstance();
        $contenturl = $config->get(Config::REPO_URL) . '/renderingproxy';
        $contenturl .= '?app_id=' . urlencode($config->get(Config::APP_ID));

        $contenturl .= '&rep_id=' . $config->get(Config::REPO_ID);
        $contenturl .= '&obj_id=' . $eduObj['nodeid'];
        $contenturl .= '&resource_id=' . urlencode($eduObj['uid']);
        $contenturl .= '&course_id=' . urlencode($eduObj['contentid']);
        $contenturl .= '&display=' . $displayMode;
        if ($displayMode === 'window')
            $contenturl .= '&closeOnBack=true';
        $contenturl .= '&width=' . $_GET['edusharing_width'];
        $contenturl .= '&height='  . $_GET['edusharing_height'];
        $contenturl .= '&language=' . 'de';
        $contenturl .= '&version=' . $eduObj['version'];
        $contenturl .= $this->getSecurityParams();

        return $contenturl;
    }

    function getRenderHtml($url)
    {
        $inline = false;
        $curl_handle = curl_init($url);
        curl_setopt($curl_handle, CURLOPT_FAILONERROR, 1);
        curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl_handle, CURLOPT_HEADER, 0);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_handle, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl_handle, CURLOPT_SSL_VERIFYHOST, false);
        $inline = curl_exec($curl_handle);
        if (curl_errno($curl_handle)) {
            error_log("Error when fetching $url: " . curl_error($curl_handle));
        }
        curl_close($curl_handle);
        return $inline;
    }


    function display($html, $eduObj)
    {
        $html = str_replace(array(
            "\r\n",
            "\r",
            "\n"
        ), '', $html);

        /*
        * replaces {{{LMS_INLINE_HELPER_SCRIPT}}}
        */
        $html = str_replace(
            "{{{LMS_INLINE_HELPER_SCRIPT}}}",
            "index.php?eID=edusharing_proxy&edusharing_external=true&edusharing_uid=" . $eduObj['uid'],
            $html
        );

        /*
         * replaces <es:title ...>...</es:title>
         */
        //$html = preg_replace ( "/<es:title[^>]*>.*<\/es:title>/Uims", $eduObj['printTitle'], $html );
        /*
         * For images, audio and video show a capture underneath object
         */
        $mimetypes = array(
            'image',
            'video',
            'audio'
        );
        foreach ($mimetypes as $mimetype) {
            if (strpos($eduObj['mimetype'], $mimetype) !== false)
                $html .= '<p class="caption">' . $eduObj['printTitle'] . '</p>';
        }

        echo $html;
    }

    public function getSecurityParams()
    {
        $library = new \Metaventis\Edusharing\Library();
        $user = $this->getFrontendUser() ?? $library->getGuestUser();
        // Get a ticket for the user to generate a user record on the edu-sharing instance in case it didn't exist before. This uses a
        // side-effect of `getTicket()`.
        // TODO: Find a more elegant solution, that won't do additional work if the user already exists and doesn't rely on side-effects.
        // Also don't make it a side-effect of this function.
        $library->getTicket($user);
        $config = Config::getInstance();
        $repo_public_key = openssl_get_publickey($config->get(Config::REPO_PUBLIC_KEY));
        $encryptedUsername = '';
        if (!openssl_public_encrypt($user['username'], $encryptedUsername, $repo_public_key)) {
            throw new Exception('Failed to encrypt username');
        }
        $paramString = '';
        $ts = round(microtime(true) * 1000);
        $paramString .= '&ts=' . $ts;
        $paramString .= '&u=' . urlencode(base64_encode($encryptedUsername));
        $signature = Ssl::getInstance()->sign($config->get(Config::APP_ID) . $ts);
        $signature = base64_encode($signature);
        $paramString .= '&sig=' . urlencode($signature);
        $paramString .= '&signed=' . urlencode($config->get(Config::APP_ID) . $ts);

        return $paramString;
    }

    /*
     * Return the frontend username identified by the respective session cookie.
     * 
     * Returns null if no session cookie was provided or no matching user could be found.
     */
    private function getFrontendUser(): ?array
    {
        $sessionId = $_COOKIE['fe_typo_user'];
        if (is_null($sessionId)) {
            return null;
        }
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionByName('Default');
        $userId = $connection->select(
            ['ses_userid'],
            'fe_sessions',
            ['ses_id' => $sessionId]
        )
            ->fetch()['ses_userid'];
        if (is_null($userId)) {
            return null;
        }
        $user = $connection->select(
            ['*'],
            'fe_users',
            ['uid' => $userId]
        )
            ->fetch();
        if (is_null($user['username'])) {
            return null;
        }
        return $user;
    }
}

function runProxy()
{
    $eduObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
        ->getConnectionForTable('tx_edusharing_object')
        ->select(
            ['*'],
            'tx_edusharing_object',
            [
                'uid' => $_GET['edusharing_uid']
            ]
        )
        ->fetch();

    $renderProxy = new renderProxy();

    if (empty($_GET['edusharing_uid']) || empty($eduObj['nodeid'])) {
        $renderProxy->display('Fehlerhaftes Objekt', $eduObj);
        exit();
    }

    $eduObj['nodeid'] = substr($eduObj['nodeid'], strrpos($eduObj['nodeid'], '/') + 1);

    if ($_GET['edusharing_external']) {
        $url = $renderProxy->getContenturl($eduObj, 'window');
        header('Location: ' . $url);
        exit();
    }

    $url = $renderProxy->getContenturl($eduObj);
    $html = $renderProxy->getRenderHtml($url);
    $renderProxy->display($html, $eduObj);
}

try {
    runProxy();
} catch (Exception $e) {
    error_log($e->getMessage());
    error_log($e->getTraceAsString());
} catch (Error $e) {
    error_log($e->getMessage());
    error_log($e->getTraceAsString());
}
