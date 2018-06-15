<?php

namespace App\Utils;

class Mailer
{
    public static function send($to, $subject ='', $body, $from = 'email@domain.com')
    {
        $transport = new \Swift_SendmailTransport('/usr/sbin/sendmail -t');
        $mailer = new \Swift_Mailer($transport);
        $message = (new \Swift_Message($subject))
            ->setFrom($from)
            ->setTo($to)
            ->setBody($body)
        ;

        $mailer->send($message);
    }
}

