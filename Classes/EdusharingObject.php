<?php

namespace metaVentis\edusharing;

class EdusharingObject {

    public $uid;
    public $nodeId;
    public $contentId;
    public $title;
    public $mimetype;
    public $version;
    public $logger;

    public function __construct($nodeId = '', $contentId = 0, $title = '', $mimetype = '', $version = '', $uid = '') {
        $this -> nodeId = $nodeId;
        $this -> contentId = $contentId;
        $this -> title = $title;
        $this -> mimetype = $mimetype;
        $this -> version = $version;
        $this -> uid = $uid;
        $this -> logger = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\Log\LogManager')->getLogger(__CLASS__);

    }

    public function exists() {
        $row = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
            ->getConnectionForTable('tx_edusharing_object')
            ->select(
                ['uid'],
                'tx_edusharing_object',
                [
                    'nodeid' => $this->nodeId,
                    'contentid' => $this->contentId,
                    'version' => $this->version
                ]
            )
            ->fetch();

        if($row) {
            $this->uid = $row['uid'];
            return true;
        }
        return false;
    }

    public function add() {
        try {
            $this->dbInsert();
            $this -> setUsage();
        } catch(\Exception $e) {
            return false;
        }
        return true;
    }

    public function delete() {
        try {
            $this->deleteUsage();
            $this->dbDelete();
        } catch(\Exception $e) {
            return false;
        }
        return true;
    }

    private function deleteUsage() {
        $eduSoapClient = new EduSoapClient(Appconfig::$repo_url . '/services/usage2?wsdl');
        $params = array(
                "eduRef" => $this -> nodeId,
                "user" => null,
                "lmsId" => Appconfig::$app_id,
                "courseId" => $this -> contentId,
                "resourceId" => $this -> uid
            );
            try {
                $eduSoapClient -> deleteUsage($params);
            } catch(\SoapFault $e) {
                $this->logger->log(\TYPO3\CMS\Core\Log\LogLevel::ERROR, $e->faultString);
            }

            return true;
    }

    private function dbDelete() {
        $connectionObject  = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)->getConnectionForTable('tx_edusharing_object');
        $uid = $connectionObject->delete(
            'tx_edusharing_object',
            [
                'uid' => $this->uid
            ]
        );
        return $uid;
    }

    private function dbInsert() {
        $connectionObject  = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)->getConnectionForTable('tx_edusharing_object');
        $connectionObject->insert(
            'tx_edusharing_object',
            [
                'nodeid' => $this->nodeId,
                'contentid' => $this->contentId,
                'title' => $this->title,
                'version' => $this->version,
                'mimetype' => $this->mimetype
            ]
        );

        //to fetch uid
        $this->exists();
        return true;
    }


    private function setUsage() {
        $eduSoapClient = new EduSoapClient(Appconfig::$repo_url . '/services/usage2?wsdl');
        $params = array(
            "eduRef" => $this -> nodeId,
            "user" => $GLOBALS['BE_USER']->user['username'],
            "lmsId" => Appconfig::$app_id,
            "courseId" => $this -> contentId,
            "userMail" => $GLOBALS['BE_USER']->user['username'],
            "fromUsed" => '2002-05-30T09:00:00',
            "toUsed" => '2222-05-30T09:00:00',
            "distinctPersons" => '0',
            "version" => $this -> version,
            "resourceId" => $this -> uid,
            "xmlParams" => ''
        );

        try {
            $eduSoapClient->setUsage($params);
        } catch(\SoapFault $e) {

            $this->logger->log(\TYPO3\CMS\Core\Log\LogLevel::ERROR, $e->faultString);
        }

        return true;

    }
}