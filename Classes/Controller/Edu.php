<?php

namespace Metaventis\Edusharing\Controller;

use Metaventis\Edusharing\Settings\Config;

class Edu extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{

    public function getAppconfig(
        \Psr\Http\Message\ServerRequestInterface $request,
        \Psr\Http\Message\ResponseInterface $response
    ) {
        $config = Config::getInstance();
        $response->getBody()->write(json_encode(array(
            'repo_url' => $config->get(Config::REPO_URL),
            'app_url' => $config->get(Config::APP_URL)
        )));
        return $response;
    }

    public function getTicket(
        \Psr\Http\Message\ServerRequestInterface $request,
        \Psr\Http\Message\ResponseInterface $response
    ) {
        $library =  new \Metaventis\Edusharing\Library();
        $backendUser = $GLOBALS['BE_USER']->user;
        $response->getBody()->write(json_encode($library->getTicket($backendUser)));
        return $response;
    }
}
