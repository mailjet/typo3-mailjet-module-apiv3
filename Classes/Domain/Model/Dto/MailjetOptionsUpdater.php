<?php

namespace Api\Mailjet\Domain\Model\Dto;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

class MailjetOptionsUpdater {

  /** @var string */
  protected $config_options;

  /** @var string */
  //$ext_key=$_EXTKEY;
  protected $ext_key = 'mailjet';

  public function __construct() {
    $this->config_options = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->ext_key]);
  }

  /**
   * @return string
   * @throws ApiKeyMissingException
   */
  public function saveConfiguration($key, $value) {
    $this->config_options[$key] = $value;

    $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->ext_key] = serialize($this->config_options);

    $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

    $version = VersionNumberUtility::getCurrentTypo3Version();
    $mainVersionNumber = VersionNumberUtility::convertVersionStringToArray($version)['version_main'];

    if ($mainVersionNumber >= 9) {
        $configuration = $objectManager->get(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class);
        $configuration->set($this->ext_key, $key, $value);
    }
    else {
        $configurationUtility = $objectManager->get(\TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility::class);
        $newConfiguration = $configurationUtility->getCurrentConfiguration($this->ext_key);
        \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($newConfiguration,  $this->config_options);
        $configurationUtility->writeConfiguration(
            $configurationUtility->convertValuedToNestedConfiguration($newConfiguration),
            $this->ext_key
        );
    }
  }

}