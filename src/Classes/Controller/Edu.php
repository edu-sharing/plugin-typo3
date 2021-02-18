<?php

namespace Metaventis\Edusharing\Controller;

use \Psr\Http\Message\ServerRequestInterface;
use \Psr\Http\Message\ResponseFactoryInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Metaventis\Edusharing\Settings\Config;

class Edu extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    private $responseFactory;

    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    public function getAppconfig(
        ServerRequestInterface $request
    ) {
        $config = GeneralUtility::makeInstance(Config::class);
        $response = $this->responseFactory->createResponse();
        $response->getBody()->write(json_encode(array(
            'repo_url' => $config->get(Config::REPO_URL),
            'app_url' => $config->get(Config::APP_URL)
        )));
        return $response;
    }

    public function getTicket(
        ServerRequestInterface $request
    ) {
        $library = new \Metaventis\Edusharing\Library();
        $response = $this->responseFactory->createResponse();
        $response->getBody()->write($library->getTicket());
        return $response;
    }

    public function getSavedSearch(
        ServerRequestInterface $request
    ) {
        $data = $request->getParsedBody();
        $library = new \Metaventis\Edusharing\Library();
        $response = $this->responseFactory->createResponse();
        $response->getBody()
            ->write(json_encode(
                $library->getSavedSearch(
                    $data['nodeId'],
                    $data['maxItems'],
                    $data['skipCount'],
                    $data['sortProperty'],
                    $data['template']
                )
            ));
        return $response;
    }
}
