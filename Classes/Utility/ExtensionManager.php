<?php
/*
 * ExtensionManager class
 * THe class has a lot methods for custom type USER implementations for ext_conf_template.txt file.
 */

namespace Mailjet\Ext;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use Fab\Vidi\Configuration\ConfigurationUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use DrewM\Mailjet\MailJet;
use Api\Mailjet\Service\ApiService;
use \TYPO3\CMS\Core\Cache\CacheManager;

class ExtensionManager {

  function statusSync() {
    require_once(ExtensionManagementUtility::extPath('mailjet', 'Resources/Private/Contrib/Mailjet/Mailjet.php'));
    $settings = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['mailjet']);

    $result_sting = '<div style="font-weight:bold !important;font-size:22px;"> Empty API key and Secrey key. </div>';
    $status_sender = '';
    if ((!empty($settings['apiKeyMailjet']) && !empty($settings['secretKey'])) && ($settings['apiKeyMailjet'] != '' && $settings['secretKey'] != '')) {

      $result_string = '<div style="font-weight:bold !important;color:red;font-size:20px;"> Error </div> <div style="font-size:20px;"> Login Failed Wrong User Credentials! Enter api key and secret key again!</div>';

      $mailjetOptionsUpdater = GeneralUtility::makeInstance('Api\\Mailjet\\Domain\\Model\\Dto\\MailjetOptionsUpdater');
      $mailjetOptionsUpdater->saveConfiguration('sync_field', 'off');

      $mailjet = new Mailjet($settings['apiKeyMailjet'], $settings['secretKey']);
      $paramsProfile = [
        'method' => 'GET',
      ];

      $response = $mailjet->myprofile($paramsProfile)->getResponse();

      if (!empty($response)) {
        $result_string = '<div style="font-weight:bold !important;color:green;font-size:20px;">OK</div><div style="font-size:20px;">Your creadintials are correct!</div>';

        if (!empty($settings['sender'])) {
          $params = [
            "method" => "LIST",
          ];
          $result = $mailjet->sender($params);
          $senders = $result->getResponse()->Data;
          $status_sender = '<div style="font-size:20px;"><span style="color:red">Invalid email address!</span> Use a <a style="text-decoration: underline;" href="https://app.mailjet.com/account/sender" target="_blank">valid sender address</a> from your Mailjet account.</div>';
          foreach ($senders as $sender) {
            if ($settings['sender'] == $sender->Email && $sender->status = 'Active') {
              $status_sender = '<div style="font-size:20px;">Your sender email is correct!</div>';
              $mailjetOptionsUpdater->saveConfiguration('sync_field', 'on');
            }
          }

          if (!empty($settings['email_to']) && $settings['sync_field'] != 'on') {
            $status_sender .= '<div style="font-size:20px;">The test email was not sent! Please enter a <a style="text-decoration: underline;" href="https://app.mailjet.com/account/sender" target="_blank">valid sender address</a>.</div>';
            $mailjetOptionsUpdater->saveConfiguration('email_to', '');
          }
        }
        else {
          if (!empty($settings['email_to'])) {
            $status_sender = '<div style="font-size:20px;">The test email was not sent! Please enter a <a style="text-decoration: underline;" href="https://app.mailjet.com/account/sender" target="_blank">valid sender address</a>.</div>';
            $mailjetOptionsUpdater->saveConfiguration('email_to', '');
          }
        }
      }

    }


    $extensionConfigurationMailjet = GeneralUtility::makeInstance('Api\\Mailjet\\Domain\\Model\\Dto\\ExtensionConfigurationMailjet');
    $email_send = '';

    if (!empty($settings['email_to'])) {

      if ($settings['sync_field'] == 'on') {

        // Create the message
        $mail = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Mail\\MailMessage');

        // Prepare and send the message
        $mail->setSubject('Mailjet test email')
          ->setFrom([$settings['sender']])
          ->setTo([$settings['email_to']])
          ->setBody('Your configuration is OK!')
          ->send();
        $mailjetOptionsUpdater->saveConfiguration('email_to', '');
        $email_send = '<div style="font-size:20px;">Test email sent successfully!</div>';
      }
    }

    return $result_string . $status_sender . $email_send;
  }

  function apiDescription() {
    $result_string = "<div><strong>Welcome to the Mailjet Configuration page. If you are new to Mailjet, please <a style='text-decoration: underline;' href='https://app.mailjet.com/signup?p=typo3' target='_blank'>create an account</a>.</strong><br /> Should you already have a pre-existing Mailjet account, you can find your API Key and Secret Key <a style='text-decoration: underline;' href='https://app.mailjet.com/account/api_keys' target='_blank'>here</a>.</div>";

    return $result_string;
  }

  function accountInfo() {
    $settings = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['mailjet']);
    $result_string = '<div style="font-size:20px;">No profile information is available at this time!</div>';
    if (!empty($settings['apiKeyMailjet']) && !empty($settings['secretKey'])) {
      $result_string = "<div style='font-weight:bold !important;font-size:16px !important;'>Your account data is not updated! Please enter your credentials and try again!</div>";
      require_once(ExtensionManagementUtility::extPath('mailjet', 'Resources/Private/Contrib/Mailjet/Mailjet.php'));
      $mailjet = new Mailjet($settings['apiKeyMailjet'], $settings['secretKey']);

      $paramsProfile = [
        'method' => 'GET',
      ];
      $response = $mailjet->myprofile($paramsProfile)->getResponse();

      if (!empty($response)) {
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

        $country_array = [
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
        ];

        $result_string = "<div>
             <div class='js-form-item form-item'>
      <label style='width:160px;' >First name</label>
        <input disabled='disabled' type='text' style='margin-left:35px;' value='" . $user_infos['Firstname'] . "' size='60' maxlength='128' class='form-text'></div>
        
         <div class='js-form-item form-item'>
      <label  style='width:160px;' >Last name</label>
        <input disabled='disabled' type='text' style='margin-left:35px;' value='" . $user_infos['Lastname'] . "' size='60' maxlength='128' class='form-text'></div>
        
         <div class='js-form-item form-item'>
      <label  style='width:160px;' >Company Name</label>
        <input disabled='disabled' type='text' style='margin-left:35px;'  value='" . $user_infos['CompanyName'] . "' size='60' maxlength='128' class='form-text'></div>
        
         <div class='js-form-item form-item'>
      <label  style='width:160px;' >Address</label>
        <input disabled='disabled' type='text' style='margin-left:35px;'  value='" . $user_infos['AddressStreet'] . "' size='60' maxlength='128' class='form-text'></div>
        
                 <div class='js-form-item form-item'>
      <label  style='width:160px;' >City</label>
        <input disabled='disabled' type='text' style='margin-left:35px;'  value='" . $user_infos['AddressCity'] . "' size='60' maxlength='128' class='form-text'></div>
        
           <div class='js-form-item form-item'>
      <label  style='width:160px;' >Postal Code / Zip Code </label>
        <input disabled='disabled' type='text' style='margin-left:35px;'  value='" . $user_infos['AddressPostalCode'] . "' size='60' maxlength='128' class='form-text'></div>
        
                   <div class='js-form-item form-item'>
      <label  style='width:160px;' >Country</label>
        <input disabled='disabled' type='text' style='margin-left:35px;'  value='" . $country_array[$user_infos['AddressCountry']] . "' size='60' maxlength='128' class='form-text'></div>
        
        </div>";
      }
    }
    return $result_string;
  }

}

?>
