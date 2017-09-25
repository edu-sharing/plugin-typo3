<?php
return [
    'getAppconfig' => [
        'path' => '/edusharing/appconfig',
        'target' => \metaVentis\edusharing\Controller\Edu::class . '::getAppconfig'
    ],
    'getTicket' => [
        'path' => '/edusharing/ticket',
        'target' => \metaVentis\edusharing\Controller\Edu::class . '::getTicket'
    ]
];