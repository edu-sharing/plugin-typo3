<?php

namespace Metaventis\Edusharing;

use Exception;
use Metaventis\Edusharing\Settings\Config;

class Library
{
    public function __construct()
    { }

    public function getGuestUser(): array
    {
        $config = Config::getInstance();
        $username = $config->get(Config::REPO_GUEST_USER);
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
                    return ['key' => $key, 'value' => $value ];
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
}
