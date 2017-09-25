<?php
namespace metaVentis\edusharing\Hook;

use metaVentis\edusharing;

class TCEmainHook
{

    private $contentId;

    private function processContent($content) {
        try {
            $formerObjects =  array();
            $rows = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
                ->getConnectionForTable('tx_edusharing_object')
                ->select(
                    ['*'],
                    'tx_edusharing_object',
                    ['contentid' => $this->contentId]
                )
                ->fetchAll();

            foreach ($rows as $row) {
                $formerObjects[$row['uid']] = new edusharing\EdusharingObject(
                    $row['nodeid'],
                    $row['contentid'],
                    $row['title'],
                    $row['mimetype'],
                    $row['version'],
                    $row['uid']
                );
            }

            if($content) {
                $doc = new \DOMDocument();
                $doc->loadHTML($content);
                $xpath = new \DOMXpath($doc);
                $elements = $xpath->query('//*[@data-edusharing_uid]');
                foreach ($elements as $element) {
                    $eduSharingObject = new edusharing\EdusharingObject(
                        $element->getAttribute('data-edusharing_nodeid'),
                        $this->contentId,
                        $element->getAttribute('data-edusharing_title'),
                        $element->getAttribute('data-edusharing_mimetype'),
                        $element->getAttribute('data-edusharing_version')
                    );
                    if (empty($element->getAttribute('data-edusharing_uid'))) {
                        if ($eduSharingObject->add())
                            $element->setAttribute('data-edusharing_uid', $eduSharingObject->uid);
                    } else {
                            if ($eduSharingObject->exists()) {
                                    unset($formerObjects[$eduSharingObject->uid]);
                            } /*else {
                                if ($eduSharingObject->add()) {
                                    $element->setAttribute('data-edusharing_uid', $eduSharingObject->uid);
                                }
                            }*/
                       }
                }

                $content = $doc->saveHTML();
            }

            foreach($formerObjects as $object) {
             $object -> delete();
            }

            return $content;

        } catch(\Exception $e) {
            return false;
        }
    }

    public function processDatamap_preProcessFieldArray(array &$fieldArray, $table, $id, \TYPO3\CMS\Core\DataHandling\DataHandler &$pObj) {
        $this->contentId = $id;
        $processedContent = $this->processContent($fieldArray['bodytext']);
        if($processedContent)
            $fieldArray['bodytext'] = $processedContent;
    }
}
