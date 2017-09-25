<?php
namespace metaVentis\edusharing;

class Library {

    public $cmsConfig;
    public $repoConfig;

    public function __construct() {

    }

    public function getTicket() {
        try {

            $eduSoapClient = new EduSoapClient(Appconfig::$repo_url . '/services/authbyapp?wsdl');
            $userid = $GLOBALS['BE_USER']->user['username'];

            if (isset($_SESSION["repository_ticket"])) {
                // ticket available.. is it valid?
                $params = array("userid" => $userid, "ticket" => $_SESSION["repository_ticket"]);
                try {
                    $alfReturn = $eduSoapClient -> checkTicket($params);

                    if ($alfReturn === true) {

                        return $_SESSION["repository_ticket"];
                    }
                } catch (Exception $e) {
                    return $e;
                }
            }

            $paramsTrusted = array("applicationId" => Appconfig::$app_id, "ticket" => session_id(), "ssoData" => array(array('key' => 'userid','value' => $userid)));

            $alfReturn = $eduSoapClient -> authenticateByTrustedApp($paramsTrusted);
            $ticket = $alfReturn -> authenticateByTrustedAppReturn -> ticket;

            $_SESSION["repository_ticket"] = $ticket;
            return $ticket;


        } catch (Exception $e) {
            error_log('Error getting ticket in ' . get_class($this));
            return;

        }
    }
}