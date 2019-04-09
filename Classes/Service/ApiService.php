<?php

namespace Api\Mailjet\Service;

use Api\Mailjet\Domain\Model\Dto\FormDto;
use Api\Mailjet\Exception\GeneralException;
use Api\Mailjet\Exception\MemberExistsException;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Log\Logger;

use DrewM\Mailjet\MailJet;

class ApiService {

  /** @var api */
  protected $api;

  /** @var $logger Logger */
  protected $logger;

  /** @var api keys */
  protected $api_mailjet;

  public function __construct() {

    require_once(ExtensionManagementUtility::extPath('mailjet', 'Resources/Private/Contrib/Mailjet/Mailjet.php'));

    /** @var \Api\Mailjet\Domain\Model\Dto\ExtensionConfiguration $extensionConfiguration */
    require_once(ExtensionManagementUtility::extPath('mailjet', 'Resources/Private/Contrib/Mailjet/Mailjet.php'));
    $settings = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['mailjet']);
    $this->api_mailjet = new Mailjet($settings['apiKeyMailjet'], $settings['secretKey']);

    $this->logger = GeneralUtility::makeInstance('TYPO3\CMS\Core\Log\LogManager')
      ->getLogger(__CLASS__);

  }


  /**
   * Get the user infos.
   *
   * @return boolean
   */
  public function mailjet_user_infos() {
    $mailjet = $this->api_mailjet;
    if (!$mailjet) {

      return FALSE;
    }
    $paramsProfile = [
      'method' => 'GET',
    ];
    $response = $mailjet->myprofile($paramsProfile)->getResponse();

    $paramsUser = [
      'method' => 'GET',
    ];
    $responseUser = $mailjet->user($paramsUser)->getResponse();

    if ($response && isset($response->Count) && $response->Count > 0) {
      return array_merge((array) $response->Data[0], (array) $responseUser->Data[0]);
    }
    else {
      return FALSE;
    }
  }


  /**
   * Update the user infos.
   *
   * @param unknown $infos
   *
   * @return boolean
   */
  public function mailjet_update($infos) {
    $mailjet = $this->api_mailjet;
    $infos['method'] = 'PUT';
    $response = $mailjet->myprofile($infos)->getResponse();
    return !empty($response->Count) ? TRUE : FALSE;
  }

  /**
   * User tracking check.
   */
  public function mailjet_user_trackingcheck() {
    $mailjet = $this->api_mailjet;
    if (!$mailjet) {
      return FALSE;
    }
    $params = [
      'APIKey' => $mailjet->getAPIKey(),
    ];
    $response = $mailjet->eventcallbackurl($params)->getResponse();

    if ($response && isset($response->Count) && $response->Count > 0) {
      return $response;
    }
    elseif ($response && isset($response->Count) && $response->Count == 0) {
      return $response;
    }
    else {
      return FALSE;
    }
  }


  /**
   * Returns a list of trusted domains.
   */
  public function mailjet_user_domain_list() {
    $mailjet = $this->api_mailjet;
    $params = [
      'method' => 'GET',
      'style' => 'full',
      'limit' => 0,
    ];
    $response = $mailjet->sender($params)->getResponse();
    if ($response && isset($response->Count) && $response->Count > 0) {
      $domains = [];
      foreach ($response->Data as $sender) {
        if (!empty($sender->DNS) and !array_key_exists($sender->DNS->Domain, $domains) && strpos($sender->Email->Email, "*@") !== FALSE) {
          $domains[$sender->DNS->Domain] = $sender;
        }
      }
      return $domains;
    }
    else {
      return FALSE;
    }
  }


  /**
   * Gets trusted email domains.
   */
  public function mailjet_get_a_trusted_email() {
    $mailjet = $this->api_mailjet;

    $params = [
      'method' => 'GET',
      'style' => 'full',
    ];
    $response = $mailjet->sender($params)->getResponse();

    if ($response && isset($response->Count) && $response->Count > 0) {
      foreach ($response->Data as $email_object) {
        if ($email_object->Status == 'Active') {
          return $email_object->Email->Email;
        }
      }
    }

    return FALSE;
  }


  /**
   * Add a trusted domain
   *
   * @param unknown $domain
   *
   * @return boolean
   */
  public function mailjet_user_domain_add($domain) {
    $mailjet = $this->api_mailjet;
    if (strpos($domain, '@') === FALSE) {
      $domain = '*@' . $domain;
    }

    $params = [
      'method' => 'JSON',
      'Email' => $domain,
    ];

    $response = $mailjet->sender($params)->getResponse();
    if ($response && isset($response->Count) && $response->Count > 0) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Check a domain
   *
   * @param unknown $domain
   *
   * @return boolean
   */
  public function mailjet_user_domain_status($domain) {
    $mailjet = $this->api_mailjet;

    if (strpos($domain, '@') === FALSE) {
      $domain = '*@' . $domain;
    }

    $params = [
      'method' => 'JSON',
      'Email' => $domain,
    ];

    $response = $mailjet->sender($params)->getResponse();

    if ($response && isset($response->Count) && $response->Count > 0) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }


  // Getter functions - LISTS and Properties
  public function mailjet_get_lists() {
    $mailjet = $this->api_mailjet;
    $contact_lists2 = [];
    $counter_contact = 0;
    $params = [
      'method' => 'GET',
      'limit' => 50,
    ];
    $contact_lists = $mailjet->contactslist($params)->getResponse();
    if (!empty($contact_lists) && is_array($contact_lists->Data)) {
      foreach ($contact_lists->Data as $list) {
        $contact_lists2[$list->ID] = $list->Name;
        $counter_contact++;
      }
    }

    return $contact_lists2;
  }

  public function mailjet_get_propertiy_name($property_name) {
    //get existing contact properties from MailJet
    $properties = [];
    $mj = $this->api_mailjet;
    $params = [
      'method' => 'GET',
      'limit' => 0,
    ];
    $response = $mj->ContactMetaData($params)->getResponse();
    if ($response && isset($response->Count) && $response->Count >= 0) {
      foreach ($response->Data as $property) {

        if ($property->Name == $property_name) {
          return $property->Datatype;
          break;
        }
      }
    }
  }

  // get names of contact properties
  public function mailjet_get_properties() {
    //get existing contact properties from MailJet
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

    return $properties;
  }

  /**
   * Set user tracking.
   */
  public function mailjet_admin_settings_tracking($form, &$form_state) {

    $settings = (array) unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['mailjet']);
    foreach ($settings as $key => $value) {
      if (property_exists(__CLASS__, $key)) {
        $this->$key = $value;
      }
    }

    $tracking = [
      "url" => $form_state['values']['tracking_url'],
      "open" => $form_state['values']['tracking_open'],
      "click" => $form_state['values']['tracking_click'],
      "bounce" => $form_state['values']['tracking_bounce'],
      "spam" => $form_state['values']['tracking_spam'],
      "blocked" => $form_state['values']['tracking_blocked'],
      "unsub" => $form_state['values']['tracking_unsub'],
    ];
    $current_events = unserialize($form_state['values']['current_events']);
    if (mailjet_user_trackingupdate($tracking, $current_events)) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }


  /**
   * Sync user's properties.
   */
  public function mailjet_properties_sync($new_field = NULL, $delete = FALSE) {

    $user_fields = [
      [
        "Name" => 'first_name',
        "Datatype" => 'str',
      ],
      [
        "Name" => 'last_name',
        "Datatype" => 'str',
      ],

    ];


    //get existing contact properties from MailJet
    $properties = [];
    $mj = $this->api_mailjet;
    $params = [
      'method' => 'GET',
      'limit' => 0,
    ];
    $response = $mj->ContactMetaData($params)->getResponse();
    if ($response && isset($response->Count) && $response->Count >= 0) {
      foreach ($response->Data as $property) {
        $properties[$property->Name] = (array) $property;
      }
    }
    else {
      return FALSE;
    }

    //sync Drupal fields to MJ properties
    foreach ($user_fields as $field) {
      if (array_key_exists($field['Name'], $properties)) {
        if ($field['Datatype'] == $properties[$field['Name']]['Datatype']) {
          //'Field '.$field['Name'].' is already in your MailJet account.'// no need of this message - too much spam :)
        }
        else {
          $update_params = [
            'method' => 'JSON',
            'ID' => $properties[$field['Name']]['ID'],
            'Name' => $field['Name'],
            'DataType' => $field['Datatype'],
          ];
          $update_response = $mj->ContactMetaData($update_params)
            ->getResponse();
          if ($update_response && $update_response->Count >= 0) {
            //success
          }
          else {
            // error
          }
        }
      }
      else {
        $insert_params = [
          'method' => 'JSON',
          'Name' => $field['Name'],
          'DataType' => $field['Datatype'],
          'NameSpace' => 'static',
        ];
        $insert_response = $mj->ContactMetaData($insert_params)->getResponse();
        if ($insert_response && $insert_response->Count >= 0) {
          // success
        }
        else {
          // error
        }
      }
    }
    return;
  }


}