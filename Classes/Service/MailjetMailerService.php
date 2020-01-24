<?php


namespace Api\Mailjet\Service;


use Api\Mailjet\Exception\ApiKeyMissingException;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

class MailjetMailerService {
    private $apiKey;
    private $secretKey;
    private $host;
    private $smtpSecure;
    private $port;

    public function __construct($apiKey, $secretKey, $host = 'in-v3.mailjet.com', $smtpSecure = '', $port = 587) {
        if (empty($apiKey) || empty($secretKey)) {
            throw new ApiKeyMissingException('Missing API key');
        }
        $this->apiKey = $apiKey;
        $this->secretKey = $secretKey;
        $this->host = empty($host) ? 'in-v3.mailjet.com' : $host;
        $this->smtpSecure = $smtpSecure;
        $this->port = empty($port) ? 587 : $port;
    }

    public function send($sender, $recipient, $subject, $body, $isHtml = false) : bool {
        require_once(ExtensionManagementUtility::extPath('mailjet', 'Resources/Private/Libraries/phpmailer/PHPMailerAutoload.php'));
        $mail = new \PHPMailer();
        $mail->isSMTP();
        $mail->Host = $this->host;
        $mail->SMTPAuth = TRUE;
        $mail->Username = $this->apiKey;
        $mail->Password = $this->secretKey;
        $mail->SMTPSecure = $this->smtpSecure;
        $mail->Port = $this->port;
        $mail->setFrom($sender);
        $mail->addAddress($recipient);
        $mail->Subject = $subject;
        if ($isHtml) {
            $mail->IsHTML(TRUE);
        }
        $mail->Body = $body;

        return $mail->Send();
    }
}