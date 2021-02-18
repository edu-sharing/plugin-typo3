<?php

// Display error messages and stack traces in the frontend.
//
// Also, this plugin will read this variable and trigger some additional exceptions, that would be
// silently logged as errors in production mode.
$GLOBALS['TYPO3_CONF_VARS']['SYS']['displayErrors'] = 1;
