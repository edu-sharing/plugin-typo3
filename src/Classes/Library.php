<?php

namespace Metaventis\Edusharing;

use Exception;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Metaventis\Edusharing\Settings\Config;
use Metaventis\Edusharing\EduRestClient;



class Library implements \TYPO3\CMS\Core\SingletonInterface
{
    private $config;
    private $ssl;
    private $eduRestClient;

    public function __construct()
    {
        $this->config = GeneralUtility::makeInstance(Config::class);
        $this->ssl = GeneralUtility::makeInstance(Ssl::class);
        $this->eduRestClient = GeneralUtility::makeInstance(EduRestClient::class);
    }

    public function getContenturl(EdusharingObject $eduObj, $displayMode = 'inline')
    {
        $contenturl = $this->config->get(Config::REPO_URL) . '/renderingproxy';
        $contenturl .= '?app_id=' . urlencode($this->config->get(Config::APP_ID));
        $contenturl .= '&rep_id=' . $this->config->get(Config::REPO_ID);
        $contenturl .= '&obj_id=' . $eduObj->nodeId;
        $contenturl .= '&resource_id=' . urlencode($eduObj->uid);
        $contenturl .= '&course_id=' . urlencode($eduObj->contentId);
        $contenturl .= '&display=' . $displayMode;
        if ($displayMode === 'window') {
            $contenturl .= '&closeOnBack=true';
        }
        $contenturl .= '&width=' . $_GET['edusharing_width'];
        $contenturl .= '&height=' . $_GET['edusharing_height'];
        $contenturl .= '&language=' . 'de';
        $contenturl .= '&version=' . $eduObj->version;
        $contenturl .= $this->getSecurityParams($eduObj);
        return $contenturl;
    }

    public function getSecurityParams(EdusharingObject $eduObj)
    {
        $user = $this->getUser();
        // Get a ticket for the user to generate a user record on the edu-sharing instance in case it didn't exist before. This uses a
        // side-effect of `getTicket()`.
        // TODO: Find a more elegant solution, that won't do additional work if the user already exists and doesn't rely on side-effects.
        // Also don't make it a side-effect of this function.
        $this->getTicket();

        $encryptedUsername = $this->ssl->encrypt($user['username']);

        $paramString = '';
        $ts = round(microtime(true) * 1000);
        $paramString .= '&ts=' . $ts;
        $paramString .= '&u=' . urlencode(base64_encode($encryptedUsername));
        $signature = $this->ssl->sign($this->config->get(Config::APP_ID) . $ts . $eduObj->nodeId);
        $signature = base64_encode($signature);
        $paramString .= '&sig=' . urlencode($signature);
        $paramString .= '&signed=' . urlencode($this->config->get(Config::APP_ID) . $ts . $eduObj->nodeId);
        $paramString .= '&ticket=' . urlencode(base64_encode($this->ssl->encrypt($this->getTicket())));

        return $paramString;
    }

    public function getTicket(): string
    {
        $username = $this->getUser()['username'];

        // FIXME: the session is not persisted across requests, so this will usually not work and
        // when it does, it sends redundant requests checking a ticket we just fetched.
        if (isset($_SESSION["repository_ticket"])) {
            // ticket available.. is it valid?
            $isValid = $this->eduRestClient->isTicketValid($_SESSION["repository_ticket"]);
            if ($isValid) {
                return $_SESSION["repository_ticket"];
            }
        }

        $ticket = $this->eduRestClient->getTicket($username);
        $_SESSION["repository_ticket"] = $ticket;
        return $ticket;
    }

    public function getSavedSearch($nodeId, $maxItems, $skipCount, $sortProperty, $template)
    {
        $url = $this->config->get(Config::REPO_URL) . 'rest/search/v1/queries/load/';
        $url .= $nodeId;
        $url .= '?';
        $url .= 'maxItems=' . $maxItems;
        $url .= '&';
        $url .= 'skipCount=' . $skipCount;
        $url .= '&';
        $url .= 'sortProperties=' . $sortProperty . '&sortAscending=false';

        $curl_handle = curl_init($url);
        $headers = array(
            'Authorization: EDU-TICKET ' . $this->getTicket(),
            'Accept: application/json',
        );
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
            throw new Exception("Error when fetching $url: " . curl_error($curl_handle));
        }
        curl_close($curl_handle);
        return json_encode($this->renderSearchResult($result, $template));
    }

    private function renderSearchResult($result, $template)
    {
        $result = json_decode($result, true);
        if (empty($result) || empty($result['nodes'])) {
            return '<span class="edusharing_saved_search_empty">Kein Suchergebnis.</span>';
        }
        $return = '';
        // Note that we do not provide tickets for preview images and link targets. Saved searches
        // are only supported with enabled guest user in edu-sharing. We could make it work by
        // adding tickets to the URLs here, but that would expose a regular user session to the
        // public in case "esguest" is not configured as guest user in edu-sharing.
        if ($template == 'card') {
            foreach ($result['nodes'] as $node) {
                $return .= '<a class="edusharing_saved_search ' . $template . '" target="_blank" ' .
                    'href="' . $this->config->get(Config::REPO_URL) . '/components/render/' . $node['ref']['id'] . '?closeOnBack=true">' .
                    '<img class="edusharing_saved_search_preview" src="' . $node['preview']['url'] . '&crop=true&width=200&height=150">' .
                    '<span class="edusharing_saved_search_name">' . $node['name'] . '</span>' .
                    '<img src="' . $node['licenseURL'] . '" class="edusharing_saved_search_licenseurl">' .
                    '</a>';
            }
        } else {
            $return = '<div class="edusharing_saved_search_table">';
            foreach ($result['nodes'] as $node) {
                $return .= '<a class="edusharing_saved_search ' . $template . '" target="_blank" ' .
                    'href="' . $this->config->get(Config::REPO_URL) . '/components/render/' . $node['ref']['id'] . '?closeOnBack=true">' .
                    '<img class="edusharing_saved_search_preview" src="' . $node['preview']['url'] . '&crop=true&width=200&height=150">' .
                    '<span class="edusharing_saved_search_name">' . $node['name'] . '</span>' .
                    '<div><img src="' . $node['licenseURL'] . '" class="edusharing_saved_search_licenseurl"></div>' .
                    '</a>';
            }
            $return .= '</div>';
        }
        return $return;
    }

    private function getUser(): array
    {
        return $this->getBackendUser() ?? $this->getFrontendUser() ?? $this->getGuestUser();
    }

    private function getBackendUser(): ?array
    {
        return $GLOBALS['BE_USER']->user;
    }

    /**
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

    private function getGuestUser(): array
    {
        $username = $this->config->get(Config::REPO_GUEST_USER);
        return [
            'username' => $username
        ];
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