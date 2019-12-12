<?php

namespace Metaventis\Edusharing;

use Exception;
use Metaventis\Edusharing\Settings\Config;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Library
{
    public function __construct()
    { }

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
        if ($displayMode === 'window') {
            $contenturl .= '&closeOnBack=true';
        }
        $contenturl .= '&width=' . $_GET['edusharing_width'];
        $contenturl .= '&height='  . $_GET['edusharing_height'];
        $contenturl .= '&language=' . 'de';
        $contenturl .= '&version=' . $eduObj['version'];
        $contenturl .= $this->getSecurityParams($eduObj);
        return $contenturl;
    }

    public function getSecurityParams($eduObj)
    {
        $user = $this->getFrontendUser() ?? $this->getGuestUser();
        // Get a ticket for the user to generate a user record on the edu-sharing instance in case it didn't exist before. This uses a
        // side-effect of `getTicket()`.
        // TODO: Find a more elegant solution, that won't do additional work if the user already exists and doesn't rely on side-effects.
        // Also don't make it a side-effect of this function.
        $this->getTicket($user);

        $encryptedUsername =  Ssl::getInstance()->encrypt($user['username']);
       
        $paramString = '';
        $ts = round(microtime(true) * 1000);
        $paramString .= '&ts=' . $ts;
        $paramString .= '&u=' . urlencode(base64_encode($encryptedUsername));
        $config = Config::getInstance();
        $signature = Ssl::getInstance()->sign($config->get(Config::APP_ID) . $ts . $eduObj['nodeid']);
        $signature = base64_encode($signature);
        $paramString .= '&sig=' . urlencode($signature);
        $paramString .= '&signed=' . urlencode($config->get(Config::APP_ID) . $ts . $eduObj['nodeid']);

        return $paramString;
    }

    public function getTicket(array $user)
    {
        $userData = $this->readUserData($user);
        $config = Config::getInstance();
        $eduSoapClient = new EduSoapClient($config->get(Config::REPO_URL) . '/services/authbyapp?wsdl');

        if (isset($_SESSION["repository_ticket"])) {
            // ticket available.. is it valid?
            $params = array("userid" => $userData['userid'], "ticket" => $_SESSION["repository_ticket"]);
            $alfReturn = $eduSoapClient->checkTicket($params);
            if ($alfReturn === true) {
                return $_SESSION["repository_ticket"];
            }
        }

        $paramsTrusted = array(
            "applicationId" => $config->get(Config::APP_ID),
            "ticket" => session_id(),
            "ssoData" => array_map(
                function ($key, $value) {
                    return ['key' => $key, 'value' => $value];
                },
                array_keys($userData),
                $userData
            )
        );

        $alfReturn = $eduSoapClient->authenticateByTrustedApp($paramsTrusted);
        $ticket = $alfReturn->authenticateByTrustedAppReturn->ticket;

        $_SESSION["repository_ticket"] = $ticket;
        return $ticket;
    }

    public function getGuestUser(): array
    {
        $config = Config::getInstance();
        $username = $config->get(Config::REPO_GUEST_USER);
        return [
            'username' => $username
        ];
    }

    public function getSavedSearch($nodeId, $maxItems, $skipCount, $sortProperty, $template)
    {
        $config = Config::getInstance();
        $url = $config->get(Config::REPO_URL) . 'rest/search/v1/queriesV2/load/';
        $url .= $nodeId;
        $url .= '?';
        $url .= 'maxItems=' . $maxItems;
        $url .= '&';
        $url .= 'skipCount=' . $skipCount;
        $url .= '&';
        $url .= 'sortProperties=' . $sortProperty . '&sortAscending=false';

        $curl_handle = curl_init($url);
        $headers = array('Accept: application/json');
        curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl_handle, CURLOPT_FAILONERROR, 1);
        curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl_handle, CURLOPT_HEADER, 0);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_handle, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl_handle, CURLOPT_SSL_VERIFYHOST, false);
        $result = curl_exec($curl_handle);
        if (curl_errno($curl_handle)) {
            error_log("Error when fetching $url: " . curl_error($curl_handle));
        }
        curl_close($curl_handle);
        return json_encode($this->renderSearchResult($result, $template));
    }

    private function renderSearchResult($result, $template)
    {
        $config = Config::getInstance();
        $result = json_decode($result, true);
        if (empty($result)) {
            return '<span class="edusharing_saved_search_empty">Kein Suchergebnis.</span>';
        }
        $return = '';
        if ($template == 'card') {
            foreach ($result['nodes'] as $node) {
                $return .= '<a class="edusharing_saved_search ' . $template . '" target="_blank" ' .
                    'href="' . $config->get(Config::REPO_URL) . '/components/render/' . $node['ref']['id'] . '?closeOnBack=true">' .
                    '<img class="edusharing_saved_search_preview" src="' . $node['preview']['url'] . '&crop=true&width=200&height=150">' .
                    '<span class="edusharing_saved_search_name">' . $node['name'] . '</span>' .
                    '<img src="' . $node['licenseURL'] . '" class="edusharing_saved_search_licenseurl">' .
                    '</a>';
            }
        } else {
            $return = '<div class="edusharing_saved_search_table">';
            foreach ($result['nodes'] as $node) {
                $return .= '<a class="edusharing_saved_search ' . $template . '" target="_blank" ' .
                    'href="' . $config->get(Config::REPO_URL) . '/components/render/' . $node['ref']['id'] . '?closeOnBack=true">' .
                    '<img class="edusharing_saved_search_preview" src="' . $node['preview']['url'] . '&crop=true&width=200&height=150">' .
                    '<span class="edusharing_saved_search_name">' . $node['name'] . '</span>' .
                    '<div><img src="' . $node['licenseURL'] . '" class="edusharing_saved_search_licenseurl"></div>' .
                    '</a>';
            }
            $return .= '</div>';
        }
        return $return;
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

    /**
     * Convert Typo3-style user to sso data excepted by Edu-Sharing.
     */
    private function readUserData(array $user): array
    {
        if (!$user['username']) {
            throw new Exception('User has no username');
        }
        $data = [
            'userid' => $user['username']
        ];
        if ($user['first_name'] || $user['last_name']) {
            $data['firstname'] = $user['first_name'];
            $data['lastname'] = $user['last_name'];
        } elseif ($user['name']) {
            $data['firstname'] = $user['name'];
            $data['lastname'] = '';
        } elseif ($user['realName']) {
            $data['firstname'] = $user['realName'];
            $data['lastname'] = '';
        } else {
            $data['firstname'] = $user['username'];
            $data['lastname'] = '';
        }
        if ($user['email']) {
            $data['email'] = $user['email'];
        }
        return $data;
    }
}
