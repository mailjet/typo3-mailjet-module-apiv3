<?php

namespace Api\Mailjet\Controller;

use Api\Mailjet\Domain\Model\Dto\ExtensionConfiguration;
use Api\Mailjet\Domain\Model\Dto\FormDto;
use Api\Mailjet\Exception\GeneralException;
use Api\Mailjet\Exception\MemberExistsException;
use Api\Mailjet\Service\ApiService;
use Api\Mailjet\ViewHelpers\TemplatesViewHelper;
use TYPO3\CMS\About\Domain\Model\Extension;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use DrewM\Mailjet\MailJet;
use TYPO3Fluid\Fluid\View\TemplateView;
use Api\Mailjet\Service\DefaultMessagesService;




class FormController extends ActionController {

    /** @var ApiService $service */
    protected $registrationService;

    private $mailjet;

    private $settings_keys;

    public function initializeAction() {
        $this->settings_keys = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['mailjet']);
        $this->registrationService = GeneralUtility::makeInstance('Api\\Mailjet\\Service\\ApiService');
    }

    /**
     * @dontvalidate $form
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

        if (!(empty($contact_properties))) {
            foreach ($contact_properties as $key => $field) {
                $error_input_data_types = DefaultMessagesService::getDataTypeMsg($form->getDataTypeMessage());
                $type = '';

                if (!empty($field['value'])) {
                    $error_type = str_replace("%id", $field['name'], $error_input_data_types);
                    $params = ['method' => 'GET', 'limit' => 0,];
                    $dataTypes = $mailjet->ContactMetaData($params)->getResponse();

                    if ($dataTypes && isset($dataTypes->Count) && $dataTypes->Count >= 0) {
                        foreach ($dataTypes->Data as $property) {
                            if ($property->Name == $key) {
                                $type = $property->Datatype;
                                break;
                            }
                        }
                    }
                    $error = false;
                    switch ($type) {
                        case 'int':
                            if (!preg_match('/^[0-9]{1,45}$/', $field['value']) && !empty($field['value'])) {
                                $error = str_replace("%type", 'number', $error_type). " Example (numbers): 1234";

                            } else {
                                $result['contact_params'][$key] = (int)$field['value'];
                            }
                            break;
                        case 'str':
                            if (!is_string($field['value']) && !empty($field['value'])) {
                                $error =  str_replace("%type", 'string', $error_type). " Example (text): First Name";

                            } else {
                                $result['contact_params'][$key] = (string)$field['value'];
                            }
                            break;
                        case 'datetime':
                            if (!preg_match("/^\s*(3[01]|[12][0-9]|0?[1-9])\-(1[012]|0?[1-9])\-((?:19|20)\d{2})\s*$/", $field['value']) && !empty($field['value'])) {
                                $error =  str_replace("%type", 'datetime', $error_type). " Example (DATE): 26-02-2017";
                            } else {
                                if (!empty($field['value'])) {
                                    $date = $field['value'];
                                    $date_array = explode("-", $date);
                                    if (checkdate($date_array[1], $date_array[0], $date_array[2]) == FALSE) {
                                        $error =  str_replace("%type", 'datetime', $error_type). " Example (DATE): 26-02-2017";
                                    }
                                }
                            }
                            break;
                        case 'bool':
                            if (!(strtoupper($field['value']) == 'TRUE' || strtoupper($field['value']) == 'FALSE') && !empty($field['value'])) {
                                $error =  str_replace("%type", 'bool (true or false)', $error_type). " Example : True or False";
                            } else {
                                $result['contact_params'][$key] = (bool)$field['value'];
                            }
                            break;
                    }
                    if ($error) {
                        $result['has_error'] = true;
                        $result['error_msg'][] = $error;
                    }
                }
            }
        }else{
            $result['has_error'] = true;
            $result['error_msg'][] = 'Your E-mail address is necessary for your subscription';
        }

        return $result;
    }

    /**
     * @param FormDto|null $form
     * @param array $validatedProperties
     */
    private function handleRegistration(FormDto $form = NULL, array $validatedProperties) {
        $message = 'Unexpected Error!';
        try {
            $mailjet = $this->getMailjet();
            $messageHelper = new DefaultMessagesService($form);

            $confirmMessage = $messageHelper->getConfirmMessage();
            $memberExistMessage = $messageHelper->getMemberExist();
            $listId = $form->getListId();
            $subscribeError = $messageHelper->getSubscribeError();

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

            $clientExists = TRUE;
            $contactParams = [
                'method' => 'GET',
                'ContactEmail' => $form->getEmail(),
                'ContactsList' => $listId,
            ];
            $result = $mailjet->listrecipient($contactParams)->getResponse();
            // 1 - unsubscribed, !=1 - subscribed
            if ($result->Count < 1) {
                $clientExists = FALSE;
            }
            if (!empty($result->Data) && $result->Data[0]->IsUnsubscribed == 1) {
                $clientExists = FALSE;
            }
            if ($clientExists == FALSE) {
                $templateHelper = new TemplatesViewHelper();
                $templateRendition = $templateHelper->getSubscriptionEmailTemplate($emailParams);

                $host = empty($this->settings_keys['smtp_host']) ? "in-v3.mailjet.com" : $this->settings_keys['smtp_host'];
                $smtpPort = empty($this->settings_keys['smtp_port']) ? 587 : $this->settings_keys['smtp_port'];
                $smtpSecure = empty($this->settings_keys['smtp_secure'])? '' : $this->settings_keys['smtp_secure'];

                if (!empty($this->settings_keys['Send']) && $this->settings_keys['Send'] == 1) {
                    require_once(ExtensionManagementUtility::extPath('mailjet', 'Resources/Private/Libraries/phpmailer/PHPMailerAutoload.php'));
                    if (class_exists('PHPMailer')) {
                        $mail = new \PHPMailer();
                        $mail->isSMTP();
                        $mail->Host = $host;
                        $mail->SMTPAuth = TRUE;
                        $mail->Username = $this->settings_keys['apiKeyMailjet'];
                        $mail->Password = $this->settings_keys['secretKey'];
                        $mail->SMTPSecure = $smtpSecure;
                        $mail->Port = $smtpPort;
                        $mail->setFrom($this->settings_keys['sender']);
                        $mail->addAddress($form->getEmail());
                        $mail->Subject = "Please confirm your subscription";
                        if (!empty($this->settings_keys['allowHtml']) && $this->settings_keys['allowHtml'] == 1) {
                            $mail->IsHTML(TRUE);
                        }
                        $mail->Body = $templateRendition;

                        if ($mail->Send()) {
                            $message = $confirmMessage;
                        } else {
                            $message = $subscribeError;
                        }
                    }
                } else {
                    // Create the message
                    $mail = GeneralUtility::makeInstance('TYPO3\CMS\Core\Mail\MailMessage');
                    // Prepare and send the message
                    $mail->setSubject('Please confirm your subscription')
                        ->setFrom($this->settings_keys['sender'])
                        ->setTo($form->getEmail())
                        ->setBody($templateRendition)
                        ->send();
                    $message = $confirmMessage;
                }
            }else {
                $message = $memberExistMessage;
            }
        } catch (MemberExistsException $e) {
            $this->view->assign('error', 'memberExists');
        } catch (GeneralException $e) {
            $this->view->assign('error', 'general');
        }
        $this->view->assignMultiple(['message' => $message,]);
    }

    private function getMailjet()
    {
        if (!is_object($this->mailjet)){
            $this->mailjet = new Mailjet($this->settings_keys['apiKeyMailjet'], $this->settings_keys['secretKey']);
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
        $result = $mailjet->contactdata($contact_params)->getResponse();
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
        $result = [
            'form' => $form,
            'email' => $settings['email'],
            'prop1' => $settings['prop1string'],
            'prop2' => $settings['prop2string'],
            'prop3' => $settings['prop3string'],
            'contact_prop1' => $arr_properties[0],
            'contact_prop2' => $arr_properties[1],
            'contact_prop3' => $arr_properties[2],
            'prop1descpr' =>$settings['prop1descr'],
            'prop2descpr' => $settings['prop2descr'],
            'prop3descpr' => $settings['prop3descr'],
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