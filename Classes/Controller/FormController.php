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


class FormController extends ActionController {

  /** @var ApiService $service */
  protected $registrationService;

  public function initializeAction() {
    $this->registrationService = GeneralUtility::makeInstance('Api\\Mailjet\\Service\\ApiService');
  }

  /**
   * @dontvalidate $form
   */
  public function indexAction(FormDto $form = NULL){
    if (!empty($_GET['list']) && !empty($_GET['fj'])) {
      require_once(ExtensionManagementUtility::extPath('mailjet', 'Resources/Private/Contrib/Mailjet/Mailjet.php'));
      $settings_keys = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['mailjet']);
      $mailjet = new Mailjet($settings_keys['apiKeyMailjet'], $settings_keys['secretKey']);
      $list_id = $_GET['list'];
      $sec_code_email = base64_decode($_GET['fj']);
      $contact_params = [
        'method' => 'GET',
        'ContactsList' => $list_id,
      ];
      $result = $mailjet->listrecipient($contact_params)->getResponse();
      if (isset($result->Count) && $result->Count > 0 && $result->Data[0]->IsUnsubscribed === false) {
        $response_message = $this->settings['memberExist'];
      } else {
          $add_params = [
              'method' => 'POST',
              'Action' => 'Add',
              'Force' => TRUE,
              'Addresses' => [$sec_code_email],
              'ListID' => $list_id,
          ];
          $mailjet->resetRequest();
          $response = $mailjet->manycontacts($add_params)->getResponse();
          $response_message = $this->settings['subscribeError'];
          if (isset($response->Total) && $response->Total > 0) {
              $response_message = $this->settings['finalMessage'];
          }
      }
    print $response_message;
  }

    if (is_null($form)) {
      /** @var FormDto $form */
      $form = GeneralUtility::makeInstance('Api\\Mailjet\\Domain\\Model\\Dto\\FormDto');
      $prefill = GeneralUtility::_GP('email');
      if ($prefill) {
        $form->setEmail($prefill);
      }
    }

    $properties = $this->settings['properties'] ? $this->settings['properties'] : '';
    if (is_string($properties)) {
      $arr_properties = explode(",", $properties);
    } else {
      $arr_properties = [
      $form->getProp1(),
      $form->getProp2(),
      $form->getProp3()
      ];
    }

  $this->view->assignMultiple([
      'form' => $form,
      'email' => $this->settings['email'],
      'prop1' => $this->settings['prop1string'],
      'prop2' => $this->settings['prop2string'],
      'prop3' => $this->settings['prop3string'],
      'contact_prop1' => $arr_properties[0],
      'contact_prop2' => $arr_properties[1],
      'contact_prop3' => $arr_properties[2],
      'prop1descpr' => $this->settings['prop1descr'],
      'prop2descpr' => $this->settings['prop2descr'],
      'prop3descpr' => $this->settings['prop3descr'],
      'description' => $this->settings['descpription'],
      'submitLabel' => $this->settings['submitLabel'],
      'headingText' => $this->settings['headingText'],
      'bodyText' => $this->settings['bodyText'],
      'confButton' => $this->settings['confButton'],
      'emailFooterMail' => $this->settings['emailFooterMail'],
      'thanks' => $this->settings['thanks'],
      'owner' => $this->settings['owner'],
      'confMessage' => $this->settings['confMessage'],
      'dataTypeMessage' => $this->settings['dataTypeMessage'],
      'subscribeError' => $this->settings['subscribeError'],
      'memberExist' => $this->settings['memberExist'],
      'finalMessage' => $this->settings['finalMessage'],
      'email_sender' => $this->settings['email_sender'],
      'listId' => $this->settings['listId'],
      'properties' => $this->settings['properties'],
      'emailSender' => $this->settings['emailSender'],
    ]);
  }

  /**
   * @param FormDto $form
   */
  public function responseAction(FormDto $form = NULL) {
    if (is_null($form)) {
      $this->redirect('index');
    }
    $this->validDataReg($form);
    $this->handleRegistration($form);
  }

  /**
   * @param FormDto|null $form
   */
  protected function validDataReg(FormDto $form = NULL) {
    /** @var FormDto $data */
    $settings_keys = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['mailjet']);
    $error_message = 'Incorrect data values. Please enter the correct values according to the example of the description in the field : <%id>';
    $contact_properties = [
      ['value' => $form->getProp1()],
      ['value' => $form->getProp2()],
      ['value' => $form->getProp3()]
    ];
    $errorMess = FALSE;
    $mailjet = new Mailjet($settings_keys['apiKeyMailjet'], $settings_keys['secretKey']);
    if (!(empty($contact_properties))) {
      foreach ($contact_properties as $key => $field) {
        $error_input_data_types = !empty($form->getDataTypeMessage()) ? $form->getDataTypeMessage() : $error_message;
        $error_input_data_types = '<div class="error error-fields">' . $error_input_data_types . '</div>';
        $type = '';
        if (!empty($field['value'])) {
          $error_input_data_types = str_replace("%id", $key, $error_input_data_types);
          $params = ['method' => 'GET', 'limit' => 0,];
          $response = $mailjet->ContactMetaData($params)->getResponse();
          if ($response && isset($response->Count) && $response->Count >= 0) {
            foreach ($response->Data as $property) {
              if ($property->Name == $key) {
                $type = $property->Datatype;
                break;
              }
            }
          }

          switch ($type) {
            case 'int':
              if (!preg_match('/^[0-9]{1,45}$/', $field['value']) && !empty($field['value'])) {
                $error_input_data_types = str_replace("%type", 'number', $error_input_data_types);

                print $error_input_data_types . "Example (numbers): 1234";
                $errorMess = TRUE;
              }
              break;
            case 'str':
              if (!(is_string($field['value'])) && !empty($field['value'])) {
                $error_input_data_types = str_replace("%type", 'string', $error_input_data_types);

                print $error_input_data_types . "Example (text): First Name";
                $errorMess = TRUE;
              }
              break;
            case 'datetime':
              if (!preg_match("/^\s*(3[01]|[12][0-9]|0?[1-9])\-(1[012]|0?[1-9])\-((?:19|20)\d{2})\s*$/", $field['value']) && !empty($field['value'])) {

                $error_input_data_types = str_replace("%type", 'datetime', $error_input_data_types);

                print $error_input_data_types . "Example (DATE): 26-02-2017";
                $errorMess = TRUE;
              }
              else {
                if (!empty($field['value'])) {
                  $date = $field['value'];
                  $date_array = explode("-", $date);
                  if (checkdate($date_array[1], $date_array[0], $date_array[2]) == FALSE) {
                    $error_input_data_types = str_replace("%type", 'datetime', $error_input_data_types);

                    print $error_input_data_types . "Example (DATE): 26-02-2017";
                    $errorMess = TRUE;
                  }
                }
              }
              break;
            case 'bool':
              if (!(strtoupper($field['value']) == 'TRUE' || strtoupper($field['value']) == 'FALSE') && !empty($field['value'])) {
                $error_input_data_types = str_replace("%type", 'bool (true or false)', $error_input_data_types);

                print $error_input_data_types . "Example : True or False";
                $errorMess = TRUE;
              }
              break;
          }
        }
      }
      if ($errorMess === TRUE) {
        $this->errorAction();
      }
    }
  }

  /**
   * @param FormDto|null $form
   */
  protected function handleRegistration(FormDto $form = NULL) {
    try {
      /** @var FormDto $data */
      require_once(ExtensionManagementUtility::extPath('mailjet', 'Resources/Private/Contrib/Mailjet/Mailjet.php'));


      $settings_keys = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['mailjet']);
      $error_message = 'Fatal error! Try again later.';
      $success = 'Subscription confirmation email sent to %email! Please check your inbox and confirm the subscription.';
      $message = '';
      $email = $form->getEmail();
      $prop_names = explode(',', $form->getProperties());
      $contact_properties_raw = [
        ['value' => $form->getProp1()],
        ['value' => $form->getProp2()],
        ['value' => $form->getProp3()]
      ];
      $contact_properties = [];
      foreach ($prop_names as $prop_key => $prop){
        $contact_properties[$prop] = $contact_properties_raw[$prop_key];
      }
      $email_heading_text = !empty($form->getHeadingText()) ? $form->getHeadingText() : 'Please Confirm Your Subscription To';
      $member_exist_text = !empty($form->getMemberExist()) ? $form->getMemberExist() : 'Subscriber exists in Mailjet database! Try different email address for subscribe.';
      $member_exist_text = str_replace('%email', $email, $member_exist_text);
      $final_message = !empty($form->getFinalMessage()) ? $form->getFinalMessage() : "Success!";
      $email_sender = $form->getEmailSender();
      $owner = !empty($form->getOwner()) ? $form->getOwner() : 'Mailjet';
      $conf_message = !empty($form->getConfMessage()) ? $form->getConfMessage() : $success;
      $conf_message = str_replace('%email', $email, $conf_message);
      $error_input_data_types = $form->getDataTypeMessage();
      $sub_error = !empty($form->getSubscribeError()) ? $form->getSubscribeError() : $error_message;
      $email_text_thank_you = !empty($form->getThanks()) ? $form->getThanks() : 'Thanks,';
      $email_footer_text = !empty($form->getEmailFooterMail()) ? $form->getEmailFooterMail() : 'Did not ask to subscribe to this list? Or maybe you have changed your mind? Then simply ignore this email and you will not be subscribed';
      $email_text_button = !empty($form->getConfButton()) ? $form->getConfButton() : 'Click here to confirm';
      $list_id = $form->getListId();
      $email_text_description = !empty($form->getBodyText()) ? $form->getBodyText() : 'You may copy/paste this link into your browser:';
      $mailjet = new Mailjet($settings_keys['apiKeyMailjet'], $settings_keys['secretKey']);
      $check_complate = FALSE;
      $prefix = (isset($_SERVER['HTTPS']) ? "https" : "http");
      $link = "{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
      $link = $prefix . "://" . substr($link, 0, strpos($link, "&"));
      $link .= '&fj=' . base64_encode($email) . '&list=' . $list_id;
      $url = $prefix . "://" . $_SERVER['HTTP_HOST'];
      $data = [];
      $response_exist_user = TRUE;
      $contact_params = [
        'method' => 'GET',
        'ContactEmail' => $email,
        'ContactsList' => $list_id,
      ];
      $result = $mailjet->listrecipient($contact_params);
      // 1 - unsubscribed, !=1 - subscribed
      if ($result->getResponse()->Count < 1) {
        $response_exist_user = FALSE;
      }
      if (!empty($result->getResponse()->Data) && $result->getResponse()->Data[0]->IsUnsubscribed == 1) {
        $response_exist_user = FALSE;
      }
      if ($response_exist_user == FALSE) {
            $neededParams = [
              'email_heading_text',
              'url',
              'email_text_description',
              'email_description',
              'link',
              'email_text_button',
              'email_footer_text',
              'email_text_thank_you',
              'owner',
              ];
            $templateHelper = new TemplatesViewHelper();
            $templateRendition = $templateHelper->getSubscriptionEmailTemplate(compact($neededParams));

          $host = "in-v3.mailjet.com";
          $smtpPort = 587;
          $smtpSecure = '';
          if (!empty($settings_keys['smtp_host'])) {
              $host = $settings_keys['smtp_host'];
          }
          if (!empty($settings_keys['smtp_secure'])) {
              $smtpSecure = $settings_keys['smtp_secure'];
          }
          if (!empty($settings_keys['smtp_port'])) {
              $smtpPort = $settings_keys['smtp_port'];
          }
          if (!empty($settings_keys['Send']) && $settings_keys['Send'] == 1) {
              require_once(ExtensionManagementUtility::extPath('mailjet', 'Resources/Private/Libraries/phpmailer/PHPMailerAutoload.php'));
              if (class_exists('PHPMailer')) {
                  $mail = new \PHPMailer();
                  $mail->isSMTP();
                  $mail->Host = $host;
                  $mail->SMTPAuth = TRUE;
                  $mail->Username = $settings_keys['apiKeyMailjet'];
                  $mail->Password = $settings_keys['secretKey'];
                  $mail->SMTPSecure = $smtpSecure;
                  $mail->Port = $smtpPort;
                  $mail->setFrom($email_sender);
                  $mail->addAddress($email);
                  $mail->Subject = "Activation mail - Mailjet";
                  if (!empty($settings_keys['allowHtml']) && $settings_keys['allowHtml'] == 1) {
                    $mail->IsHTML(TRUE);
                  }
                  $mail->Body = $templateRendition;

                  if ($mail->Send()) {
                    $message = $conf_message;
                  } else {
                    $message = $sub_error;
                  }
              }
         } else {
          // Create the message
            $mail = GeneralUtility::makeInstance('TYPO3\CMS\Core\Mail\MailMessage');
          // Prepare and send the message
            $mail->setSubject('Mailjet Activation Mail')
              ->setFrom($email_sender)
              ->setTo($email)
              ->setBody($templateRendition)
              ->send();
            $message = $conf_message;
        }
      }else {
        $message = $member_exist_text;
      }
    } catch (MemberExistsException $e) {
      $this->view->assign('error', 'memberExists');
    } catch (GeneralException $e) {
      $this->view->assign('error', 'general');
    }
    $this->view->assignMultiple(['form' => $form, 'message' => $message,]);
  }
}