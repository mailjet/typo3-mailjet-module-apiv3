<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

//MIHA
if (!defined('TYPO3')) {
  die ('Access denied.');
}

if (empty($_EXTKEY)) {
    $_EXTKEY ='mailjet';
}

$settings = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
    \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
)->get('mailjet');


if ($settings['Send'] == 1 && $settings['sync_field'] == 'on') {

  $host = "in-v3.mailjet.com";
  $smtpPort = 587;
  $smtpSecure = 'tls';
  if (!empty($settings_keys['smtp_host'])) {
    $host = $settings_keys['smtp_host'];
  }
  if (!empty($settings_keys['smtp_secure'])) {
    $smtpSecure = $settings_keys['smtp_secure'];
  }
  if (!empty($settings_keys['smtp_port'])) {
    $smtpPort = $settings_keys['smtp_port'];
  }

  $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport'] = 'smtp';
  $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_server'] = $host . ':' . $smtpPort;
  $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_port'] = $smtpPort;
  $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_encrypt'] = $smtpSecure; // ssl, sslv3, tls
  $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_username'] = $settings['apiKeyMailjet'];
  $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_password'] = $settings['secretKey'];

}
else {
  $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport'] = 'mail';
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Mailjet',
    'Registration',
    [
        \Api\Mailjet\Controller\FormController::class => 'index,response,ajaxResponse',
    ],
    [
        \Api\Mailjet\Controller\FormController::class => 'index,response,ajaxResponse',
    ]
);

$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
$iconRegistry->registerIcon(
    'ext-mailjet-wizard-icon',
    \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
    ['source' => 'EXT:mailjet/ext_icon.png']
);
//if (TYPO3_MODE === 'BE') { MIHA
//if (ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend()) {
  /*if (\TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3_branch) >= \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger('7.0')) {

    $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
    $iconRegistry->registerIcon(
      'ext-mailjet-wizard-icon',
      \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
      ['source' => 'EXT:mailjet/ext_icon.png']
    );
  }*/

// Fully qualified class names for usage in ext_localconf.php / ext_tables.php
/*$backendConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
    \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
)->get('backend');*/


  // Page module hook
  $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info']['mailjet_registration']['mailjet'] =
    'Api\Mailjet\Hooks\Backend\PageLayoutViewHook->getExtensionSummary';


//}

ExtensionManagementUtility::addPageTSConfig(
    '@import "EXT:mailjet/Configuration/TSconfig/ContentElementWizard.tsconfig"'
);
//\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:mailjet/Configuration/TSconfig/ContentElementWizard.txt">');
