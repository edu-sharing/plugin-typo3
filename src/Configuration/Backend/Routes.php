<?php

return [
    'edusharing_setup' => [
        'path' => '/edusharing/setup',
        'access' => 'public',
        'target' => \Metaventis\Edusharing\Settings\Setup::class . '::runSetup'
    ],

];
