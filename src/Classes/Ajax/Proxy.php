<?php

namespace Metaventis\Edusharing\Ajax;

use Exception;
use \Psr\Http\Message\ResponseFactoryInterface;
use \Psr\Http\Message\ResponseInterface;
use \Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Metaventis\Edusharing\Library;

class Proxy
{
    private $responseFactory;
    private $library;

    public function __construct()
    {
        $this->responseFactory = GeneralUtility::makeInstance(ResponseFactoryInterface::class);
        $this->library = GeneralUtility::makeInstance(Library::class);
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
        if (empty($eduObject['objecturl'])) {
            return $this->createResponse(400, 'Fehlerhaftes Objekt');
        }

        $eduObject['nodeid'] = substr(
            $eduObject['objecturl'],
            strrpos($eduObject['objecturl'], '/') + 1
        );

        if ($queryParams['edusharing_external']) {
            $url = $this->library->getContenturl($eduObject, 'window');
            return $this->createRedirectResponse($url);
        }

        if ($queryParams['data-edusharing_mediatype'] == 'saved_search') {
            $html = $this->library->getSavedSearch(
                $eduObject['nodeid'],
                $queryParams['data-edusharing_savedsearch_limit'],
                0,
                $queryParams['data-edusharing_savedsearch_sortproperty'],
                $queryParams['data-edusharing_savedsearch_template']
            );
            $html = json_decode($html) . '<div style="clear:both"></div>';
        } else {
            $url = $this->library->getContenturl($eduObject, 'inline');
            $html = $this->getRenderHtml($url);
        }
        $html = $this->insertProxyUrl($html, $eduObject);
        return $this->createResponse(200, $html);
    }

    private function getEduObject(int $uid): array
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_edusharing_object')
            ->select(
                ['*'],
                'tx_edusharing_object',
                [
                    'uid' => $uid
                ]
            )
            ->fetch();
    }

    private function getRenderHtml(string $url)
    {
        $curl_handle = curl_init($url);
        curl_setopt($curl_handle, CURLOPT_FAILONERROR, 1);
        curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl_handle, CURLOPT_HEADER, 0);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_handle, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl_handle, CURLOPT_SSL_VERIFYHOST, false);
        $inline = curl_exec($curl_handle);
        if (curl_errno($curl_handle)) {
            throw new Exception("Error when fetching $url: " . curl_error($curl_handle));
        }
        curl_close($curl_handle);
        return $inline;
    }

    private function insertProxyUrl(string $html, $eduObj): string
    {
        $html = str_replace(array("\r\n", "\r", "\n"), '', $html);
        $html = str_replace(
            "{{{LMS_INLINE_HELPER_SCRIPT}}}",
            "index.php?eID=edusharing_proxy&edusharing_external=true&data-edusharing_uid="
                . $eduObj['uid'],
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
