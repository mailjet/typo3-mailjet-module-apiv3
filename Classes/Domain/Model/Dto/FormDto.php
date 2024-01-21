<?php

namespace Api\Mailjet\Domain\Model\Dto;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class FormDto extends AbstractEntity {

  /**
   * @var string
   */
  protected $email;

  /** @var string */
  protected $prop1;

  /** @var string */
  protected $prop2;

  /** @var string */
  protected $prop3;

  /** @var string */
  protected $emailSender;

  /** @var string */
  protected $finalMessage;

  /** @var string */
  protected $memberExist;

  /** @var string */
  protected $subscribeError;

  /** @var string */
  protected $dataTypeMessage;

  /** @var string */
  protected $confMessage;

  /** @var string */
  protected $owner;

  /** @var string */
  protected $thanks;

  /** @var string */
  protected $emailFooterMail;

  /** @var string */
  protected $confButton;

  /** @var string */
  protected $bodyText;

  /** @var string */
  protected $headingText;

  /** @var string */
  protected $submitLabel;

  /** @var string */
  protected $listId;

  /** @var string */
  protected $description;

  /** @var string */
  protected $properties;


  /**
   * @return string
   */
  public function getBodyText() {
    return $this->bodyText;
  }

  /**
   * @param string $description
   */
  public function setBodyText($bodyText) {
    $this->bodyText = $bodyText;
  }


  /**
   * @return string
   */
  public function getProperties() {
    return $this->properties;
  }

  /**
   * @param string $description
   */
  public function setProperties($properties) {
    $this->properties = $properties;
  }

  /**
   * @return string
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * @param string $description
   */
  public function setDescription($description) {
    $this->description = $description;
  }

  /**
   * @return int
   */
  public function getListId() {
    return $this->listId;
  }

  /**
   * @param string $list_id
   */
  public function setListId($list_id) {
    $this->listId = $list_id;
  }

  /**
   * @return string
   */
  public function getSubmitLabel() {
    return $this->submitLabel;
  }

  /**
   * @param string $subscribeError
   */
  public function setSubmitLabel($submitLabel) {
    $this->submitLabel = $submitLabel;
  }

  /**
   * @return string
   */
  public function getHeadingText() {
    return $this->headingText;
  }

  /**
   * @param string $headingText
   */
  public function setHeadingText($headingText) {
    $this->headingText = $headingText;
  }

  /**
   * @return string
   */
  public function getConfButton() {
    return $this->confButton;
  }

  /**
   * @param string $confButton
   */
  public function setConfButton($confButton) {
    $this->confButton = $confButton;
  }


  /**
   * @return string
   */
  public function getEmailFooterMail() {
    return $this->emailFooterMail;
  }

  /**
   * @param string $emailFooterMai
   */
  public function setEmailFooterMail($emailFooterMail) {
    $this->emailFooterMail = $emailFooterMail;
  }


  /**
   * @return string
   */
  public function getThanks() {
    return $this->thanks;
  }

  /**
   * @param string $thanks
   */
  public function setThanks($thanks) {
    $this->thanks = $thanks;
  }

  /**
   * @return string
   */
  public function getOwner() {
    return $this->owner;
  }

  /**
   * @param string $owner
   */
  public function setOwner($owner) {
    $this->owner = $owner;
  }

  /**
   * @return string
   */
  public function getConfMessage() {
    return $this->confMessage;
  }

  /**
   * @param string $confMessage
   */
  public function setConfMessage($confMessage) {
    $this->confMessage = $confMessage;
  }

  /**
   * @return stringsubscribeError
   */
  public function getDataTypeMessage() {
    return $this->dataTypeMessage;
  }

  /**
   * @param string $dataTypeMessage
   */
  public function setDataTypeMessage($dataTypeMessage) {
    $this->dataTypeMessage = $dataTypeMessage;
  }

  /**
   * @return string
   */
  public function getSubscribeError() {
    return $this->subscribeError;
  }

  /**
   * @param string $subscribeError
   */
  public function setSubscribeError($subscribeError) {
    $this->subscribeError = $subscribeError;
  }

  /**
   * @return string
   */
  public function getMemberExist() {
    return $this->memberExist;
  }

  /**
   * @param string $memberExist
   */
  public function setMemberExist($memberExist) {
    $this->memberExist = $memberExist;
  }

  /**
   * @return string
   */
  public function getFinalMessage() {
    return $this->finalMessage;
  }

  /**
   * @param string $finalMessage
   */
  public function setFinalMessage($finalMessage) {
    $this->finalMessage = $finalMessage;
  }


  /**
   * @return string
   */
  public function getEmailSender() {
    return $this->emailSender;
  }

  /**
   * @param string $EmailSender
   */
  public function setEmailSender($emailSender) {
    $this->emailSender = $emailSender;
  }

  /**
   * @return string
   */
  public function getProp1() {
    return $this->prop1;
  }


  /**
   * @param string $prop1
   */
  public function setProp1($prop1) {
    $this->prop1 = $prop1;
  }

  /**
   * @return string
   */
  public function getProp2() {
    return $this->prop2;
  }


  /**
   * @param string $prop2
   */
  public function setProp2($prop2) {
    $this->prop2 = $prop2;
  }

  /**
   * @return string
   */
  public function getProp3() {
    return $this->prop3;
  }


  /**
   * @param string $prop3
   */
  public function setProp3($prop3) {
    $this->prop3 = $prop3;
  }


  /**
   * @return string
   */
  public function getEmail() {
    return $this->email;
  }

  /**
   * @param string $email
   */
  public function setEmail($email) {
    $this->email = $email;
  }

}
