<?php

namespace Api\Mailjet\Hooks\Backend;

use Api\Mailjet\Service\ApiService;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use DrewM\Mailjet\MailJet;

class PageLayoutViewHook {

  /**
   * Extension key
   *
   * @var string
   */
  const KEY = 'mailjet';

  /**
   * Path to the locallang file
   *
   * @var string
   */
  const LLPATH = 'LLL:EXT:mailjet/Resources/Private/Language/locallang.xml:';

  /**
   * Table information
   *
   * @var array
   */
  protected $tableData = [];

  /**
   * @var array
   */
  protected $flexformData = [];

  /** @var  DatabaseConnection */
  protected $databaseConnection;

  /** @var ApiService */
  protected $api;

  public function __construct() {
    /** @var DatabaseConnection databaseConnection */
    $this->databaseConnection = $GLOBALS['TYPO3_DB'];
    $this->api = GeneralUtility::makeInstance('Api\\Mailjet\\Service\\ApiService');
  }


  public function getExtensionSummary(array $params = []) {


    $this->flexformData = GeneralUtility::xml2array($params['row']['pi_flexform']);

    $result = '<strong>' . htmlspecialchars($this->getLanguageService()
        ->sL(self::LLPATH . 'plugin.title')) . '</strong><br>';


    $result .= $this->renderSettingsAsTable();

    $settings = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['mailjet']);
    if (is_array($settings)) {

      if (!empty($settings['apiKeyMailjet']) && !empty($settings['secretKey'])) {

        $mailjetOptionsUpdater = GeneralUtility::makeInstance('Api\\Mailjet\\Domain\\Model\\Dto\\MailjetOptionsUpdater');
        $extensionConfigurationMailjet = GeneralUtility::makeInstance('Api\\Mailjet\\Domain\\Model\\Dto\\ExtensionConfigurationMailjet');

        if (!empty($settings['email_to']) && !empty($settings['email_from'])) {
          // Create the message
          $mail = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Mail\\MailMessage');

          // Prepare and send the message
          $mail->setSubject('Mailjet test email')
            ->setFrom([$settings['email_from']])
            ->setTo([$settings['email_to']])
            ->setBody('Your configuration is OK!')
            ->send();
          $mailjetOptionsUpdater->saveConfiguration('email_to', '');
          $mailjetOptionsUpdater->saveConfiguration('email_from', '');
        }

        if (isset($settings['sync_field']) && $settings['sync_field'] != 'ready1') {
          require_once(ExtensionManagementUtility::extPath('mailjet', 'Resources/Private/Contrib/Mailjet/Mailjet.php'));
          $mailjet = new Mailjet($settings['apiKeyMailjet'], $settings['secretKey']);

          if ($mailjet) {

            $params = [
              'APIKey' => $mailjet->getAPIKey(),
            ];

            $response = $mailjet->eventcallbackurl($params)->getResponse();

            if ($response && isset($response->Count) && $response->Count > 0) {
              $tracking_check = $response;
            }
            elseif ($response && isset($response->Count) && $response->Count == 0) {
              $tracking_check = $response;
            }

            $check = [
              "open" => 0,
              "click" => 0,
              "bounce" => 0,
              "spam" => 0,
              "blocked" => 0,
              "unsub" => 0,
            ];

            $tracking_url = $_SERVER['REQUEST_URI'];
            $current_events = [];
            foreach ($tracking_check->Data as $event) {
              if (array_key_exists($event->EventType, $check)) {
                $check[$event->EventType] = 1;
                $tracking_url = $event->Url;
                $current_events[$event->EventType] = $event->ID;
              }
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
              $user_infos = array_merge((array) $response->Data[0], (array) $responseUser->Data[0]);
            }
            else {
              $user_infos = [];
            }
          }

          // events options
          $mailjetOptionsUpdater->saveConfiguration('openEventss', !empty($check['open']) ? $check['open'] : 0);
          $mailjetOptionsUpdater->saveConfiguration('clickEvents', !empty($check['click']) ? $check['click'] : 0);
          $mailjetOptionsUpdater->saveConfiguration('bounceEvents', !empty($check['bounce']) ? $check['bounce'] : 0);
          $mailjetOptionsUpdater->saveConfiguration('unsubEvents', !empty($check['unsub']) ? $check['unsub'] : 0);
          $mailjetOptionsUpdater->saveConfiguration('spamEvents', !empty($check['spam']) ? $check['spam'] : 0);
          $mailjetOptionsUpdater->saveConfiguration('blockedEvents', !empty($check['blocked']) ? $check['blocked'] : 0);

          // account options
          $mailjetOptionsUpdater->saveConfiguration('first_name', !empty($user_infos) ? $user_infos['Firstname'] : '');
          $mailjetOptionsUpdater->saveConfiguration('last_name', !empty($user_infos) ? $user_infos['Lastname'] : '');
          $mailjetOptionsUpdater->saveConfiguration('company_name', !empty($user_infos) ? $user_infos['CompanyName'] : '');
          $mailjetOptionsUpdater->saveConfiguration('address', !empty($user_infos) ? $user_infos['AddressStreet'] : '');
          $mailjetOptionsUpdater->saveConfiguration('city', !empty($user_infos) ? $user_infos['AddressCity'] : '');
          $mailjetOptionsUpdater->saveConfiguration('zip_code', !empty($user_infos) ? $user_infos['AddressPostalCode'] : '');
          $mailjetOptionsUpdater->saveConfiguration('country', !empty($user_infos) ? $user_infos['AddressCountry'] : '');

          $mailjetOptionsUpdater->saveConfiguration('sync_field', 'ready1');
        }
        else {
          $extensionConfigurationMailjet->syncConfigOptions();
        }


      }
    }

    return $result;
  }


  /**
   * @param string $string
   *
   * @return string
   */
  protected function getLabel($string, $hsc = TRUE) {
    $label = $this->getLanguageService()->sL(self::LLPATH . $string);
    if ($hsc) {
      $label = htmlspecialchars($label);
    }
    return $label;
  }

  /**
   * Return language service instance
   *
   * @return \TYPO3\CMS\Lang\LanguageService
   */
  public function getLanguageService() {
    return $GLOBALS['LANG'];
  }

  /**
   * Render the settings as table for Web>Page module
   * System settings are displayed in mono font
   *
   * @return string
   */
  protected function renderSettingsAsTable() {
    if (count($this->tableData) == 0) {
      return '';
    }

    $content = '';
    foreach ($this->tableData as $line) {
      $content .= ($line[0] ? ('<strong>' . $line[0] . '</strong>' . ' ') : '') . $line[1] . '<br />';
    }

    return '<pre style="white-space:normal">' . $content . '</pre>';
  }

  /**
   * Get field value from flexform configuration,
   * including checks if flexform configuration is available
   *
   * @param string $key name of the key
   * @param string $sheet name of the sheet
   *
   * @return string|NULL if nothing found, value if found
   */
  protected function getFieldFromFlexform($key, $sheet = 'sDEF') {
    $flexform = $this->flexformData;
    if (isset($flexform['data'])) {
      $flexform = $flexform['data'];
      if (is_array($flexform) && is_array($flexform[$sheet]) && is_array($flexform[$sheet]['lDEF'])
        && is_array($flexform[$sheet]['lDEF'][$key]) && isset($flexform[$sheet]['lDEF'][$key]['vDEF'])
      ) {
        return $flexform[$sheet]['lDEF'][$key]['vDEF'];
      }
    }

    return NULL;
  }

}
