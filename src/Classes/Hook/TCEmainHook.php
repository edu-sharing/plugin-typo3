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
                $row['objecturl'],
                $row['contentid'],
                $row['title'],
                $row['mimetype'],
                $row['version'],
                $row['uid']
            );
        }

        if ($content) {
            $doc = new \DOMDocument();
            $doc->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
            $xpath = new \DOMXpath($doc);
            $elements = $xpath->query('//*[@data-edusharing_uid]');
            foreach ($elements as $element) {
                $eduSharingObject = new EdusharingObject(
                    $element->getAttribute('data-edusharing_objecturl'),
                    $this->contentId,
                    $element->getAttribute('data-edusharing_title'),
                    $element->getAttribute('data-edusharing_mimetype'),
                    $element->getAttribute('data-edusharing_version')
                );
                // Add
                if (empty($element->getAttribute('data-edusharing_uid'))) {
                    $eduSharingObject->add();
                    $element->setAttribute('data-edusharing_uid', $eduSharingObject->uid);
                }
                // Edit
                else if ($element->getAttribute('data-edusharing_edited') == 'true') {
                    $element->removeAttribute('data-edusharing_edited');
                    $eduSharingObject->add();
                    $element->setAttribute('data-edusharing_uid', $eduSharingObject->uid);
                }
                // Keep untouched
                else if ($eduSharingObject->exists()) {
                    unset($formerObjects[$eduSharingObject->uid]);
                }
            }
            $content = str_replace(array('<html>', '<body>'), '', $doc->saveHTML()); // Remove tags added by ::loadHTML()
        }

        // Delete
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
