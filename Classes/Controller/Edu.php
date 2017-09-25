<?php

 namespace metaVentis\edusharing\Controller;

 class Edu extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

     public function getAppconfig(
         \Psr\Http\Message\ServerRequestInterface $request,
         \Psr\Http\Message\ResponseInterface $response
     ) {
         $response->getBody()->write(json_encode(array('repo_url' => \metaVentis\edusharing\Appconfig::$repo_url, 'app_url'=> \metaVentis\edusharing\Appconfig::$app_url, 'test' => 'testo')));
         return $response;
     }

     public function getTicket(
         \Psr\Http\Message\ServerRequestInterface $request,
         \Psr\Http\Message\ResponseInterface $response
     ) {
         $library =  new \metaVentis\edusharing\Library();
         $response->getBody()->write(json_encode($library->getTicket()));
         return $response;
     }

     public function getPreview(
         \Psr\Http\Message\ServerRequestInterface $request,
         \Psr\Http\Message\ResponseInterface $response
     ) {
         $library =  new \metaVentis\edusharing\Library();
         $response->getBody()->write(json_encode($library->getPreview($request->getQueryParams()['nodeId'])));
         return $response;
     }
 }