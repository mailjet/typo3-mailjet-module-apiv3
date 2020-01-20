<?php

namespace Api\Mailjet\Controller;

use Api\Mailjet\Domain\Model\Dto\ExtensionConfiguration;
use Api\Mailjet\Domain\Model\Dto\FormDto;
use Api\Mailjet\ViewHelpers\TemplatesViewHelper;
use TYPO3\CMS\About\Domain\Model\Extension;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use Api\Mailjet\Service\DefaultMessagesService;
use TYPO3\CMS\Extbase\Annotation as Extbase;



class FormController extends ActionController {

    private $mailjet;

    private $settings_keys;

    public function initializeAction() {
        $this->settings_keys = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['mailjet']);
        require_once ExtensionManagementUtility::extPath('mailjet', 'Resources/Private/Contrib/Mailjet/Mailjet.php');
    }

    /**
     * @Extbase/IgnoreValidation("form")
     */
    public function indexAction(FormDto $form = NULL) {
        $message = null;
        if (!empty($_GET['list']) && !empty($_GET['mj'])) {
            $list_id = $_GET['list'];
            $contact_data = json_decode(base64_decode($_GET['mj']));
            if ($contact_data){
                $message = $this->confirmSubscription($list_id, $contact_data);
            }
        }
        $this->renderSubscriptionForm($form, $message);
    }

    /**
     * @param FormDto $form
     */
    public function responseAction(FormDto $form = NULL) {
        if (is_null($form)) {
            $this->redirect('index');
        }
        $validation = $this->validDataReg($form);

        if ($validation['has_error']){
            $this->view->assignMultiple($this->formatParamsArray($form, $this->settings, $validation['error_msg']));
        }else {
            $this->handleRegistration($form, $validation['contact_params']);
        }
    }

    /**
     * @param FormDto|null $form
     */
    private function validDataReg(FormDto $form = NULL) {
        $result = [
            'contact_params' => [],
            'has_error' => false,
            'error_msg' => []
        ];

        $prop_names = explode(',', $form->getProperties());
        $contact_properties_raw = [
            ['name' => $this->settings['prop1string'], 'value' => $form->getProp1()],
            ['name' => $this->settings['prop2string'], 'value' => $form->getProp2()],
            ['name' => $this->settings['prop3string'], 'value' => $form->getProp3()]
        ];

        $contact_properties = [];
        foreach ($prop_names as $prop_key => $prop){
            $contact_properties[$prop] = $contact_properties_raw[$prop_key];
        }

        $mailjet = $this->getMailjet();

        if ((empty($contact_properties))) {
            $result['has_error'] = true;
            $result['error_msg'][] = 'Your E-mail address is necessary for your subscription';
            return $result;
        }

        foreach ($contact_properties as $key => $field) {
            $error_input_data_types = DefaultMessagesService::getDataTypeMsg($form->getDataTypeMessage());
            $type = '';

            $error_type = str_replace("%id", $field['name'], $error_input_data_types);
            $params = ['method' => 'GET', 'limit' => 0, 'ID' => $key];
            $contactPropertyInfo = $mailjet->ContactMetaData($params)->getResponse()->Data;

            if (is_array($contactPropertyInfo) && count($contactPropertyInfo) > 0) {
                $type = $contactPropertyInfo[0]->Datatype;
                $propertyKey = $contactPropertyInfo[0]->Name;
            }
            if ($type === 'bool') {
                $result['contact_params'][$propertyKey] = (bool)$field['value'];
            }
            else if (!empty($field['value'])) {
                $error = false;
                switch ($type) {
                    case 'int':
                        if (!preg_match('/^[0-9]{1,45}$/', $field['value'])) {
                            $error = str_replace("%type", 'integer', $error_type). " Example (numbers): 1234";
                        } else {
                            $result['contact_params'][$propertyKey] = (int)$field['value'];
                        }
                        break;
                    case 'float':
                        if (!preg_match('/\-?\d+\.?\d*/', $field['value'])) {
                            $error = str_replace("%type", 'float', $error_type). " Example (float): 12.34";
                        } else {
                            $result['contact_params'][$propertyKey] = (int)$field['value'];
                        }
                        break;
                    case 'str':
                        if (!is_string($field['value'])) {
                            $error =  str_replace("%type", 'string', $error_type). " Example (text): First Name";
                        } else {
                            $result['contact_params'][$propertyKey] = (string)$field['value'];
                        }
                        break;
                    case 'datetime':
                        $date = \DateTime::createFromFormat('Y-m-d', $field['value'], new \DateTimeZone('UTC'));
                        if ($date !== false) {
                            $result['contact_params'][$propertyKey] = $date->format(\DateTime::RFC3339);
                        }
                        else {
                            $error =  str_replace("%type", 'datetime', $error_type). " Enter a valid date.";
                        }
                        break;
                }
                if ($error) {
                    $result['has_error'] = true;
                    $result['error_msg'][] = $error;
                }
            }
        }

        return $result;
    }

    /**
     * @param FormDto|null $form
     * @param array $validatedProperties
     */
    private function handleRegistration(FormDto $form = NULL, array $validatedProperties) {
        $isSend = false;
        $messageHelper = GeneralUtility::makeInstance('Api\\Mailjet\\Service\\DefaultMessagesService', $form);
        try {
            $mailjet = $this->getMailjet();

            $listId = $form->getListId();

            $emailParams['owner'] = $messageHelper->getOwner();
            $emailParams['email_heading_text'] = $messageHelper->getHeadingText();
            $emailParams['email_text_thank_you'] = $messageHelper->getThanksMessage();
            $emailParams['email_footer_text'] = $messageHelper->getEmailFooterMessage();
            $emailParams['email_text_button'] = $messageHelper->getConfButtonText();
            $emailParams['email_text_description'] = $messageHelper->getBodyMessage();

            $prefix = (isset($_SERVER['HTTPS']) ? "https" : "http");
            $link = "{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
            $emailParams['link'] = $prefix . "://" . substr($link, 0, strpos($link, "&"));
            $emailParams['link'] .= '&mj=' . base64_encode(json_encode(['Properties' => $validatedProperties, 'Email' => $form->getEmail()])) . '&list=' . $listId;
            $emailParams['url'] = $prefix . "://" . $_SERVER['HTTP_HOST'];

            $clientExists = true;
            $contactParams = [
                'method' => 'GET',
                'ContactEmail' => $form->getEmail(),
                'ContactsList' => $listId,
            ];
            $result = $mailjet->listrecipient($contactParams)->getResponse();
            // 1 - unsubscribed, !=1 - subscribed
            if ($result->Count < 1) {
                $clientExists = false;
            }
            if (!empty($result->Data) && $result->Data[0]->IsUnsubscribed == 1) {
                $clientExists = false;
            }
            if (!$clientExists) {
                if (!empty($this->settings_keys['Send']) && $this->settings_keys['Send'] == 1) {
                    $apiKey = $this->settings_keys['apiKeyMailjet'];
                    $secretKey = $this->settings_keys['secretKey'];
                    $host = $this->settings_keys['smtp_host'];
                    $smtpPort = $this->settings_keys['smtp_port'];
                    $smtpSecure = $this->settings_keys['smtp_secure'];
                    $mailerService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Api\\Mailjet\\Service\\MailjetMailerService', $apiKey, $secretKey, $host, $smtpSecure, $smtpPort);
                    $emailSubject = 'Please confirm your subscription';
                    $templateHelper = new TemplatesViewHelper();
                    $emailBody = $templateHelper->getSubscriptionEmailTemplate($emailParams);
                    $allowHtml = !empty($this->settings_keys['allowHtml']) && $this->settings_keys['allowHtml'] == 1;
                    $isSend = $mailerService->send($this->settings_keys['sender'], $form->getEmail(), $emailSubject, $emailBody, $allowHtml);
                }
            }else {
                $message = $messageHelper->getMemberExist();
            }
        } catch (\Exception $e) {
            $this->view->assign('error', 'general');
        }
        if (!isset($message)) {
            if ($isSend) {
                $message = $messageHelper->getConfirmMessage();
            } else {
                $message = $messageHelper->getSubscribeError();
            }
        }

        $this->view->assignMultiple(['message' => $message,]);
    }

    private function getMailjet()
    {
        if (!is_object($this->mailjet)){
            $this->mailjet = GeneralUtility::makeInstance('DrewM\\Mailjet\\Mailjet', $this->settings_keys['apiKeyMailjet'], $this->settings_keys['secretKey']);
        }else{
            $this->mailjet->resetRequest();
        }

        return $this->mailjet;
    }

    private function confirmSubscription($list_id, $customer_data)
    {
        $response_message = empty($this->settings['subscribeError']) ? 'Subscribe error. Please try again later!' : $this->settings['subscribeError'];
        $mailjet = $this->getMailjet();
        $contact_params = [
            'method' => 'GET',
            'ContactEmail' => $customer_data->Email,
            'ContactsList' => $list_id,
        ];
        $result = $mailjet->listrecipient($contact_params)->getResponse();
        if (!isset($result->Count)){
            return $response_message;
        }

        $response_message = DefaultMessagesService::getSubscribedMessage($customer_data->Email);

        if ( $result->Count === 0 || $result->Data[0]->IsUnsubscribed === true) {
            $add_params = [
                'Properties' => $customer_data->Properties,
                'Action' => 'addforce',
                'Email' => $customer_data->Email
            ];
            $mailjet->resetRequest();
            $response = $mailjet->manageContact($list_id, $add_params);
            if ($response && $response->Total > 0) {
                $response_message = DefaultMessagesService::getSuccessMessage($this->settings['finalMessage']);
            }
        }

        return $response_message;
    }

    private function renderSubscriptionForm($form, $message = null)
    {
        if (is_null($form)) {
            /** @var FormDto $form */
            $form = GeneralUtility::makeInstance('Api\\Mailjet\\Domain\\Model\\Dto\\FormDto');
            $prefill = GeneralUtility::_GP('email');
            if ($prefill) {
                $form->setEmail($prefill);
            }
        }
        $this->view->assignMultiple($this->formatParamsArray($form, $this->settings, $message));
    }

    private function formatParamsArray($form, $settings, $errors)
    {

        $arr_properties = [
            $form->getProp1(),
            $form->getProp2(),
            $form->getProp3()
        ];

        $properties = $settings['properties'] ? $settings['properties'] : '';
        if (is_string($properties)) {
            $arr_properties = explode(",", $properties);
        }
        $propertiesInfo = [];
        $mailjet = $this->getMailjet();
        foreach($arr_properties as $keyProp => $prop) {
            $id = (int)$keyProp + 1;
            if (!empty($prop)) {
                $params = ['method' => 'GET', 'limit' => 0, 'ID' => $prop];
                $data = $mailjet->ContactMetaData($params)->getResponse()->Data;
                if (is_array($data) && count($data) > 0) {
                    $type = $data[0]->Datatype;
                    $propertiesInfo['prop' . $id] = array(
                        'title' => $settings['prop' . $id . 'string'],
                        'description' => $settings['prop' . $id . 'descr'],
                        'input_property' => 'prop' . $id,
                        'contact_property' => $prop,
                        'type' => $type
                    );
                }
            }
        }
        $result = [
            'form' => $form,
            'propertiesInfo' => $propertiesInfo,
            'email' => $settings['email'],
            'description' => $settings['descpription'],
            'submitLabel' => $settings['submitLabel'],
            'headingText' => $settings['headingText'],
            'bodyText' => $settings['bodyText'],
            'confButton' => $settings['confButton'],
            'emailFooterMail' => $settings['emailFooterMail'],
            'thanks' => $settings['thanks'],
            'owner' => $settings['owner'],
            'confMessage' => $settings['confMessage'],
            'dataTypeMessage' => $settings['dataTypeMessage'],
            'subscribeError' => $settings['subscribeError'],
            'memberExist' => $settings['memberExist'],
            'finalMessage' => $settings['finalMessage'],
            'email_sender' => $settings['email_sender'],
            'listId' => $settings['listId'],
            'properties' => $settings['properties'],
            'emailSender' =>$settings['emailSender'],
            'generalMessage' => is_array( $errors) ?  $errors : null,
            'subscriptionMessage' => (!is_null( $errors) && !is_array( $errors)) ?  $errors : null];

        return $result;
    }
}