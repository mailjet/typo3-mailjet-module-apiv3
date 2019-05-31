<?php


namespace Api\Mailjet\Service;


use Api\Mailjet\Domain\Model\Dto\FormDto;

class DefaultMessagesService
{
    private $form;

    private $confMessage = 'Subscription confirmation email sent to %email. Please check your inbox and confirm the subscription.';

    private $subscribeError = 'Subscribe error. Please try again later!';

    private $memberExist = 'The contact %email is already subscribed!';

    private $thanksMessage = 'Thanks for subscribing!';

    private $headingText = 'Please Confirm Your Subscription To';

    private $emailFooterMsg = 'Did not ask to subscribe to this list? Or maybe you have changed your mind? Then simply ignore this email and you will not be subscribed.';

    private $confirmationBtnText = 'Click here to confirm';

    private $bodyMessage = 'You may copy/paste this link into your browser:';

    private $owner = 'Mailjet';

    private static $successMessage = 'You have successfully subscribed!';

    private static $dataTypeMessage = 'Please enter the correct values according to the example of the description in the field: %id.';

    public function __construct(FormDto $formDto)
    {
        $this->form = $formDto;
    }

    public function getConfirmMessage()
    {
        $message = $this->confMessage;
        $email = $this->form->getEmail();

        if (!empty($this->form->getConfMessage())) {
            $message = $this->form->getConfMessage();
        }
        $message = str_replace('%email', $email, $message);

        return $message;
    }

    public function getMemberExist()
    {
        $message = $this->memberExist;
        $email = $this->form->getEmail();

        if (!empty($this->form->getMemberExist())) {
            $message = $this->form->getMemberExist();
        }
        $message = str_replace('%email', $email, $message);

        return $message;
    }

    public function getHeadingText()
    {
        if(!empty($this->form->getHeadingText())){
            return $this->form->getHeadingText();
        }

        return $this->headingText;
    }

    public function getSubscribeError()
    {
        if(!empty($this->form->getSubscribeError())){
            return $this->form->getSubscribeError();
        }

        return $this->subscribeError;
    }

    public function getThanksMessage()
    {
        if(!empty($this->form->getThanks())){
            return $this->form->getThanks();
        }

        return $this->thanksMessage;
    }

    public function getEmailFooterMessage()
    {
        if(!empty($this->form->getEmailFooterMail())){
            return $this->form->getEmailFooterMail();
        }

        return $this->emailFooterMsg;
    }

    public function getConfButtonText()
    {
        if(!empty($this->form->getConfButton())){
            return $this->form->getConfButton();
        }

        return $this->confirmationBtnText;
    }

    public function getBodyMessage()
    {
        if(!empty($this->form->getBodyText())){
            return $this->form->getBodyText();
        }

        return $this->bodyMessage;
    }

    public function getOwner()
    {
        if(!empty($this->form->getOwner())){
            return $this->form->getOwner();
        }

        return $this->owner;
    }

    public static function getSuccessMessage($message)
    {
        if (!empty($message)){
            return $message;
        }

        return self::$successMessage;
    }

    public static function getDataTypeMsg($message)
    {
        if (!empty($message)){
            return $message;
        }

        return self::$dataTypeMessage;
    }
}