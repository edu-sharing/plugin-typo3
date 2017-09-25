<?php
namespace metaVentis\edusharing\Hook;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Search and replace in generated page using regular expressions
 */
class ContentPostProcAll
{

    /**
     * Search for a string and replace it with something else.
     * @param array $parameters Parameters delivered by the caller (tslib_fe)
     * @param TypoScriptFrontendController $parentObject The parent object (tslib_fe)
     * @return void
     */
    public function replaceContent(&$parameters, TypoScriptFrontendController $parentObject)
    {
        if (TYPO3_MODE === 'FE') {

            // Replace Content
            $parameters['pObj']->content = preg_replace(
                '/test/',
                'yeah',
                $parameters['pObj']->content
            );
        }
    }
}
