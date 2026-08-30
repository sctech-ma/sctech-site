<?php

declare(strict_types=1);

namespace SCTech\Services;

use PHPMailer\PHPMailer\PHPMailer;
use Throwable;

final class PHPMailerTransport implements MailTransport
{
    /** @param array<string, mixed> $config */
    public function __construct(private readonly array $config)
    {
    }

    public function send(MailMessage $message): MailDelivery
    {
        $transport = (string) ($this->config['transport'] ?? 'log');
        if ($transport === 'log') {
            error_log('[SCTECH] Lead notification retained: outbound mail transport is not configured.');

            return new MailDelivery(false, 'transport_not_configured');
        }

        try {
            $mail = new PHPMailer(true);
            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            if ($transport === 'smtp') {
                $mail->isSMTP();
                $mail->Host = (string) ($this->config['host'] ?? '');
                $mail->Port = (int) ($this->config['port'] ?? 587);
                $username = (string) ($this->config['username'] ?? '');
                $mail->SMTPAuth = $username !== '';
                if ($mail->SMTPAuth) {
                    $mail->Username = $username;
                    $mail->Password = (string) ($this->config['password'] ?? '');
                }
                $encryption = (string) ($this->config['encryption'] ?? '');
                if (in_array($encryption, ['tls', 'ssl'], true)) {
                    $mail->SMTPSecure = $encryption;
                }
            } elseif ($transport === 'sendmail') {
                $mail->isSendmail();
            } elseif ($transport === 'mail') {
                $mail->isMail();
            } else {
                return new MailDelivery(false, 'unsupported_transport');
            }

            $mail->setFrom(
                (string) ($this->config['from_address'] ?? 'no-reply@sctech.ma'),
                (string) ($this->config['from_name'] ?? 'SCTECH')
            );
            $mail->addAddress((string) ($this->config['to_address'] ?? 'contact@sctech.ma'));
            $mail->addReplyTo($message->replyToAddress, $message->replyToName);
            $mail->Subject = $message->subject;
            $mail->isHTML(true);
            $mail->Body = $message->htmlBody;
            $mail->AltBody = $message->textBody;
            $mail->send();

            return new MailDelivery(true);
        } catch (Throwable) {
            return new MailDelivery(false, 'delivery_failed');
        }
    }
}
