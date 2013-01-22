<?php

use Alchemy\Phrasea\Application;

class mail
{
    public static function ftp_sent(Application $app, $email, $subject, $body)
    {
        $to = array('email' => $email, 'name'  => $email);

        return self::send_mail($app, $subject, $body, $to);
    }

    public static function ftp_receive(Application $app, $email, $body)
    {
        $subject = _("task::ftp:Someone has sent some files onto FTP server");

        $to = array('email' => $email, 'name'  => $email);

        return self::send_mail($app, $subject, $body, $to);
    }

}
