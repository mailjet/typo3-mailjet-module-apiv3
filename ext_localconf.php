<?php

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

if (!defined('TYPO3_MODE')) {
  die ('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
  'Api.' . $_EXTKEY,
  'Registration',
  [
    'Form' => 'index,response,ajaxResponse',
  ],
  [
    'Form' => 'index,response,ajaxResponse',
  ]
);


if (TYPO3_MODE === 'BE') {
  if (\TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3_branch) >= \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger('7.0')) {
    /** @var \TYPO3\CMS\Core\Imaging\IconRegistry $iconRegistry */
    $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
    $iconRegistry->registerIcon(
      'ext-mailjet-wizard-icon',
      \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
      ['source' => 'EXT:mailjet/ext_icon.png']
    );
  }


  // Page module hook
  $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info'][$_EXTKEY . '_registration'][$_EXTKEY] =
    'Api\Mailjet\Hooks\Backend\PageLayoutViewHook->getExtensionSummary';


}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:mailjet/Configuration/TSconfig/ContentElementWizard.txt">');
