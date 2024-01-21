<?php
if (!defined('TYPO3')) {
  //die('Access denied.');
}
if (empty($_EXTKEY)) {
    $_EXTKEY ='mailjet';
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
  'Mailjet',
  'Registration',
  'Mailjet'
);
