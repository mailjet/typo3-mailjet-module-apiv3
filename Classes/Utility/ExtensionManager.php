<?php
/*
 * ExtensionManager class
 * THe class has a lot methods for custom type USER implementations for ext_conf_template.txt file.
 */

namespace Api\Mailjet\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use Fab\Vidi\Configuration\ConfigurationUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use DrewM\Mailjet\Mailjet;

class ExtensionManager {

  function statusSync() {
    require_once(ExtensionManagementUtility::extPath('mailjet', 'Resources/Private/Contrib/Mailjet/Mailjet.php'));
    $settings = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['mailjet']);

    $result_string = $this->getHtmlMessage('Empty API key and Secret key.', true, '', 22);
    $status_sender = '';
    $sending_allowed_status = '';
    $email_send = '';
    $apiKey = $settings['apiKeyMailjet'];
    $secretKey = $settings['secretKey'];
    if (!empty($apiKey) && !empty($secretKey)) {

      $loginOk = false;
      $result_string = $this->getHtmlMessage('Login Failed: Wrong user credentials! Check your API key and secret key!');

      $mailjetOptionsUpdater = GeneralUtility::makeInstance('Api\\Mailjet\\Domain\\Model\\Dto\\MailjetOptionsUpdater');
      $mailjetOptionsUpdater->saveConfiguration('sync_field', 'off');

      $mailjet = GeneralUtility::makeInstance('DrewM\\Mailjet\\Mailjet', $apiKey, $secretKey);
      $paramsProfile = [
          'method' => 'GET',
      ];

      $response = $mailjet->myprofile($paramsProfile)->getResponse();

      $isSenderAddressCorrect = false;
      $sendingAllowed = false;
      if (!empty($response)) {
        $loginOk = true;
        $result_string = $this->getHtmlMessage('Your credentials are correct!');

        if (!empty($settings['sender'])) {
          $params = [
              "method" => "LIST",
          ];
          $result = $mailjet->sender($params);
          $senders = $result->getResponse()->Data;
          $status_sender = $this->getHtmlMessage('<span style="color:red">Invalid email address!</span> Use a <a style="text-decoration: underline;" href="https://app.mailjet.com/account/sender" target="_blank">valid sender address</a> from your Mailjet account.');
          foreach ($senders as $sender) {
            if ($settings['sender'] == $sender->Email && $sender->status = 'Active') {
              $status_sender = $this->getHtmlMessage('Your sender email address is correct!');
              $isSenderAddressCorrect = true;
              $mailjetOptionsUpdater->saveConfiguration('sync_field', 'on');
            }
          }
        }

        if (!$isSenderAddressCorrect) {
          $status_sender = $this->getHtmlMessage('Please enter a <a style="text-decoration: underline;" href="https://app.mailjet.com/account/sender" target="_blank">valid sender address</a>.');
          if (!empty($settings['email_to'])) {
            $email_send = $this->getHtmlMessage('The test email was not sent! Please enter a <a style="text-decoration: underline;" href="https://app.mailjet.com/account/sender" target="_blank">valid sender address</a>.');
            $mailjetOptionsUpdater->saveConfiguration('email_to', '');
          }
        }
        else {
          $sendingAllowed = !empty($settings['Send']) && $settings['Send'] == 1;
          if ($sendingAllowed) {
            $sending_allowed_status = $this->getHtmlMessage('Sending emails through Mailjet is activated!');
          }
          else {
            $sending_allowed_status = $this->getHtmlMessage('Sending emails through Mailjet is not activated!');
          }
        }
      }

      if ($loginOk && $isSenderAddressCorrect && $sendingAllowed) {
        $result_string = $this->getHtmlMessage('OK', true, 'green') . $result_string;
      }
      else {
        $result_string = $this->getHtmlMessage('Error', true, 'red') . $result_string;
      }

      if (!empty($settings['email_to']) && $loginOk) {
        $errorMessage = 'The test email was not sent! ';
        if (filter_var($settings['email_to'], FILTER_VALIDATE_EMAIL)) {
          if ($isSenderAddressCorrect) {
            if ($sendingAllowed) {
              try {
                $host = $settings['smtp_host'];
                $smtpPort = $settings['smtp_port'];
                $smtpSecure = $settings['smtp_secure'];
                $mailerService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Api\\Mailjet\\Service\\MailjetMailerService', $apiKey, $secretKey, $host, $smtpSecure, $smtpPort);
                $emailSubject = 'Mailjet test email';
                $emailBody = 'Your configuration is OK!';
                $isSuccess = $mailerService->send($settings['sender'], $settings['email_to'], $emailSubject, $emailBody);
                $mailjetOptionsUpdater->saveConfiguration('email_to', '');
              } catch (\Exception $exception) {
                $isSuccess = false;
                $errorMessage .= $exception->getMessage();
              }
              if ($isSuccess) {
                $email_send = $this->getHtmlMessage('Test email sent successfully!');
              } else {
                $email_send = $this->getHtmlMessage($errorMessage);
              }
            }
            else {
              $email_send = $this->getHtmlMessage($errorMessage . 'Please activate email sending through Mailjet.');
            }
          }
          else {
            $email_send = $this->getHtmlMessage($errorMessage . 'Please enter a <a style="text-decoration: underline;" href="https://app.mailjet.com/account/sender" target="_blank">valid sender address</a>.');
            $mailjetOptionsUpdater->saveConfiguration('email_to', '');
          }
        }
        else {
          $email_send = $this->getHtmlMessage($errorMessage . 'Please enter a correct test email address.');
        }
      }
    }

    return $result_string . $status_sender . $sending_allowed_status . $email_send;
  }

  private function getHtmlMessage($message, $isBold = false, $color = '', $fontSize = 20): string {
    $fontSize = 'font-size:'.$fontSize.'px;';
    $boldStyle = $isBold ? 'font-weight:bold !important;' : '';
    $colorStyle = !empty($color) ? 'color:' . $color . ';' : '';
    return '<div style="'.$fontSize.$boldStyle.$colorStyle.'">' . $message .'</div>';
  }

  function apiDescription() {
    $result_string = "<div><strong>Welcome to the Mailjet Configuration page. If you are new to Mailjet, please <a style='text-decoration: underline;' href='https://app.mailjet.com/signup?p=typo3' target='_blank'>create an account</a>.</strong><br /> Should you already have a pre-existing Mailjet account, you can find your API Key and Secret Key <a style='text-decoration: underline;' href='https://app.mailjet.com/account/api_keys' target='_blank'>here</a>.</div>";

    return $result_string;
  }

  function accountInfo() {
    $settings = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['mailjet']);

    $result_string = $this->getHtmlMessage('Your account data is not updated! Please enter your credentials and try again!', true, '', 16);
    if (!empty($settings['apiKeyMailjet']) && !empty($settings['secretKey'])) {
      $result_string = $this->getHtmlMessage('No profile information is available at this time!');
      require_once(ExtensionManagementUtility::extPath('mailjet', 'Resources/Private/Contrib/Mailjet/Mailjet.php'));
      $mailjet = new Mailjet($settings['apiKeyMailjet'], $settings['secretKey']);

      $paramsProfile = [
        'method' => 'GET',
      ];
      $response = $mailjet->myprofile($paramsProfile)->getResponse();

      if (!empty($response) && $response->Count > 0) {
        $user_infos = (array) $response->Data[0];

        $displayInfo = [
          ['title' => 'First name', 'value' => $user_infos['Firstname']],
          ['title' => 'Last name', 'value' => $user_infos['Lastname']],
          ['title' => 'Company Name', 'value' => $user_infos['CompanyName']],
          ['title' => 'Address', 'value' => $user_infos['AddressStreet']],
          ['title' => 'City', 'value' => $user_infos['AddressCity']],
          ['title' => 'Postal Code / Zip Code', 'value' => $user_infos['AddressPostalCode']],
          ['title' => 'Country', 'value' => self::$country_array[$user_infos['AddressCountry']]],
        ];

        $result_string = '<div>';
        foreach ($displayInfo as $info) {
            $result_string .= "<div class='js-form-item form-item'>
                                    <label  style='width:160px;' >" . $info['title'] . "</label>
                                    <input disabled='disabled' type='text' style='margin-left:35px;'  value='" . $info['value'] . "' size='60' maxlength='128' class='form-text'>
                               </div>";
        }
        $result_string .= '</div>';
      }
    }
    return $result_string;
  }

    private static $country_array = array(
        "AF" => "Afghanistan",
        "AL" => "Albania",
        "DZ" => "Algeria",
        "AS" => "American Samoa",
        "AD" => "Andorra",
        "AO" => "Angola",
        "AI" => "Anguilla",
        "AQ" => "Antarctica",
        "AG" => "Antigua and Barbuda",
        "AR" => "Argentina",
        "AM" => "Armenia",
        "AW" => "Aruba",
        "AU" => "Australia",
        "AT" => "Austria",
        "AZ" => "Azerbaijan",
        "BS" => "Bahamas",
        "BH" => "Bahrain",
        "BD" => "Bangladesh",
        "BB" => "Barbados",
        "BY" => "Belarus",
        "BE" => "Belgium",
        "BZ" => "Belize",
        "BJ" => "Benin",
        "BM" => "Bermuda",
        "BT" => "Bhutan",
        "BO" => "Bolivia",
        "BA" => "Bosnia and Herzegovina",
        "BW" => "Botswana",
        "BV" => "Bouvet Island",
        "BR" => "Brazil",
        "BQ" => "British Antarctic Territory",
        "IO" => "British Indian Ocean Territory",
        "VG" => "British Virgin Islands",
        "BN" => "Brunei",
        "BG" => "Bulgaria",
        "BF" => "Burkina Faso",
        "BI" => "Burundi",
        "KH" => "Cambodia",
        "CM" => "Cameroon",
        "CA" => "Canada",
        "CT" => "Canton and Enderbury Islands",
        "CV" => "Cape Verde",
        "KY" => "Cayman Islands",
        "CF" => "Central African Republic",
        "TD" => "Chad",
        "CL" => "Chile",
        "CN" => "China",
        "CX" => "Christmas Island",
        "CC" => "Cocos [Keeling] Islands",
        "CO" => "Colombia",
        "KM" => "Comoros",
        "CG" => "Congo - Brazzaville",
        "CD" => "Congo - Kinshasa",
        "CK" => "Cook Islands",
        "CR" => "Costa Rica",
        "HR" => "Croatia",
        "CU" => "Cuba",
        "CY" => "Cyprus",
        "CZ" => "Czech Republic",
        "CI" => "Côte d’Ivoire",
        "DK" => "Denmark",
        "DJ" => "Djibouti",
        "DM" => "Dominica",
        "DO" => "Dominican Republic",
        "NQ" => "Dronning Maud Land",
        "DD" => "East Germany",
        "EC" => "Ecuador",
        "EG" => "Egypt",
        "SV" => "El Salvador",
        "GQ" => "Equatorial Guinea",
        "ER" => "Eritrea",
        "EE" => "Estonia",
        "ET" => "Ethiopia",
        "FK" => "Falkland Islands",
        "FO" => "Faroe Islands",
        "FJ" => "Fiji",
        "FI" => "Finland",
        "FR" => "France",
        "GF" => "French Guiana",
        "PF" => "French Polynesia",
        "TF" => "French Southern Territories",
        "FQ" => "French Southern and Antarctic Territories",
        "GA" => "Gabon",
        "GM" => "Gambia",
        "GE" => "Georgia",
        "DE" => "Germany",
        "GH" => "Ghana",
        "GI" => "Gibraltar",
        "GR" => "Greece",
        "GL" => "Greenland",
        "GD" => "Grenada",
        "GP" => "Guadeloupe",
        "GU" => "Guam",
        "GT" => "Guatemala",
        "GG" => "Guernsey",
        "GN" => "Guinea",
        "GW" => "Guinea-Bissau",
        "GY" => "Guyana",
        "HT" => "Haiti",
        "HM" => "Heard Island and McDonald Islands",
        "HN" => "Honduras",
        "HK" => "Hong Kong SAR China",
        "HU" => "Hungary",
        "IS" => "Iceland",
        "IN" => "India",
        "ID" => "Indonesia",
        "IR" => "Iran",
        "IQ" => "Iraq",
        "IE" => "Ireland",
        "IM" => "Isle of Man",
        "IL" => "Israel",
        "IT" => "Italy",
        "JM" => "Jamaica",
        "JP" => "Japan",
        "JE" => "Jersey",
        "JT" => "Johnston Island",
        "JO" => "Jordan",
        "KZ" => "Kazakhstan",
        "KE" => "Kenya",
        "KI" => "Kiribati",
        "KW" => "Kuwait",
        "KG" => "Kyrgyzstan",
        "LA" => "Laos",
        "LV" => "Latvia",
        "LB" => "Lebanon",
        "LS" => "Lesotho",
        "LR" => "Liberia",
        "LY" => "Libya",
        "LI" => "Liechtenstein",
        "LT" => "Lithuania",
        "LU" => "Luxembourg",
        "MO" => "Macau SAR China",
        "MK" => "Macedonia",
        "MG" => "Madagascar",
        "MW" => "Malawi",
        "MY" => "Malaysia",
        "MV" => "Maldives",
        "ML" => "Mali",
        "MT" => "Malta",
        "MH" => "Marshall Islands",
        "MQ" => "Martinique",
        "MR" => "Mauritania",
        "MU" => "Mauritius",
        "YT" => "Mayotte",
        "FX" => "Metropolitan France",
        "MX" => "Mexico",
        "FM" => "Micronesia",
        "MI" => "Midway Islands",
        "MD" => "Moldova",
        "MC" => "Monaco",
        "MN" => "Mongolia",
        "ME" => "Montenegro",
        "MS" => "Montserrat",
        "MA" => "Morocco",
        "MZ" => "Mozambique",
        "MM" => "Myanmar [Burma]",
        "NA" => "Namibia",
        "NR" => "Nauru",
        "NP" => "Nepal",
        "NL" => "Netherlands",
        "AN" => "Netherlands Antilles",
        "NT" => "Neutral Zone",
        "NC" => "New Caledonia",
        "NZ" => "New Zealand",
        "NI" => "Nicaragua",
        "NE" => "Niger",
        "NG" => "Nigeria",
        "NU" => "Niue",
        "NF" => "Norfolk Island",
        "KP" => "North Korea",
        "VD" => "North Vietnam",
        "MP" => "Northern Mariana Islands",
        "NO" => "Norway",
        "OM" => "Oman",
        "PC" => "Pacific Islands Trust Territory",
        "PK" => "Pakistan",
        "PW" => "Palau",
        "PS" => "Palestinian Territories",
        "PA" => "Panama",
        "PZ" => "Panama Canal Zone",
        "PG" => "Papua New Guinea",
        "PY" => "Paraguay",
        "YD" => "People's Democratic Republic of Yemen",
        "PE" => "Peru",
        "PH" => "Philippines",
        "PN" => "Pitcairn Islands",
        "PL" => "Poland",
        "PT" => "Portugal",
        "PR" => "Puerto Rico",
        "QA" => "Qatar",
        "RO" => "Romania",
        "RU" => "Russia",
        "RW" => "Rwanda",
        "RE" => "Réunion",
        "BL" => "Saint Barthélemy",
        "SH" => "Saint Helena",
        "KN" => "Saint Kitts and Nevis",
        "LC" => "Saint Lucia",
        "MF" => "Saint Martin",
        "PM" => "Saint Pierre and Miquelon",
        "VC" => "Saint Vincent and the Grenadines",
        "WS" => "Samoa",
        "SM" => "San Marino",
        "SA" => "Saudi Arabia",
        "SN" => "Senegal",
        "RS" => "Serbia",
        "CS" => "Serbia and Montenegro",
        "SC" => "Seychelles",
        "SL" => "Sierra Leone",
        "SG" => "Singapore",
        "SK" => "Slovakia",
        "SI" => "Slovenia",
        "SB" => "Solomon Islands",
        "SO" => "Somalia",
        "ZA" => "South Africa",
        "GS" => "South Georgia and the South Sandwich Islands",
        "KR" => "South Korea",
        "ES" => "Spain",
        "LK" => "Sri Lanka",
        "SD" => "Sudan",
        "SR" => "Suriname",
        "SJ" => "Svalbard and Jan Mayen",
        "SZ" => "Swaziland",
        "SE" => "Sweden",
        "CH" => "Switzerland",
        "SY" => "Syria",
        "ST" => "São Tomé and Príncipe",
        "TW" => "Taiwan",
        "TJ" => "Tajikistan",
        "TZ" => "Tanzania",
        "TH" => "Thailand",
        "TL" => "Timor-Leste",
        "TG" => "Togo",
        "TK" => "Tokelau",
        "TO" => "Tonga",
        "TT" => "Trinidad and Tobago",
        "TN" => "Tunisia",
        "TR" => "Turkey",
        "TM" => "Turkmenistan",
        "TC" => "Turks and Caicos Islands",
        "TV" => "Tuvalu",
        "UM" => "U.S. Minor Outlying Islands",
        "PU" => "U.S. Miscellaneous Pacific Islands",
        "VI" => "U.S. Virgin Islands",
        "UG" => "Uganda",
        "UA" => "Ukraine",
        "SU" => "Union of Soviet Socialist Republics",
        "AE" => "United Arab Emirates",
        "GB" => "United Kingdom",
        "US" => "United States",
        "ZZ" => "Unknown or Invalid Region",
        "UY" => "Uruguay",
        "UZ" => "Uzbekistan",
        "VU" => "Vanuatu",
        "VA" => "Vatican City",
        "VE" => "Venezuela",
        "VN" => "Vietnam",
        "WK" => "Wake Island",
        "WF" => "Wallis and Futuna",
        "EH" => "Western Sahara",
        "YE" => "Yemen",
        "ZM" => "Zambia",
        "ZW" => "Zimbabwe",
        "AX" => "Åland Islands",
    );
}
