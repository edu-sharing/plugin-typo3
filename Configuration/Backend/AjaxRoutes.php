<?php
return [
    'getAppconfig' => [
        'path' => '/edusharing/appconfig',
        'target' => \Metaventis\Edusharing\Controller\Edu::class . '::getAppconfig'
    ],
    'getTicket' => [
        'path' => '/edusharing/ticket',
        'target' => \Metaventis\Edusharing\Controller\Edu::class . '::getTicket'
    ]
];
