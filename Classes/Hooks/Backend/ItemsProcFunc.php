<?php

namespace Api\Mailjet\Hooks\Backend;

use Api\Mailjet\Service\ApiService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use DrewM\Mailjet\MailJet;

class ItemsProcFunc {

  /** @var ApiService */
  protected $api;

  protected $api_mailjet;

  public function __construct() {
    $this->api = GeneralUtility::makeInstance('Api\\Mailjet\Service\ApiService');
    require_once(ExtensionManagementUtility::extPath('mailjet', 'Resources/Private/Contrib/Mailjet/Mailjet.php'));
    $settings = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['mailjet']);

    $this->api_mailjet = new Mailjet($settings['apiKeyMailjet'], $settings['secretKey']);
  }


  public function getLists(array &$config) {

    try {

      $mailjet = $this->api_mailjet;
      $params = [
        'method' => 'GET',
        'limit' => 50,
      ];
      $contact_lists = $mailjet->contactslist($params)->getResponse();
 
      if ($contact_lists == NULL) {
        echo "<div style='color:red;font-size:33px;font-family:Verdana,Arial,Helvetica,sans-serif;'>Please enter correct API KEYS!</div>";
        exit;
      }

      $lists = [];
      $counter_contact = 0;
      if (!empty($contact_lists) && is_array($contact_lists->Data)) {
        foreach ($contact_lists->Data as $list) {
          $lists[$list->ID] = $list->Name;
          $counter_contact++;
        }
      }

      foreach ($lists as $id => $value) {
        $title = sprintf('%s [%s]', $value, $id);
        array_push($config['items'], [$title, $id]);
      }
    } catch (\Exception $e) {
      //
    }
  }

  /**
   * Get interests of a given list
   *
   * @param array $config
   */
  public function getProperties(array &$config) {
    try {
      $properties = [];
      $mj = $this->api_mailjet;

      $params = [
        'method' => 'GET',
        'limit' => 0,
      ];
      $response = $mj->ContactMetaData($params)->getResponse();
      if ($response && isset($response->Count) && $response->Count >= 0) {
        foreach ($response->Data as $property) {
          $properties[$property->Name] = $property->Name;
        }
      }

     asort($properties);

      foreach ($properties as $id => $value) {
        $name = sprintf('%s [%s]', $value, $id);
        array_push($config['items'], [$name, $id]);
      }
    } catch (\Exception $e) {
      //
    }
  }
}