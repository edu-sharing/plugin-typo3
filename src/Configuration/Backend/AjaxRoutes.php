<?php
return [
    'edusharing_setup' => [
        'path' => '/edusharing/setup',
        'target' => \Metaventis\Edusharing\Settings\Setup::class . '::runSetup'
    ],
    'getAppconfig' => [
        'path' => '/edusharing/appconfig',
        'target' => \Metaventis\Edusharing\Controller\Edu::class . '::getAppconfig'
    ],
    'getTicket' => [
        'path' => '/edusharing/ticket',
        'target' => \Metaventis\Edusharing\Controller\Edu::class . '::getTicket'
    ],
    'getSavedSearch' => [
        'path' => '/edusharing/savedsearch',
        'target' => \Metaventis\Edusharing\Controller\Edu::class . '::getSavedSearch'
    ]
];
