<?php

namespace Metaventis\Edusharing\Hook;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Metaventis\Edusharing\EdusharingObject;

class TCEmainHook
{

    private $contentId;

    private function processContent($content)
    {
        $formerObjects = array();
        $rows = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_edusharing_object')
            ->select(
                ['*'],
                'tx_edusharing_object',
                ['contentId' => $this->contentId]
            )
            ->fetchAll();

        foreach ($rows as $row) {
            $eduSharingObject = EdusharingObject::fromDbRow($row);
            if (empty($eduSharingObject->usageId)) {
                $eduSharingObject->lookUpAndSaveUsageId();
            }
            $formerObjects[$row['uid']] = $eduSharingObject;
        }

        if ($content) {
            $doc = new \DOMDocument();
            $doc->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
            $xpath = new \DOMXpath($doc);
            $elements = $xpath->query('//*[@data-edusharing_uid]');
            foreach ($elements as $element) {
                $eduSharingObject = new EdusharingObject(
                    $element->getAttribute('data-edusharing_uid'),
                    $element->getAttribute('data-edusharing_node-id'),
                    $this->contentId,
                    // We don't save the usage ID as attribute and we don't need it here.
                    '',
                    $element->getAttribute('data-edusharing_title'),
                    $element->getAttribute('data-edusharing_mimetype'),
                    $element->getAttribute('data-edusharing_version')
                );
                // Add
                if (empty($element->getAttribute('data-edusharing_uid'))) {
                    // error_log("Add new object: " . $eduSharingObject->nodeId);
                    $eduSharingObject->add();
                    $element->setAttribute('data-edusharing_uid', $eduSharingObject->uid);
                }
                // Edit
                else if ($element->getAttribute('data-edusharing_edited') == 'true') {
                    // error_log("Edit existing object: " . $eduSharingObject->nodeId);
                    $element->removeAttribute('data-edusharing_edited');
                    $eduSharingObject->add();
                    $element->setAttribute('data-edusharing_uid', $eduSharingObject->uid);
                }
                // Keep untouched.
                //
                // TODO: add `usageId`s to existing objects if not present (migration)
                else if ($formerObjects[$eduSharingObject->uid]) {
                    // error_log("Leave existing object: " . $eduSharingObject->nodeId);

                    // Migrate old entries, that don't have the `node-id` attribute (needed for
                    // preview images in the editor).
                    if (empty($eduSharingObject->nodeId)) {
                        $element->setAttribute(
                            'data-edusharing_node-id',
                                $formerObjects[$eduSharingObject->uid]->nodeId
                        );
                    }

                    unset($formerObjects[$eduSharingObject->uid]);
                }
                // The `img` elements are only used as images inside the editor. They always need a
                // fresh ticket as parameter in their `src` attribute to work. In the final page
                // view, the `img` elements are replaced with rendered content.
                //
                // We could basically remove the `src` attribute, but the editor will discard our
                // image when we do. So we just strip the ticket parameter.
                $element->setAttribute('src', strtok($element->getAttribute('src'), '&'));
            }
            // Remove tags added by ::loadHTML().
            $content = str_replace(array('<html>', '</html>', '<body>', '</body>'), '', $doc->saveHTML());
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

    // When an entire page is deleted, delete all usages of objects on that page.
    public function processCmdmap_deleteAction(
        $table,
        $id,
        array $record,
        &$recordWasDeleted,
        DataHandler $dataHandler
    )
    {
        if ($table != 'tt_content') {
            return;
        }
        $rows = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_edusharing_object')
            ->select(
                ['*'],
                'tx_edusharing_object',
                ['contentId' => $id]
            )
            ->fetchAll();
        foreach ($rows as $row) {
            $eduSharingObject = EdusharingObject::fromDbRow($row);
            $eduSharingObject->delete();
        }
    }
}