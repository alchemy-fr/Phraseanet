<?php

class mail
{

    public static function mail_test($email)
    {
        $registry = registry::get_instance();
        $from = array('email' => $registry->get('GV_defaulmailsenderaddr'), 'name'  => $registry->get('GV_defaulmailsenderaddr'));

        $subject = _('mail:: test d\'envoi d\'email');

        $message = sprintf(_('Ce mail est un test d\'envoi de mail depuis %s'), $registry->get('GV_ServerName'));

        $to = array('email' => $email, 'name'  => $email);

        return self::send_mail($subject, $message, $to, $from);
    }

    public static function send_validation_results($email, $subject, $from, $message)
    {
        $to = array('email' => $email, 'name'  => $email);

        return self::send_mail($subject, $message, $to, $from);
    }

    public static function ftp_sent($email, $subject, $body)
    {
        $to = array('email' => $email, 'name'  => $email);

        return self::send_mail($subject, $body, $to);
    }

    public static function ftp_receive($email, $body)
    {
        $subject = _("task::ftp:Someone has sent some files onto FTP server");

        $to = array('email' => $email, 'name'  => $email);

        return self::send_mail($subject, $body, $to);
    }

    public static function send_documents($email, $url, $from, $endate_obj, $message = '', $accuse)
    {
        $subject = _('export::vous avez recu des documents');

        $body = '<div>' . _('Vous avez recu des documents, vous pourrez les telecharger a ladresse suivante ') . "</div>\n";
        $body .= "<a title='' href='" . $url . "'>" . $url . "</a>\n";

        $body .= '<br><div>' .
            sprintf(
                _('Attention, ce lien lien est valable jusqu\'au %s'), phraseadate::getDate($endate_obj) . ' ' . phraseadate::getTime($endate_obj)
            )
            . '</div>';

        if ($message != '') {
            $body .= "<div>---------------------------------------------------</div>\n" . $message;
        }

        $to = array('email' => $email, 'name'  => $email);

        return self::send_mail($subject, $body, $to, $from, array(), $accuse);
    }

    public static function forgot_passord($email, $login, $url)
    {
        $subject = _('login:: Forgot your password'); // Registration order on .

        $body = "<div>" . _('login:: Quelqu\'un a demande a reinitialiser le mode passe correspondant au login suivant : ') . "</div><div>\n\n" . $login . "</div>\n\n";
        $body .= "<div>" . _('login:: Visitez le lien suivant et suivez les instructions pour continuer, sinon ignorez cet email et il ne se passera rien') . "</div>\n\n";
        $body .= "<div>" . '<a href="' . $url . '">' . $url . '</a>' . "</div>\n";

        $to = array('email' => $email, 'name'  => $email);

        return self::send_mail($subject, $body, $to);
    }

    public static function register_confirm($email, $accept, $deny)
    {
        $registry = registry::get_instance();
        $subject = sprintf(_('login::register:email: Votre compte %s'), $registry->get('GV_homeTitle'));

        $body = '<div>' . _('login::register:email: Voici un compte rendu du traitement de vos demandes d\'acces :') . "</div>\n";

        if ($accept != '') {
            $body .= "<br/>\n<div>" . _('login::register:email: Vous avez ete accepte sur les collections suivantes : ') . "</div>\n<ul>" . $accept . "</ul>\n";
        }
        if ($deny != '') {
            $body .= "<br/>\n<div>" . _('login::register:email: Vous avez ete refuse sur les collections suivantes : ') . "</div>\n<ul>" . $deny . "</ul>\n";
        }

        $to = array('email' => $email, 'name'  => $email);

        return self::send_mail($subject, $body, $to);
    }

    public static function register_user($email, $auto, $others)
    {
        $registry = registry::get_instance();
        $subject = sprintf(_('login::register:email: Votre compte %s'), $registry->get('GV_homeTitle'));

        $body = "<div>" . _('login::register:Votre inscription a ete prise en compte') . "</div>\n";

        if ($auto != '') {
            $body .= "<br/>\n<div>" . _('login::register: vous avez des a present acces aux collections suivantes : ') . "</div>\n<ul>" . $auto . "</ul>\n";
        }

        if ($others != '') {
            $body .= "<br/>\n<div>" . _('login::register: vos demandes concernat les collections suivantes sont sujettes a approbation d\'un administrateur') . "</div>\n<ul>" . $others . "</ul>\n";
            $body .= "<br/>\n<div>" . _('login::register : vous serez avertis par email lorsque vos demandes seront traitees') . "</div>\n";
        }

        $to = array('email' => $email, 'name'  => $email);

        return self::send_mail($subject, $body, $to);
    }

    public static function reset_email($email, $usr_id)
    {
        $registry = registry::get_instance();
        $date = new DateTime('1 day');
        $token = random::getUrlToken(\random::TYPE_EMAIL, $usr_id, $date, $email);

        $url = $registry->get('GV_ServerName') . 'login/reset-email.php?token=' . $token;

        $subject = _('login::register: sujet email : confirmation de votre adresse email');

        $body = "<div>" . _('admin::compte-utilisateur: email changement de mot d\'email Bonjour, nous avons bien recu votre demande de changement d\'adresse e-mail. Pour la confirmer, veuillez suivre le lien qui suit. SI vous recevez ce mail sans l\'avoir sollicite, merci de le detruire et de l\'ignorer.') . "</div>\n";
        $body .= "<div><a href='" . $url . "'>" . $url . "</a></div>\n";

        $to = array('email' => $email, 'name'  => $email);

        return self::send_mail($subject, $body, $to);
    }

    public static function change_mail_information($display_name, $old_email, $new_email)
    {
        $registry = registry::get_instance();
        $subject = sprintf(_('Update of your email address on %s'), $registry->get('GV_homeTitle'));

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

        $res_old = $old_email ? self::send_mail($subject, $body, $to_old) : true;
        $res_new = $new_email ? self::send_mail($subject, $body, $to_new) : true;

        return $res_old && $res_new;
    }

    public static function change_password(User_Adapter $user, $ip, \DateTime $date)
    {
        $registry = registry::get_instance();

        $subject = sprintf(_('Your account update on %s'), $registry->get('GV_homeTitle'));

        $body = "<div>" . sprintf(_('Dear %s,'), $user->get_display_name()) . "</div><br/>\n\n";
        $body .= "<div>" . sprintf(_('The password of your account %s has been successfully updated'), $user->get_login()) . "</div><br/>\n\n";
        $body .= "<div>" . sprintf(_('For your interest, the request has been done from %s at %s'), $ip, $date->format(DATE_ATOM)) . "</div>\n";

        $to = array('email' => $user->get_email(), 'name'  => $user->get_email());

        return self::send_mail($subject, $body, $to);
    }

    public static function send_credentials($url, $login, $email)
    {
        $registry = registry::get_instance();

        $subject = sprintf(_('Your account on %s'), $registry->get('GV_homeTitle'));

        $body = "<div>" . sprintf(_('Your account with the login %s as been created'), $login) . "</div><br/>\n\n";
        $body .= "<div>" . _('Please follow this url to setup your password') . "</div>\n";
        $body .= "<div><a href=\"" . $url . "\">" . $url . "</a></div>\n";

        $to = array('email' => $email, 'name'  => $email);

        return self::send_mail($subject, $body, $to);
    }

    public static function mail_confirm_registered($email)
    {
        $registry = \registry::get_instance();

        $subject = _('login::register: sujet email : confirmation de votre adresse email');

        $body = "<div>" . _('login::register: merci d\'avoir confirme votre adresse email') . "</div>\n";
        $body .= "<br/><div>" . _('login::register: vous pouvez maintenant vous connecter a l\'adresse suivante : ') . "</div>\n";
        $body .= "<div><a href='" . $registry->get('GV_ServerName') . "' target='_blank'>" . $registry->get('GV_ServerName') . "</a></div>\n";

        $to = array('email' => $email, 'name'  => $email);

        return self::send_mail($subject, $body, $to);
    }

    public static function mail_confirm_unregistered($email, $others)
    {

        $subject = _('login::register: sujet email : confirmation de votre adresse email');

        $body = "<div>" . _('login::register: merci d\'avoir confirme votre adresse email') . "</div>\n";
        $body .= "<br/>\n<div>" . _('login::register: vous devez attendre la confirmation d\'un administrateur ; vos demandes sur les collections suivantes sont toujours en attente : ') . "</div>\n<ul>" . $others . "</ul>\n";
        $body .= "<br/>\n<div>" . _('login::register : vous serez avertis par email lorsque vos demandes seront traitees') . "</div>\n";

        $to = array('email' => $email, 'name'  => $email);

        return self::send_mail($subject, $body, $to);
    }

    public static function mail_confirmation($email, $usr_id)
    {
        $registry = registry::get_instance();
        $expire = new DateTime('+3 days');
        $token = random::getUrlToken(\random::TYPE_PASSWORD, $usr_id, $expire, $email);

        $subject = _('login::register: sujet email : confirmation de votre adresse email');

        $body = "<div>" . _('login::register: email confirmation email Pour valider votre inscription a la base de donnees, merci de confirmer votre e-mail en suivant le lien ci-dessous.') . "</div>\n";
        $body .= "<br/>\n<div><a href='" . $registry->get('GV_ServerName') . "register-confirm=" . $token . "' target='_blank'>" . $registry->get('GV_ServerName') . "register-confirm=" . $token . "</a></div>\n";

        $to = array('email' => $email, 'name'  => $email);

        return self::send_mail($subject, $body, $to);
    }

    public static function validateEmail($email)
    {
        return PHPMailer::ValidateAddress($email);
    }

    public static function send_mail($subject, $body, $to, $from = false, $files = array(), $reading_confirm_to = false)
    {
        $Core = \bootstrap::getCore();

        $registry = $Core->getRegistry();

        if ( ! isset($to['email']) || ! PHPMailer::ValidateAddress($to['email'])) {
            return false;
        }

        $mail = new PHPMailer();

        $body = eregi_replace("[\]", '', $body);

        $body .= "<br/><br/><br/><br/>\n\n\n\n";
        $body .= '<div style="font-style:italic;">' . _('si cet email contient des liens non cliquables copiez/collez ces liens dans votre navigateur.') . '</div>';
        $body .= "<br/>\n";
        $body .= '<div style="font-style:italic;">' . _('phraseanet::signature automatique des notifications par mail, infos a l\'url suivante') . "</div>\n";
        $body .= '<div><a href="' . $registry->get('GV_ServerName') . '">' . $registry->get('GV_ServerName') . "</a></div>\n";
        $body = '<body>' . $body . '</body>';

        try {
            $mail->CharSet = 'utf-8';
            $mail->Encoding = 'base64'; //'quoted-printable';

            $registry = registry::get_instance();

            if ($registry->get('GV_smtp')) {
                $mail->IsSMTP();
                if ($registry->get('GV_smtp_host') != '')
                    $mail->Host = $registry->get('GV_smtp_host');
//        $mail->SMTPDebug  = 2;                     // enables SMTP debug information (for testing)
                if ($registry->get('GV_smtp_auth')) {
                    $mail->SMTPAuth = true;

                    if ($registry->get('GV_smtp_secure') === true) {
                        $mail->SMTPSecure = "ssl";
                    }
                    $mail->Host = $registry->get('GV_smtp_host');
                    $mail->Port = $registry->get('GV_smtp_port');
                    $mail->Username = $registry->get('GV_smtp_user');
                    $mail->Password = $registry->get('GV_smtp_password');
                }
            }

            if ($from && trim($from['email']) != '')
                $mail->AddReplyTo($from['email'], $from['name']);

            $mail->AddAddress($to['email'], $to['name']);

            $mail->SetFrom($registry->get('GV_defaulmailsenderaddr'), $registry->get('GV_homeTitle'));

            $mail->Subject = $subject;

            $mail->AltBody = html_entity_decode(strip_tags($body), ENT_QUOTES, 'UTF-8');

            if ($reading_confirm_to) {
                $mail->ConfirmReadingTo = $reading_confirm_to;
            }

            $mail->MsgHTML(strip_tags($body, '<div><br><ul><li><em><strong><span><br><a>'));

            foreach ($files as $f) {
                $mail->AddAttachment($f);      // attachment
            }

            if ($Core->getConfiguration()->getEnvironnement() !== 'test') {
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
