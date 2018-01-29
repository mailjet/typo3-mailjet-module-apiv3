<?php

namespace Api\Mailjet\ViewHelpers;

use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;


class FooterDataViewHelper extends AbstractViewHelper {

  public function render() {
    /** @var PageRenderer $pageRenderer */
    $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
    $pageRenderer->addFooterData($this->renderChildren());
  }
}