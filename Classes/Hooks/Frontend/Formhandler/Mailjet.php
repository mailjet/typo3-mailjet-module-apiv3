<?php

namespace Api\Mailjet\Hooks\Frontend\Formhandler;

use Api\Mailjet\Domain\Model\Dto\FormDto;
use Api\Mailjet\Service\ApiService;
use TYPO3\CMS\Core\Utility\GeneralUtility;


class Mailjet extends \Tx_Formhandler_AbstractFinisher {

  protected $api;

  public function process() {

    return;
  }

  /**
   * @return FormDto
   */
  protected function getData() {
    /** @var FormDto $data */
    $data = GeneralUtility::makeInstance('Api\\Mailjet\\Domain\\Model\\Dto\\FormDto');

    $emailField = $this->utilityFuncs->getSingle($this->settings, 'fieldEmail');
    if ($emailField && $this->gp[$emailField]) {
      $data->setEmail($this->gp[$emailField]);
    }

    $prop1Field = $this->utilityFuncs->getSingle($this->settings, 'fieldProp1');

    if ($prop1Field && $this->gp[$prop1Field]) {
      $data->setProp1($this->gp[$prop1Field]);
    }

    $prop2Field = $this->utilityFuncs->getSingle($this->settings, 'fieldProp2');

    if ($prop2Field && $this->gp[$prop2Field]) {
      $data->setProp2($this->gp[$prop2Field]);
    }

    $prop3Field = $this->utilityFuncs->getSingle($this->settings, 'fieldProp3');

    if ($prop3Field && $this->gp[$prop3Field]) {
      $data->setProp3($this->gp[$prop3Field]);
    }

    $emailSenderField = $this->utilityFuncs->getSingle($this->settings, 'fieldЕmailSender');
    if ($emailSenderField && $this->gp[$emailSenderField]) {
      $data->setEmailSender($this->gp[$emailSenderField]);
    }

    $finalMessageField = $this->utilityFuncs->getSingle($this->settings, 'fieldFinalMessage');
    if ($finalMessageField && $this->gp[$finalMessageField]) {
      $data->setFinalMessage($this->gp[$finalMessageField]);
    }

    $memberExistField = $this->utilityFuncs->getSingle($this->settings, 'fieldMemberExist');
    if ($memberExistField && $this->gp[$memberExistField]) {
      $data->setMemberExist($this->gp[$memberExistField]);
    }

    $subscribeErrortField = $this->utilityFuncs->getSingle($this->settings, 'fieldSubscribeError');
    if ($subscribeErrortField && $this->gp[$subscribeErrortField]) {
      $data->setSubscribeErrortField($this->gp[$subscribeErrortField]);
    }

    $dataTypeMessageField = $this->utilityFuncs->getSingle($this->settings, 'fieldDataTypeMessage');
    if ($dataTypeMessageField && $this->gp[$dataTypeMessageField]) {
      $data->setDataTypeMessage($this->gp[$dataTypeMessageField]);
    }

    $confMessageField = $this->utilityFuncs->getSingle($this->settings, 'fieldConfMessage');
    if ($confMessageField && $this->gp[$confMessageField]) {
      $data->setConfMessage($this->gp[$confMessageField]);
    }

    $ownerField = $this->utilityFuncs->getSingle($this->settings, 'fieldOwner');
    if ($ownerField && $this->gp[$ownerField]) {
      $data->setOwner($this->gp[$ownerField]);
    }

    $thanksField = $this->utilityFuncs->getSingle($this->settings, 'fieldThanks');
    if ($thanksField && $this->gp[$thanksField]) {
      $data->setThanks($this->gp[$thanksField]);
    }

    $emailFooterMailField = $this->utilityFuncs->getSingle($this->settings, 'fieldEmailFooterMail');
    if ($emailFooterMailField && $this->gp[$emailFooterMailField]) {
      $data->setEmailFooterMail($this->gp[$emailFooterMailField]);
    }

    $confButtonField = $this->utilityFuncs->getSingle($this->settings, 'fieldConfButton');
    if ($confButtonField && $this->gp[$confButtonField]) {
      $data->setConfButton($this->gp[$confButtonField]);
    }

    $bodyTextField = $this->utilityFuncs->getSingle($this->settings, 'fieldBodyText');
    if ($bodyTextField && $this->gp[$bodyTextField]) {
      $data->setBodyText($this->gp[$bodyTextField]);
    }

    $headingTextField = $this->utilityFuncs->getSingle($this->settings, 'fieldHeadingText');
    if ($headingTextField && $this->gp[$headingTextField]) {
      $data->setHeadingText($this->gp[$headingTextField]);
    }


    $submitLabelField = $this->utilityFuncs->getSingle($this->settings, 'fieldSubmitLabel');
    if ($submitLabelField && $this->gp[$submitLabelField]) {
      $data->setSubmitLabel($this->gp[$submitLabelField]);
    }

    $listIdField = $this->utilityFuncs->getSingle($this->settings, 'fieldListId');
    if ($listIdField && $this->gp[$listIdField]) {
      $data->setListId($this->gp[$listIdField]);
    }

    $propertiesField = $this->utilityFuncs->getSingle($this->settings, 'fieldProperties');
    if ($propertiesField && $this->gp[$propertiesField]) {
      $data->setProperties($this->gp[$propertiesField]);
    }


    return $data;
  }

}