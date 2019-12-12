<?php

namespace Metaventis\Edusharing;

use Metaventis\Edusharing\Settings\Config;

class EdusharingObject
{

    public $uid;
    public $objecturl;
    public $contentId;
    public $title;
    public $mimetype;
    public $version;
    public $logger;
    private $config;

    /**
     * Set the content id to its final value and complete initialization.
     *
     * This should be called once for an edusharing object, that was instantiated with a temporary content id and hence couldn't complete
     * initialization (see `add()`).
     */
    public static function updateContentId(string $temporaryContentId, int $finalContentId)
    {
        $edusharingObjects = EdusharingObjectsInitMap::getInstance()->pop($temporaryContentId);
        foreach ($edusharingObjects as $edusharingObject) {
            $edusharingObject->contentId = $finalContentId;
            $edusharingObject->dbUpdateContentId();
            $edusharingObject->setUsage();
        }
    }

    public function __construct($objecturl = '', $contentId = 0, $title = '', $mimetype = '', $version = '', $uid = '')
    {
        $this->objecturl = $objecturl;
        $this->contentId = $contentId;
        $this->title = $title;
        $this->mimetype = $mimetype;
        $this->version = $version;
        $this->uid = $uid;
        $this->logger = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\Log\LogManager')->getLogger(__CLASS__);
        $this->config = Config::getInstance();
    }

    public function exists()
    {
        $row = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
            ->getConnectionForTable('tx_edusharing_object')
            ->select(
                ['uid'],
                'tx_edusharing_object',
                [
                    'objecturl' => $this->objecturl,
                    'contentid' => $this->contentId,
                    'version' => $this->version
                ]
            )
            ->fetch();

        if ($row) {
            $this->uid = $row['uid'];
            return true;
        }
        return false;
    }

    public function add()
    {
        if ($this->contentIdIsFinal()) {
            $this->dbInsert();
            $this->setUsage();
        } else {
            // Typo3 provided us with a temporary content id that will change before being persisted. In this case, we create a db entry to
            // obtain a UID, but we don't set usage for now. When we learn the final content id, we update the db entry and call
            // `setUsage()`.
            $temporaryContentId = $this->contentId;
            $this->contentId = 0;
            $this->dbInsert();
            EdusharingObjectsInitMap::getInstance()->push($temporaryContentId, $this);
        }
    }

    public function delete()
    {
        $this->deleteUsage();
        $this->dbDelete();
    }

    private function contentIdIsFinal()
    {
        return is_numeric($this->contentId);
    }

    private function deleteUsage()
    {
        $eduSoapClient = new EduSoapClient($this->config->get(Config::REPO_URL) . '/services/usage2?wsdl');
        $params = array(
            "eduRef" => $this->objecturl,
            "user" => null,
            "lmsId" => $this->config->get(Config::APP_ID),
            "courseId" => $this->contentId,
            "resourceId" => $this->uid
        );
        try {
            $eduSoapClient->deleteUsage($params);
        } catch (\SoapFault $e) {
            $this->logger->log(\TYPO3\CMS\Core\Log\LogLevel::ERROR, $e->faultString);
        }
    }

    private function dbDelete()
    {
        $connectionObject = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
            ->getConnectionForTable('tx_edusharing_object');
        $connectionObject->delete(
            'tx_edusharing_object',
            [
                'uid' => $this->uid
            ]
        );
    }

    private function dbInsert()
    {
        $connectionObject = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
            ->getConnectionForTable('tx_edusharing_object');
        $connectionObject->insert(
            'tx_edusharing_object',
            [
                'objecturl' => $this->objecturl,
                'contentid' => $this->contentId,
                'title' => $this->title,
                'version' => $this->version,
                'mimetype' => $this->mimetype
            ]
        );
        $this->uid = (int) $connectionObject->lastInsertId('tx_edusharing_object');
    }

    private function dbUpdateContentId()
    {
        $connectionObject = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
            ->getConnectionForTable('tx_edusharing_object');
        $connectionObject->update(
            'tx_edusharing_object',
            [
                'contentid' => $this->contentId,
            ],
            [
                'uid' => $this->uid
            ]
        );
    }

    private function setUsage()
    {
        $eduSoapClient = new EduSoapClient($this->config->get(Config::REPO_URL) . '/services/usage2?wsdl');
        $params = array(
            "eduRef" => $this->objecturl,
            "user" => $GLOBALS['BE_USER']->user['username'],
            "lmsId" => $this->config->get(Config::APP_ID),
            "courseId" => $this->contentId,
            "userMail" => $GLOBALS['BE_USER']->user['username'],
            "fromUsed" => '2002-05-30T09:00:00',
            "toUsed" => '2222-05-30T09:00:00',
            "distinctPersons" => '0',
            "version" => $this->version,
            "resourceId" => $this->uid,
            "xmlParams" => ''
        );

        try {
            $eduSoapClient->setUsage($params);
        } catch (\SoapFault $e) {
            $this->logger->log(\TYPO3\CMS\Core\Log\LogLevel::ERROR, $e->faultString);
            return false;
        }

        return true;
    }
}
