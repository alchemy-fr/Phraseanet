<?php

use Alchemy\Phrasea\Application;

class mail
{

    public static function mail_test(Application $app, $email)
    {
        $from = array('email' => $app['phraseanet.registry']->get('GV_defaulmailsenderaddr'), 'name'  => $app['phraseanet.registry']->get('GV_defaulmailsenderaddr'));

        $subject = _('mail:: test d\'envoi d\'email');

        $message = sprintf(_('Ce mail est un test d\'envoi de mail depuis %s'), $app['phraseanet.registry']->get('GV_ServerName'));

        $to = array('email' => $email, 'name'  => $email);

        return self::send_mail($app, $subject, $message, $to, $from);
    }

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

    public static function send_documents(Application $app, $email, $url, $from, $endate_obj, $message = '', $accuse)
    {
        $subject = _('export::vous avez recu des documents');

        $body = '<div>' . _('Vous avez recu des documents, vous pourrez les telecharger a ladresse suivante ') . "</div>\n";
        $body .= "<a title='' href='" . $url . "'>" . $url . "</a>\n";

        $body .= '<br><div>' .
            sprintf(
                _('Attention, ce lien lien est valable jusqu\'au %s'), $app['date-formatter']->getDate($endate_obj) . ' ' . $app['date-formatter']->getTime($endate_obj)
            )
            . '</div>';

        if ($message != '') {
            $body .= "<div>---------------------------------------------------</div>\n" . $message;
        }

        $to = array('email' => $email, 'name'  => $email);

        return self::send_mail($app, $subject, $body, $to, $from, array(), $accuse);
    }

    public static function forgot_passord(Application $app, $email, $login, $url)
    {
        $subject = _('login:: Forgot your password'); // Registration order on .

        $body = "<div>" . _('login:: Quelqu\'un a demande a reinitialiser le mode passe correspondant au login suivant : ') . "</div><div>\n\n" . $login . "</div>\n\n";
        $body .= "<div>" . _('login:: Visitez le lien suivant et suivez les instructions pour continuer, sinon ignorez cet email et il ne se passera rien') . "</div>\n\n";
        $body .= "<div>" . '<a href="' . $url . '">' . $url . '</a>' . "</div>\n";

        $to = array('email' => $email, 'name'  => $email);

        return self::send_mail($app, $subject, $body, $to);
    }

    public static function register_confirm(Application $app, $email, $accept, $deny)
    {
        $subject = sprintf(_('login::register:email: Votre compte %s'), $app['phraseanet.registry']->get('GV_homeTitle'));

        $body = '<div>' . _('login::register:email: Voici un compte rendu du traitement de vos demandes d\'acces :') . "</div>\n";

        if ($accept != '') {
            $body .= "<br/>\n<div>" . _('login::register:email: Vous avez ete accepte sur les collections suivantes : ') . "</div>\n<ul>" . $accept . "</ul>\n";
        }
        if ($deny != '') {
            $body .= "<br/>\n<div>" . _('login::register:email: Vous avez ete refuse sur les collections suivantes : ') . "</div>\n<ul>" . $deny . "</ul>\n";
        }

        $to = array('email' => $email, 'name'  => $email);

        return self::send_mail($app, $subject, $body, $to);
    }

    public static function reset_email(Application $app, $email, $usr_id)
    {
        $date = new DateTime('1 day');
        $token = random::getUrlToken($app, \random::TYPE_EMAIL, $usr_id, $date, $email);

        $url = $app['phraseanet.registry']->get('GV_ServerName') . 'account/reset-email/?token=' . $token;

        $subject = _('login::register: sujet email : confirmation de votre adresse email');

        $body = "<div>" . _('admin::compte-utilisateur: email changement de mot d\'email Bonjour, nous avons bien recu votre demande de changement d\'adresse e-mail. Pour la confirmer, veuillez suivre le lien qui suit. SI vous recevez ce mail sans l\'avoir sollicite, merci de le detruire et de l\'ignorer.') . "</div>\n";
        $body .= "<div><a href='" . $url . "'>" . $url . "</a></div>\n";

        $to = array('email' => $email, 'name'  => $email);

        return self::send_mail($app, $subject, $body, $to);
    }

    public static function change_mail_information(Application $app, $display_name, $old_email, $new_email)
    {
        $subject = sprintf(_('Update of your email address on %s'), $app['phraseanet.registry']->get('GV_homeTitle'));

        $body = "<div>" . sprintf(_('Dear %s,'), $display_name) . "</div>\n<br/>\n";
        $body .= "<div>" . _('Your contact email address has been updated') . "</div>\n<br/>\n";

        if ($old_email) {
            $body .= "<div>" . sprintf(_('You will no longer receive notifications at %s'), sprintf('<b>%s</b>', $old_email)) . "</div>\n";
        }

        if ($new_email) {
            $body .= "<div>" . sprintf(_('You will now receive notifications at %s'), sprintf('<b>%s</b>', $new_email)) . "</div>\n";
        }

        $to_old = array('email' => $old_email, 'name'  => $display_name);
        $to_new = array('email' => $new_email, 'name'  => $display_name);

        $res_old = $old_email ? self::send_mail($app, $subject, $body, $to_old) : true;
        $res_new = $new_email ? self::send_mail($app, $subject, $body, $to_new) : true;

        return $res_old && $res_new;
    }

    public static function send_credentials(Application $app, $url, $login, $email)
    {
        $subject = sprintf(_('Your account on %s'), $app['phraseanet.registry']->get('GV_homeTitle'));

        $body = "<div>" . sprintf(_('Your account with the login %s as been created'), $login) . "</div><br/>\n\n";
        $body .= "<div>" . _('Please follow this url to setup your password') . "</div>\n";
        $body .= "<div><a href=\"" . $url . "\">" . $url . "</a></div>\n";

        $to = array('email' => $email, 'name'  => $email);

        return self::send_mail($app, $subject, $body, $to);
    }

    public static function mail_confirm_registered(Application $app, $email)
    {
        $subject = _('login::register: sujet email : confirmation de votre adresse email');

        $body = "<div>" . _('login::register: merci d\'avoir confirme votre adresse email') . "</div>\n";
        $body .= "<br/><div>" . _('login::register: vous pouvez maintenant vous connecter a l\'adresse suivante : ') . "</div>\n";
        $body .= "<div><a href='" . $app['phraseanet.registry']->get('GV_ServerName') . "' target='_blank'>" . $app['phraseanet.registry']->get('GV_ServerName') . "</a></div>\n";

        $to = array('email' => $email, 'name'  => $email);

        return self::send_mail($app, $subject, $body, $to);
    }

    public static function mail_confirm_unregistered(Application $app, $email, array $others)
    {

        $subject = _('login::register: sujet email : confirmation de votre adresse email');

        $body = "<div>" . _('login::register: merci d\'avoir confirme votre adresse email') . "</div>\n";
        $body .= "<br/>\n<div>" . _('login::register: vous devez attendre la confirmation d\'un administrateur ; vos demandes sur les collections suivantes sont toujours en attente : ') . "</div>\n";
        $body .= "<ul>";
        foreach ($others as $other) {
            $body .= sprintf("<li>%s</li>", $other);
        }
        $body .= "</ul>\n";
        $body .= "<br/>\n<div>" . _('login::register : vous serez avertis par email lorsque vos demandes seront traitees') . "</div>\n";

        $to = array('email' => $email, 'name'  => $email);

        return self::send_mail($app, $subject, $body, $to);
    }

    public static function mail_confirmation(Application $app, $email, $usr_id)
    {
        $expire = new DateTime('+3 days');
        $token = random::getUrlToken($app, \random::TYPE_PASSWORD, $usr_id, $expire, $email);

        $subject = _('login::register: sujet email : confirmation de votre adresse email');

        $body = "<div>" . _('login::register: email confirmation email Pour valider votre inscription a la base de donnees, merci de confirmer votre e-mail en suivant le lien ci-dessous.') . "</div>\n";
        $body .= "<br/>\n<div><a href='" . $app['phraseanet.registry']->get('GV_ServerName') . "register-confirm/?code=" . $token . "' target='_blank'>" . $app['phraseanet.registry']->get('GV_ServerName') . "register-confirm/code=" . $token . "</a></div>\n";

        $to = array('email' => $email, 'name'  => $email);

        return self::send_mail($app, $subject, $body, $to);
    }

    public static function validateEmail($email)
    {
        return PHPMailer::ValidateAddress($email);
    }

    public static function send_mail(Application $app, $subject, $body, $to, $from = false, $files = array(), $reading_confirm_to = false)
    {
        if ( ! isset($to['email']) || ! PHPMailer::ValidateAddress($to['email'])) {
            return false;
        }

        $mail = new PHPMailer();

        $body .= "<br/><br/><br/><br/>\n\n\n\n";
        $body .= '<div style="font-style:italic;">' . _('si cet email contient des liens non cliquables copiez/collez ces liens dans votre navigateur.') . '</div>';
        $body .= "<br/>\n";
        $body .= '<div style="font-style:italic;">' . _('phraseanet::signature automatique des notifications par mail, infos a l\'url suivante') . "</div>\n";
        $body .= '<div><a href="' . $app['phraseanet.registry']->get('GV_ServerName') . '">' . $app['phraseanet.registry']->get('GV_ServerName') . "</a></div>\n";
        $body = '<body>' . $body . '</body>';

        try {
            $mail->CharSet = 'utf-8';
            $mail->Encoding = 'base64'; //'quoted-printable';

            if ($app['phraseanet.registry']->get('GV_smtp')) {
                $mail->IsSMTP();
                if ($app['phraseanet.registry']->get('GV_smtp_host') != '')
                    $mail->Host = $app['phraseanet.registry']->get('GV_smtp_host');

                if ($app['phraseanet.registry']->get('GV_smtp_auth')) {
                    $mail->SMTPAuth = true;

                    if ($app['phraseanet.registry']->get('GV_smtp_secure') === true) {
                        $mail->SMTPSecure = "ssl";
                    }
                    $mail->Host = $app['phraseanet.registry']->get('GV_smtp_host');
                    $mail->Port = $app['phraseanet.registry']->get('GV_smtp_port');
                    $mail->Username = $app['phraseanet.registry']->get('GV_smtp_user');
                    $mail->Password = $app['phraseanet.registry']->get('GV_smtp_password');
                }
            }

            if ($from && trim($from['email']) != '')
                $mail->AddReplyTo($from['email'], $from['name']);

            $mail->AddAddress($to['email'], $to['name']);

            $mail->SetFrom($app['phraseanet.registry']->get('GV_defaulmailsenderaddr'), $app['phraseanet.registry']->get('GV_homeTitle'));

            $mail->Subject = $subject;

            $mail->AltBody = html_entity_decode(strip_tags($body), ENT_QUOTES, 'UTF-8');

            if ($reading_confirm_to) {
                $mail->ConfirmReadingTo = $reading_confirm_to;
            }

            $mail->MsgHTML(strip_tags($body, '<div><br><ul><li><em><strong><span><br><a>'));

            foreach ($files as $f) {
                $mail->AddAttachment($f);      // attachment
            }

            if ($app->getEnvironment() !== 'test') {
                $mail->Send();
            }

            return true;
        } catch (phpmailerException $e) {
            return $e->errorMessage();
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
