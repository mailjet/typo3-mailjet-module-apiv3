<?php


namespace Api\Mailjet\ViewHelpers;


use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class TemplatesViewHelper extends StandaloneView
{
    private $templatePath;

    public function __construct(ContentObjectRenderer $contentObject = null)
    {
        $this->templatePath = ExtensionManagementUtility::extPath('mailjet', 'Resources/Private/Templates/Emails/Subscription.html');
        parent::__construct($contentObject);
    }

    public function  getSubscriptionEmailTemplate(Array $params)
    {
        $this->setTemplatePathAndFilename($this->templatePath);

        return $this->renderSection('Subscription', $params );
    }


}