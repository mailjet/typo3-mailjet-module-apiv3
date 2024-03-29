<?php

namespace Api\Mailjet\Hooks\Backend;

use Api\Mailjet\Service\ApiService;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use DrewM\Mailjet\MailJet;

class PageLayoutViewHook
{

    /**
     * Extension key
     * @var string
     */
    const KEY = 'mailjet';

    /**
     * Path to the locallang file
     * @var string
     */
    const LLPATH = 'LLL:EXT:mailjet/Resources/Private/Language/locallang.xml:';

    /**
     * Table information
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

    public function __construct()
    {
        $this->databaseConnection = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default'];
        $this->api = GeneralUtility::makeInstance('Api\\Mailjet\\Service\\ApiService');
    }


    public function getExtensionSummary(array $params = [])
    {

        $this->flexformData = GeneralUtility::xml2array($params['row']['pi_flexform']);

        $result = '<strong>' . htmlspecialchars($this->getLanguageService()
                ->sL(self::LLPATH . 'plugin.title')) . '</strong><br>';

        $result .= $this->renderSettingsAsTable();

        return $result;
    }


    /**
     * @param string $string
     * @return string
     */
    protected function getLabel($string, $hsc = TRUE)
    {
        $label = $this->getLanguageService()->sL(self::LLPATH . $string);
        if ($hsc) {
            $label = htmlspecialchars($label);
        }
        return $label;
    }

    /**
     * Return language service instance
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    public function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Render the settings as table for Web>Page module
     * System settings are displayed in mono font
     * @return string
     */
    protected function renderSettingsAsTable()
    {
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
     * @param string $key name of the key
     * @param string $sheet name of the sheet
     * @return string|NULL if nothing found, value if found
     */
    protected function getFieldFromFlexform($key, $sheet = 'sDEF')
    {
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
