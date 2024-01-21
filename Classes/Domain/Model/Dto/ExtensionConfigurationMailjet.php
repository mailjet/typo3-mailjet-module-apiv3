<?php

namespace Api\Mailjet\Domain\Model\Dto;

use Api\Mailjet\Exception\ApiKeyMissingException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Api\Mailjet\Service\ApiService;
use DrewM\Mailjet\MailJet;
use Api\Mailjet\Exception\GeneralException;
use Api\Mailjet\Exception\InvalidInputException;

class ExtensionConfigurationMailjet
{

    /** @var string */
    protected $apiKeyMailjet;

    /** @var string */
    protected $secretKey;

    public function __construct()
    {
        $settings = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
        )->get('mailjet');
        //$settings = (array)unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['mailjet']);
        foreach ($settings as $key => $value) {
            if (property_exists(__CLASS__, $key)) {
                $this->$key = $value;
            }
        }

    }

    /**
     * @throws  Exception
     */
    public function getApiData()
    {
        if ((empty($this->apiKeyMailjet) || empty($this->secretKey)) || (empty($this->apiKeyMailjet) && empty($this->secretKey))) {
            throw new ApiKeyMissingException('API key or Secret key is missing!');
        }

        return ['apiKey' => $this->apiKeyMailjet, 'secretKey' => $this->secretKey];
    }

    /**
     * @return string
     * @throws ApiKeyMissingException
     */
    public function syncConfigOptions()
    {

        $tracking_check = NULL;
        $mailjetService = GeneralUtility::makeInstance('Api\\Mailjet\\Service\\ApiService');
        $country_key = '';
        $settings = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
        )->get('mailjet');
        $mailjet = new Mailjet($this->apiKeyMailjet, $this->secretKey);

        if ($mailjet) {


            $params = [
                'APIKey' => $mailjet->getAPIKey(),
            ];
            $response = $mailjet->eventcallbackurl($params)->getResponse();

            if ($response && isset($response->Count) && $response->Count > 0) {
                $tracking_check = $response;
            } elseif ($response && isset($response->Count) && $response->Count == 0) {
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

            $tracking_url = 'http://typo3-8v.hstaged.com/';
            $current_events = [];
            foreach ($tracking_check->Data as $event) {
                if (array_key_exists($event->EventType, $check)) {
                    $check[$event->EventType] = 1;
                    $tracking_url = $event->Url;
                    $current_events[$event->EventType] = $event->ID;
                }
            }

            $tracking = [
                "url" => $tracking_url,
                "open" => $settings['openEventss'],
                "click" => $settings['clickEvents'],
                "bounce" => $settings['bounceEvents'],
                "spam" => $settings['spamEvents'],
                "blocked" => $settings['blockedEvents'],
                "unsub" => $settings['unsubEvents'],
            ];


            $check = ["open", "click", "bounce", "spam", "blocked", "unsub"];
            foreach ($tracking as $key => $value) {
                if (in_array($key, $check)) {
                    if ($value == 1 && !array_key_exists($key, $current_events)) {
                        $params = [
                            'method' => 'JSON',
                            'APIKeyALT' => $mailjet->getAPIKey(),
                            'EventType' => $key,
                            'Url' => $tracking['url'],
                        ];
                        $new_response = $mailjet->eventcallbackurl($params)->getResponse();
                        unset($params);
                        unset($new_response);
                    }

                    if ($value == 0 && array_key_exists($key, $current_events)) {
                        $del_params = [
                            'method' => 'DELETE',
                            'ID' => $current_events[$key],
                        ];

                        $del_response = $mailjet->eventcallbackurl($del_params);
                        unset($del_params);
                        unset($del_response);
                    }
                }
            }


            // $mailjetService->mailjet_user_trackingupdate($tracking, $current_events);

            $countries = [
                'AF' => 'Afghanistan',
                'AX' => 'Aland Islands',
                'AL' => 'Albania',
                'DZ' => 'Algeria',
                'AS' => 'American Samoa',
                'AD' => 'Andorra',
                'AO' => 'Angola',
                'AI' => 'Anguilla',
                'AQ' => 'Antarctica',
                'AG' => 'Antigua And Barbuda',
                'AR' => 'Argentina',
                'AM' => 'Armenia',
                'AW' => 'Aruba',
                'AU' => 'Australia',
                'AT' => 'Austria',
                'AZ' => 'Azerbaijan',
                'BS' => 'Bahamas',
                'BH' => 'Bahrain',
                'BD' => 'Bangladesh',
                'BB' => 'Barbados',
                'BY' => 'Belarus',
                'BE' => 'Belgium',
                'BZ' => 'Belize',
                'BJ' => 'Benin',
                'BM' => 'Bermuda',
                'BT' => 'Bhutan',
                'BO' => 'Bolivia',
                'BA' => 'Bosnia And Herzegovina',
                'BW' => 'Botswana',
                'BV' => 'Bouvet Island',
                'BR' => 'Brazil',
                'IO' => 'British Indian Ocean Territory',
                'BN' => 'Brunei Darussalam',
                'BG' => 'Bulgaria',
                'BF' => 'Burkina Faso',
                'BI' => 'Burundi',
                'KH' => 'Cambodia',
                'CM' => 'Cameroon',
                'CA' => 'Canada',
                'CV' => 'Cape Verde',
                'KY' => 'Cayman Islands',
                'CF' => 'Central African Republic',
                'TD' => 'Chad',
                'CL' => 'Chile',
                'CN' => 'China',
                'CX' => 'Christmas Island',
                'CC' => 'Cocos (Keeling) Islands',
                'CO' => 'Colombia',
                'KM' => 'Comoros',
                'CG' => 'Congo',
                'CD' => 'Congo, Democratic Republic',
                'CK' => 'Cook Islands',
                'CR' => 'Costa Rica',
                'CI' => 'Cote D\'Ivoire',
                'HR' => 'Croatia',
                'CU' => 'Cuba',
                'CY' => 'Cyprus',
                'CZ' => 'Czech Republic',
                'DK' => 'Denmark',
                'DJ' => 'Djibouti',
                'DM' => 'Dominica',
                'DO' => 'Dominican Republic',
                'EC' => 'Ecuador',
                'EG' => 'Egypt',
                'SV' => 'El Salvador',
                'GQ' => 'Equatorial Guinea',
                'ER' => 'Eritrea',
                'EE' => 'Estonia',
                'ET' => 'Ethiopia',
                'FK' => 'Falkland Islands (Malvinas)',
                'FO' => 'Faroe Islands',
                'FJ' => 'Fiji',
                'FI' => 'Finland',
                'FR' => 'France',
                'GF' => 'French Guiana',
                'PF' => 'French Polynesia',
                'TF' => 'French Southern Territories',
                'GA' => 'Gabon',
                'GM' => 'Gambia',
                'GE' => 'Georgia',
                'DE' => 'Germany',
                'GH' => 'Ghana',
                'GI' => 'Gibraltar',
                'GR' => 'Greece',
                'GL' => 'Greenland',
                'GD' => 'Grenada',
                'GP' => 'Guadeloupe',
                'GU' => 'Guam',
                'GT' => 'Guatemala',
                'GG' => 'Guernsey',
                'GN' => 'Guinea',
                'GW' => 'Guinea-Bissau',
                'GY' => 'Guyana',
                'HT' => 'Haiti',
                'HM' => 'Heard Island & Mcdonald Islands',
                'VA' => 'Holy See (Vatican City State)',
                'HN' => 'Honduras',
                'HK' => 'Hong Kong',
                'HU' => 'Hungary',
                'IS' => 'Iceland',
                'IN' => 'India',
                'ID' => 'Indonesia',
                'IR' => 'Iran, Islamic Republic Of',
                'IQ' => 'Iraq',
                'IE' => 'Ireland',
                'IM' => 'Isle Of Man',
                'IL' => 'Israel',
                'IT' => 'Italy',
                'JM' => 'Jamaica',
                'JP' => 'Japan',
                'JE' => 'Jersey',
                'JO' => 'Jordan',
                'KZ' => 'Kazakhstan',
                'KE' => 'Kenya',
                'KI' => 'Kiribati',
                'KR' => 'Korea',
                'KW' => 'Kuwait',
                'KG' => 'Kyrgyzstan',
                'LA' => 'Lao People\'s Democratic Republic',
                'LV' => 'Latvia',
                'LB' => 'Lebanon',
                'LS' => 'Lesotho',
                'LR' => 'Liberia',
                'LY' => 'Libyan Arab Jamahiriya',
                'LI' => 'Liechtenstein',
                'LT' => 'Lithuania',
                'LU' => 'Luxembourg',
                'MO' => 'Macao',
                'MK' => 'Macedonia',
                'MG' => 'Madagascar',
                'MW' => 'Malawi',
                'MY' => 'Malaysia',
                'MV' => 'Maldives',
                'ML' => 'Mali',
                'MT' => 'Malta',
                'MH' => 'Marshall Islands',
                'MQ' => 'Martinique',
                'MR' => 'Mauritania',
                'MU' => 'Mauritius',
                'YT' => 'Mayotte',
                'MX' => 'Mexico',
                'FM' => 'Micronesia, Federated States Of',
                'MD' => 'Moldova',
                'MC' => 'Monaco',
                'MN' => 'Mongolia',
                'ME' => 'Montenegro',
                'MS' => 'Montserrat',
                'MA' => 'Morocco',
                'MZ' => 'Mozambique',
                'MM' => 'Myanmar',
                'NA' => 'Namibia',
                'NR' => 'Nauru',
                'NP' => 'Nepal',
                'NL' => 'Netherlands',
                'AN' => 'Netherlands Antilles',
                'NC' => 'New Caledonia',
                'NZ' => 'New Zealand',
                'NI' => 'Nicaragua',
                'NE' => 'Niger',
                'NG' => 'Nigeria',
                'NU' => 'Niue',
                'NF' => 'Norfolk Island',
                'MP' => 'Northern Mariana Islands',
                'NO' => 'Norway',
                'OM' => 'Oman',
                'PK' => 'Pakistan',
                'PW' => 'Palau',
                'PS' => 'Palestinian Territory, Occupied',
                'PA' => 'Panama',
                'PG' => 'Papua New Guinea',
                'PY' => 'Paraguay',
                'PE' => 'Peru',
                'PH' => 'Philippines',
                'PN' => 'Pitcairn',
                'PL' => 'Poland',
                'PT' => 'Portugal',
                'PR' => 'Puerto Rico',
                'QA' => 'Qatar',
                'RE' => 'Reunion',
                'RO' => 'Romania',
                'RU' => 'Russian Federation',
                'RW' => 'Rwanda',
                'BL' => 'Saint Barthelemy',
                'SH' => 'Saint Helena',
                'KN' => 'Saint Kitts And Nevis',
                'LC' => 'Saint Lucia',
                'MF' => 'Saint Martin',
                'PM' => 'Saint Pierre And Miquelon',
                'VC' => 'Saint Vincent And Grenadines',
                'WS' => 'Samoa',
                'SM' => 'San Marino',
                'ST' => 'Sao Tome And Principe',
                'SA' => 'Saudi Arabia',
                'SN' => 'Senegal',
                'RS' => 'Serbia',
                'SC' => 'Seychelles',
                'SL' => 'Sierra Leone',
                'SG' => 'Singapore',
                'SK' => 'Slovakia',
                'SI' => 'Slovenia',
                'SB' => 'Solomon Islands',
                'SO' => 'Somalia',
                'ZA' => 'South Africa',
                'GS' => 'South Georgia And Sandwich Isl.',
                'ES' => 'Spain',
                'LK' => 'Sri Lanka',
                'SD' => 'Sudan',
                'SR' => 'Suriname',
                'SJ' => 'Svalbard And Jan Mayen',
                'SZ' => 'Swaziland',
                'SE' => 'Sweden',
                'CH' => 'Switzerland',
                'SY' => 'Syrian Arab Republic',
                'TW' => 'Taiwan',
                'TJ' => 'Tajikistan',
                'TZ' => 'Tanzania',
                'TH' => 'Thailand',
                'TL' => 'Timor-Leste',
                'TG' => 'Togo',
                'TK' => 'Tokelau',
                'TO' => 'Tonga',
                'TT' => 'Trinidad And Tobago',
                'TN' => 'Tunisia',
                'TR' => 'Turkey',
                'TM' => 'Turkmenistan',
                'TC' => 'Turks And Caicos Islands',
                'TV' => 'Tuvalu',
                'UG' => 'Uganda',
                'UA' => 'Ukraine',
                'AE' => 'United Arab Emirates',
                'GB' => 'United Kingdom',
                'US' => 'United States',
                'UM' => 'United States Outlying Islands',
                'UY' => 'Uruguay',
                'UZ' => 'Uzbekistan',
                'VU' => 'Vanuatu',
                'VE' => 'Venezuela',
                'VN' => 'Viet Nam',
                'VG' => 'Virgin Islands, British',
                'VI' => 'Virgin Islands, U.S.',
                'WF' => 'Wallis And Futuna',
                'EH' => 'Western Sahara',
                'YE' => 'Yemen',
                'ZM' => 'Zambia',
                'ZW' => 'Zimbabwe',
            ];

            $search_country = trim($settings['country']);
            $country_key = array_search($search_country, $countries);

            $infos = [
                'Firstname' => $settings['first_name'],
                'Lastname' => $settings['last_name'],
                'CompanyName' => $settings['company_name'],
                'AddressStreet' => $settings['address'],
                'AddressCity' => $settings['city'],
                'AddressPostalCode' => $settings['zip_code'],
                'AddressCountry' => $country_key,
            ];

            $infos['method'] = 'PUT';
            $response = $mailjet->myprofile($infos)->getResponse();

            if (isset($response->ErrorInfo) && !empty($response->ErrorInfo)) {
                // error...
            }
        }
    }

}
