<?php

use Metaventis\Edusharing\Settings\Config;
use Metaventis\Edusharing\Ssl;

class ApplicationXml
{

    private $xml;

    public function printXml(): void
    {
        $this->generateXml();
        header('Content-Type: text/xml');
        print(html_entity_decode($this->xml->asXML()));
    }

    private function addEntry(string $key, string $value): void
    {
        $this->xml
            ->addChild('entry', $value)
            ->addAttribute('key', $key);
    }

    private function generateXml(): void
    {
        $config = Config::getInstance();
        $ssl = Ssl::getInstance();
        $this->xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="utf-8" ?>' .
                '<!DOCTYPE properties SYSTEM "http://java.sun.com/dtd/properties.dtd">' .
                '<properties></properties>'
        );

        $domain = parse_url($config->get(Config::APP_URL), PHP_URL_HOST);
        $this->addEntry('appid', $config->get(Config::APP_ID));
        $this->addEntry('type', 'CMS');
        $this->addEntry('subtype', 'typo3');
        $this->addEntry('domain', $domain);
        $this->addEntry('host', gethostbyname($domain));
        $this->addEntry('trustedclient', 'true');
        $this->addEntry('public_key', $ssl->getPublicKey());
        $this->addEntry('appcaption', 'Typo3');
    }
}

(new ApplicationXml())->printXml();
