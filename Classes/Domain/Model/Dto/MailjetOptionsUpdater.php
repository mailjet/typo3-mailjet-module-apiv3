<?php

namespace Api\Mailjet\Domain\Model\Dto;

use Api\Mailjet\Exception\ApiKeyMissingException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class MailjetOptionsUpdater
{

    /** @var string */
    protected $config_options;

    /** @var string */
    //$ext_key=$_EXTKEY;
    protected $ext_key = 'mailjet';

    public function __construct()
    {
        $this->config_options = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
        )->get('mailjet');
    }

    /**
     * @return void
     * @throws ApiKeyMissingException
     */
    public function saveConfiguration($key, $value)
    {
        $this->config_options[$key] = $value;

        $this->config_options = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
        )->set(serialize($this->config_options));
    }

}
