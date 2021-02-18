<?php

namespace Metaventis\Edusharing;

use Metaventis\Edusharing\Settings\Config;
use \TYPO3\CMS\Core\Utility\GeneralUtility;

class EduSoapClient extends \SoapClient
{

    public function __construct($wsdl, $options = array())
    {
        parent::__construct($wsdl, $options);
        $this->setSoapHeaders();
    }

    private function setSoapHeaders()
    {
        try {
            $config = GeneralUtility::makeInstance(Config::class);
            $timestamp = round(microtime(true) * 1000);
            $signData = $config->get(Config::APP_ID) . $timestamp;
            $signature = GeneralUtility::makeInstance(Ssl::class)->sign($signData);
            $signature = base64_encode($signature);
            $headers = array();
            $headers[] = new \SOAPHeader('http://webservices.edu_sharing.org', 'appId', $config->get(Config::APP_ID));
            $headers[] = new \SOAPHeader('http://webservices.edu_sharing.org', 'timestamp', $timestamp);
            $headers[] = new \SOAPHeader('http://webservices.edu_sharing.org', 'signature', $signature);
            $headers[] = new \SOAPHeader('http://webservices.edu_sharing.org', 'signed', $signData);
            parent::__setSoapHeaders($headers);
        } catch (\Exception $e) {
            throw new \Exception('Could not set soap headers - ' . $e->getMessage());
        }
    }
}
