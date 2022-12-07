<?php

namespace Metaventis\Edusharing;

use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Usage;
use Metaventis\Edusharing\Settings\Config;
use Metaventis\Edusharing\Library;
use Metaventis\Edusharing\EduRestClient;

class EdusharingObject
{

    public $uid;
    public $nodeId;
    public $contentId;
    public $usageId;
    public $title;
    public $mimetype;
    public $version;
    public $logger;
    private $config;
    private $library;
    private $eduRestClient;

    public static function fromDbRow($row)
    {
        // Backwards compatibility to db entries from plugin version <= 2.
        $nodeId = $row['nodeId'] ?: EdusharingObject::objectUrlToNodeId($row['objecturl']);
        $contentId = $row['contentId'] ?: $row['contentid'];

        return new EdusharingObject(
            $row['uid'],
            $nodeId,
            $contentId,
            $row['usageId'],
            $row['title'],
            $row['mimetype'],
            $row['version']
        );
    }

    /**
     * Sets the content id to its final value and completes initialization.
     *
     * This should be called once for an edusharing object, that was instantiated with a temporary
     * content id and hence couldn't complete initialization (see `add()`).
     */
    public static function updateContentId(string $temporaryContentId, int $finalContentId)
    {
        $edusharingObjects = GeneralUtility::makeInstance(EdusharingObjectsInitMap::class)
            ->pop($temporaryContentId);
        foreach ($edusharingObjects as $edusharingObject) {
            // error_log("updateContentId " . $edusharingObject->uid);
            $edusharingObject->contentId = $finalContentId;
            $usage = $edusharingObject->setUsage();
            $edusharingObject->usageId = $usage->usageId;
            $edusharingObject->dbUpdate();
        }
    }

    private static function objectUrlToNodeId(string $objectUrl): string
    {
        return substr(
            $objectUrl,
            strrpos($objectUrl, '/') + 1
        );

    }

    public function __construct(
        $uid = '',
        $nodeId = '',
        $contentId = 0,
        $usageId = '',
        $title = '',
        $mimetype = '',
        $version = ''
    )
    {
        $this->uid = $uid;
        $this->nodeId = $nodeId;
        $this->contentId = $contentId;
        $this->usageId = $usageId;
        $this->title = $title;
        $this->mimetype = $mimetype;
        $this->version = $version;
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
        $this->config = GeneralUtility::makeInstance(Config::class);
        $this->library = GeneralUtility::makeInstance(Library::class);
        $this->eduRestClient = GeneralUtility::makeInstance(EduRestClient::class);
    }

    public function add()
    {
        if ($this->contentIdIsFinal()) {
            // First, create a database entry to obtain a `uid` needed for usage.
            $this->dbInsert();
            $this->usageId = $this->setUsage()->usageId;
            $this->dbUpdate();
        } else {
            // Typo3 provided us with a temporary content id that will change before being
            // persisted. In this case, we create a db entry to obtain a UID, but we don't set usage
            // for now. When we learn the final content id, we call `setUsage()` and update the db
            // entry.
            $temporaryContentId = $this->contentId;
            $this->contentId = 0;
            $this->usageId = 0;
            $this->dbInsert();
            GeneralUtility::makeInstance(EdusharingObjectsInitMap::class)
                ->push($temporaryContentId, $this);
        }
    }

    public function delete()
    {
        $this->deleteUsage();
        $this->dbDelete();
    }

    public function lookUpAndSaveUsageId(): void
    {
        // error_log("lookUpAndSaveUsageId");
        $ticket = $this->library->getTicket();
        try {
            $this->usageId = $this->eduRestClient->getUsageId(
                $ticket,
                $this->nodeId,
                $this->contentId,
                $this->uid,
            );
            $this->dbUpdate();
        } catch (\Exception $e) {
            $this->logger->log(LogLevel::ERROR, 'Failed to look up usage: ' . $e->getMessage());
            if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['displayErrors'] === 1) {
                throw $e;
            }
        }
    }

    private function contentIdIsFinal()
    {
        return is_numeric($this->contentId);
    }

    private function deleteUsage(): void
    {
        try {
            $this->eduRestClient->deleteUsage($this->nodeId, $this->usageId);
        } catch (\Exception $e) {
            $this->logger->log(LogLevel::ERROR, 'Failed to delete usage: ' . $e->getMessage());
            if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['displayErrors'] === 1) {
                throw $e;
            }
        }
    }

    private function dbDelete()
    {
        $connectionObject = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                \TYPO3\CMS\Core\Database\ConnectionPool::class
        )->getConnectionForTable('tx_edusharing_object');
        $connectionObject->delete(
            'tx_edusharing_object',
            [
                'uid' => $this->uid
            ]
        );
    }

    private function dbInsert()
    {
        $connectionObject = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                \TYPO3\CMS\Core\Database\ConnectionPool::class
        )->getConnectionForTable('tx_edusharing_object');
        $connectionObject->insert(
            'tx_edusharing_object',
            [
                'nodeId' => $this->nodeId,
                'contentId' => $this->contentId,
                'usageId' => $this->usageId,
                'title' => $this->title,
                'version' => $this->version,
                'mimetype' => $this->mimetype,
            ]
        );
        $this->uid = (int) $connectionObject->lastInsertId('tx_edusharing_object');
    }

    private function dbUpdate()
    {
        $connectionObject = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                \TYPO3\CMS\Core\Database\ConnectionPool::class
        )->getConnectionForTable('tx_edusharing_object');
        $connectionObject->update(
            'tx_edusharing_object',
            [
                'nodeId' => $this->nodeId,
                'contentId' => $this->contentId,
                'usageId' => $this->usageId,
                'title' => $this->title,
                'version' => $this->version,
                'mimetype' => $this->mimetype,
            ],
            [
                'uid' => $this->uid
            ]
        );
    }

    private function setUsage(): Usage
    {
        $ticket = $this->library->getTicket();
        $nodeId = $this->nodeId;
        $containerId = $this->contentId;
        $resourceId = $this->uid;
        $nodeVersion = $this->version;

        $usage = $this->eduRestClient->createUsage(
            $ticket,
            $containerId,
            $resourceId,
            $nodeId,
            $nodeVersion,
        );
        return $usage;
    }
}