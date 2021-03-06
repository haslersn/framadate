<?php
namespace Framadate\Services;

use PHPMailer\PHPMailer\PHPMailer;

class MailService {
    const DELAY_BEFORE_RESEND = 300;

    const MAILSERVICE_KEY = 'mailservice';

    private $smtp_allowed;

    private $smtp_options = [];

    private $logService;

    function __construct($smtp_allowed, $smtp_options = []) {
        $this->logService = new LogService();
        $this->smtp_allowed = $smtp_allowed;
        if (true === is_array($smtp_options)) {
            $this->smtp_options = $smtp_options;
        }
    }

    public function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public function send($to, $subject, $body, $msgKey = null) {
        if ($this->smtp_allowed === true && $this->canSendMsg($msgKey)) {
            $mail = new PHPMailer(true);
            $this->configureMailer($mail);

            // From
            $mail->FromName = NOMAPPLICATION;
            $mail->From = ADRESSEMAILADMIN;
            if ($this->isValidEmail(ADRESSEMAILREPONSEAUTO)) {
                $mail->addReplyTo(ADRESSEMAILREPONSEAUTO);
            }

            // To
            $mail->addAddress($to);

            // Subject
            $mail->Subject = $subject;

            // Bodies
            $body = $body . ' <br/><br/>' . __('Mail', 'Thanks for your trust.') . ' <br/>' . NOMAPPLICATION . ' <hr/>' . __('Mail', 'FOOTER');
            $mail->isHTML(true);
            $mail->msgHTML($body, ROOT_DIR, true);

            // Build headers
            $mail->CharSet = 'UTF-8';
            $mail->addCustomHeader('Auto-Submitted', 'auto-generated');
            $mail->addCustomHeader('Return-Path', '<>');

            // Send mail
            $mail->send();

            // Log
            $this->logService->log('MAIL', 'Mail sent to: ' . $to . ', key: ' . $msgKey);

            // Store the mail sending date
            $_SESSION[self::MAILSERVICE_KEY][$msgKey] = time();
        }
    }

    public function canSendMsg($msgKey) {
        if ($msgKey === null) {
            return true;
        }

        if (!isset($_SESSION[self::MAILSERVICE_KEY])) {
            $_SESSION[self::MAILSERVICE_KEY] = [];
        }
        return !isset($_SESSION[self::MAILSERVICE_KEY][$msgKey]) || time() - $_SESSION[self::MAILSERVICE_KEY][$msgKey] > self::DELAY_BEFORE_RESEND;
    }

    /**
     * Configure the mailer with the options
     *
     * @param PHPMailer $mailer
     */
    private function configureMailer(PHPMailer $mailer) {
        $mailer->isSMTP();

        $available_options = [
            'host' => 'Host',
            'auth' => 'SMTPAuth',
            'username' => 'Username',
            'password' => 'Password',
            'secure' => 'SMTPSecure',
            'port' => 'Port',
        ];

        foreach ($available_options as $config_option => $mailer_option) {
            if (true === isset($this->smtp_options[$config_option]) && false === empty($this->smtp_options[$config_option])) {
                $mailer->{$mailer_option} = $this->smtp_options[$config_option];
            }
        }
    }
}
