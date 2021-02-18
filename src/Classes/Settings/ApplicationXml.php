<?php

namespace Metaventis\Edusharing\Settings;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use SimpleXMLElement;

use Metaventis\Edusharing\Ssl;

class ApplicationXml
{
    private $responseFactory;
    private $config;
    private $ssl;
    private $xml;

    public function __construct()
    {
        $this->responseFactory = GeneralUtility::makeInstance(ResponseFactoryInterface::class);
        $this->config = GeneralUtility::makeInstance(Config::class);
        $this->ssl = GeneralUtility::makeInstance(Ssl::class);
    }

    public function printXml(): ResponseInterface
    {
        $this->generateXml();
        $response = $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'application/xml; charset=utf-8');
        $xml = html_entity_decode($this->xml->asXML());
        $response->getBody()->write($xml);
        return $response;
    }

    private function addEntry(string $key, string $value): void
    {
        $this->xml
            ->addChild('entry', $value)
            ->addAttribute('key', $key);
    }

    private function generateXml(): void
    {
        $this->xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="utf-8" ?>' .
                '<!DOCTYPE properties SYSTEM "http://java.sun.com/dtd/properties.dtd">' .
                '<properties></properties>'
        );

        $domain = parse_url($this->config->get(Config::APP_URL), PHP_URL_HOST);
        $this->addEntry('appid', $this->config->get(Config::APP_ID));
        $this->addEntry('type', 'CMS');
        $this->addEntry('subtype', 'typo3');
        $this->addEntry('domain', $domain);
        $this->addEntry('host', gethostbyname($domain));
        $this->addEntry('trustedclient', 'true');
        $this->addEntry('public_key', $this->ssl->getPublicKey());
        $this->addEntry('appcaption', 'Typo3');
    }
}
