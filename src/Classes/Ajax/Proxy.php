<?php

namespace Metaventis\Edusharing\Ajax;

use Exception;
use \Psr\Http\Message\ResponseFactoryInterface;
use \Psr\Http\Message\ResponseInterface;
use \Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Metaventis\Edusharing\Library;
use Metaventis\Edusharing\EduRestClient;
use Metaventis\Edusharing\EdusharingObject;

class Proxy
{
    private $responseFactory;
    private $library;
    private $eduRestClient;

    public function __construct()
    {
        $this->responseFactory = GeneralUtility::makeInstance(ResponseFactoryInterface::class);
        $this->library = GeneralUtility::makeInstance(Library::class);
        $this->eduRestClient = GeneralUtility::makeInstance(EduRestClient::class);
    }

    public function __invoke(
        ServerRequestInterface $request
    ): ResponseInterface {
        $queryParams = $request->getQueryParams();
        $uid = $queryParams['data-edusharing_uid'];
        if (empty($uid)) {
            return $this->createResponse(400, 'Missing parameter "data-edusharing_uid"');
        }
        $eduObject = $this->getEduObject($uid);
        if (empty($eduObject->nodeId)) {
            return $this->createResponse(400, 'Fehlerhaftes Objekt');
        } else if (empty($eduObject->usageId)) {
            // If we wanted to do the migration here, we would have to get a ticket for the owner of
            // the page.
    
            // if (empty($eduObject->usageId)) {
            //     $eduObject->lookUpAndSaveUsageId();
            // }
            return $this->createResponse(400, 'Missing usageId. The page needs to be saved once by its owner to complete migration.');
        }

        // TODO: check if this is still needed
        if ($queryParams['edusharing_external']) {
            $url = $this->library->getContenturl($eduObject, 'window');
            return $this->createRedirectResponse($url);
        }

        if ($queryParams['data-edusharing_mediatype'] == 'saved_search') {
            $html = $this->library->getSavedSearch(
                $eduObject->nodeId,
                $queryParams['data-edusharing_savedsearch_limit'],
                0,
                $queryParams['data-edusharing_savedsearch_sortproperty'],
                $queryParams['data-edusharing_savedsearch_template']
            );
            $html = json_decode($html) . '<div style="clear:both"></div>';
        } else {
            $html = $this->getRenderHtml($eduObject);
        }
        $html = $this->insertProxyUrl($html, $eduObject);
        return $this->createResponse(200, $html);
    }

    private function getEduObject(int $uid): EdusharingObject
    {
        $row = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_edusharing_object')
            ->select(
                ['*'],
                'tx_edusharing_object',
                [
                    'uid' => $uid
                ]
            )
            ->fetch();
        $eduSharingObject = EdusharingObject::fromDbRow($row);
        return $eduSharingObject;
    }

    private function getRenderHtml(EdusharingObject $eduObject)
    {
        // error_log('getRenderHtml ' . serialize($eduObject));
        $node = $this->eduRestClient->getNodeByUsage(
            $eduObject->nodeId,
            $eduObject->version,
            $eduObject->contentId ,
            $eduObject->uid,
            $eduObject->usageId
        );
        return $node['detailsSnippet'];
    }

    private function insertProxyUrl(string $html, EdusharingObject $eduObj): string
    {
        $html = str_replace(array("\r\n", "\r", "\n"), '', $html);
        $html = str_replace(
            "{{{LMS_INLINE_HELPER_SCRIPT}}}",
            "index.php?eID=edusharing_proxy&edusharing_external=true&data-edusharing_uid="
                . $eduObj->uid,
            $html
        );
        return $html;
    }

    private function createResponse(int $status, string $body): ResponseInterface
    {
        $response = $this->responseFactory->createResponse()->withStatus($status);
        $response->getBody()->write($body);
        return $response;
    }

    private function createRedirectResponse(string $url): ResponseInterface
    {
        return $this->responseFactory->createResponse()
            ->withStatus(302)
            ->withHeader('Location', $url);
    }
}
