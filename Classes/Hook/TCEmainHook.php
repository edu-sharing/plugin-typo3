<?php

namespace Metaventis\Edusharing\Hook;

use TYPO3\CMS\Core\DataHandling\DataHandler;
use Metaventis\Edusharing\EdusharingObject;

class TCEmainHook
{

    private $contentId;

    private function processContent($content)
    {
        $formerObjects = array();
        $rows = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
            ->getConnectionForTable('tx_edusharing_object')
            ->select(
                ['*'],
                'tx_edusharing_object',
                ['contentid' => $this->contentId]
            )
            ->fetchAll();

        foreach ($rows as $row) {
            $formerObjects[$row['uid']] = new EdusharingObject(
                $row['nodeid'],
                $row['contentid'],
                $row['title'],
                $row['mimetype'],
                $row['version'],
                $row['uid']
            );
        }

        if ($content) {
            $doc = new \DOMDocument();
            $doc->loadHTML($content);
            $xpath = new \DOMXpath($doc);
            $elements = $xpath->query('//*[@data-edusharing_uid]');
            foreach ($elements as $element) {
                $eduSharingObject = new EdusharingObject(
                    $element->getAttribute('data-edusharing_nodeid'),
                    $this->contentId,
                    $element->getAttribute('data-edusharing_title'),
                    $element->getAttribute('data-edusharing_mimetype'),
                    $element->getAttribute('data-edusharing_version')
                );
                if (empty($element->getAttribute('data-edusharing_uid'))) {
                    $eduSharingObject->add();
                    $element->setAttribute('data-edusharing_uid', $eduSharingObject->uid);
                    // Our new object might share a DB entry with an existing one. We don't want to delete it.
                    unset($formerObjects[$eduSharingObject->uid]);
                } else {
                    if ($eduSharingObject->exists()) {
                        unset($formerObjects[$eduSharingObject->uid]);
                    } else {
                        error_log("Encountered a reference to an Edusharing element which doesn't exist in our database!");
                    }
                }
            }

            $content = $doc->saveHTML();
        }

        foreach ($formerObjects as $object) {
            $object->delete();
        }

        return $content;
    }

    public function processDatamap_preProcessFieldArray(array &$fieldArray, $table, $id, DataHandler &$pObj)
    {
        if ($table != 'tt_content') {
            return;
        }
        $this->contentId = $id;
        $processedContent = $this->processContent($fieldArray['bodytext']);
        if ($processedContent) {
            $fieldArray['bodytext'] = $processedContent;
        }
    }

    // The `$id` provided in `processDatamap_preProcessFieldArray` might have been a temporary id that will be replaced now. Update our
    // records accordingly.
    public function processDatamap_afterDatabaseOperations($status, $table, $id, array $fieldArray, DataHandler &$pObj)
    {
        if ($table != 'tt_content') {
            return;
        }
        if (!is_numeric($id)) {
            EdusharingObject::updateContentId($id, $pObj->substNEWwithIDs[$id]);
        }
    }
}
