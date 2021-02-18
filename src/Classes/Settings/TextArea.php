<?php

namespace Metaventis\Edusharing\Settings;

use TYPO3\CMS\Install\ViewHelpers\Form\TypoScriptConstantsViewHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;


class TextArea
{

    protected $tag = NULL;

    function __construct()
    {
        $this->tag = GeneralUtility::makeInstance('TYPO3Fluid\\Fluid\\Core\\ViewHelper\\TagBuilder');
    }

    public function render(array $parameter, TypoScriptConstantsViewHelper $viewHelper)
    {
        $this->tag->setTagName('textarea');
        $this->tag->forceClosingTag(TRUE);
        $this->tag->addAttribute('cols', 70);
        $this->tag->addAttribute('rows', 7);
        $this->tag->addAttribute('name', $parameter['fieldName']);
        $this->tag->addAttribute('id', 'em-' . $parameter['fieldName']);
        if ($parameter['fieldValue'] !== NULL) {
            $this->tag->setContent(trim($parameter['fieldValue']));
        }
        return $this->tag->render();
    }
}
