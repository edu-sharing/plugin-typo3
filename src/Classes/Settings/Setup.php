<?php

namespace Metaventis\Edusharing\Settings;

use Exception;
use SimpleXMLElement;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Setup
{
    private $responseFactory;

    public function __construct()
    {
        $this->responseFactory = GeneralUtility::makeInstance(ResponseFactoryInterface::class);
    }

    public function runSetup(
        ServerRequestInterface $request
    ): ResponseInterface {
        if (!$this->isAdmin()) {
            $response = $this->responseFactory->createResponse()->withStatus(401);
            $response->getBody()->write('Could not confirm admin privileges for the current user');
            return $response;
        }
        $repoUrl = $request->getParsedBody()['repoUrl'];
        if (!$repoUrl) {
            $response = $this->responseFactory->createResponse()->withStatus(400);
            $response->getBody()->write('Missing or empty Repository URL');
            return $response;
        }
        try {
            $metadataUrl = $repoUrl . "/metadata?format=lms";
            $metadataXml = $this->download($metadataUrl);
            $repoProperties = $this->extractRepoProperties($metadataXml);
            $repoProperties['repo_url'] = $repoUrl;
            $this->writeRepoConfig($repoProperties);
            return $this->responseFactory->createResponse()->withStatus(202);
        } catch (Exception $error) {
            $response = $this->responseFactory->createResponse()->withStatus(500);
            $response->getBody()->write($error->getMessage());
            return $response;
        }
    }

    private function isAdmin()
    {
        if (!$GLOBALS['BE_USER']) {
            $GLOBALS['BE_USER'] = GeneralUtility::makeInstance(BackendUserAuthentication::class);
            $GLOBALS['BE_USER']->start();
        }
        return $GLOBALS['BE_USER']->isAdmin();;
    }

    private function download(string $url): string
    {
        $curlHandle = curl_init($url);
        curl_setopt($curlHandle, CURLOPT_FAILONERROR, 1);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($curlHandle);
        if (curl_errno($curlHandle)) {
            throw new Exception("Error when fetching $url: " . curl_error($curlHandle));
        }
        return $response;
    }

    private function extractRepoProperties(string $metadataXml): array
    {
        $metadata = new SimpleXMLElement($metadataXml);
        return [
            'repo_id' => $this->getSingleResult($metadata, 'entry[@key="appid"]'),
            'repo_public_key' => $this->getSingleResult($metadata, 'entry[@key="public_key"]'),
        ];
    }

    private function getSingleResult(SimpleXMLElement $xml, string $xpath): string
    {
        $result = $xml->xpath($xpath);
        if (sizeof($result) != 1) {
            throw new Exception('Expected exactly one result for ' . $xpath . ', got ' . sizeof($result));
        }
        return $result[0];
    }

    private function writeRepoConfig(array $repoProperties): void
    {
        $config = GeneralUtility::makeInstance(Config::class);
        $config->set(Config::REPO_ID, $repoProperties['repo_id']);
        $config->set(Config::REPO_URL, $repoProperties['repo_url']);
        $config->set(Config::REPO_PUBLIC_KEY, $repoProperties['repo_public_key']);
    }
}
