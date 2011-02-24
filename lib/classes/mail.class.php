<?php

class mail
{

  public static function mail_test($email)
  {
    $from = array('email' => GV_defaulmailsenderaddr, 'name' => GV_defaulmailsenderaddr);

    $subject = _('mail:: test d\'envoi d\'email');

    $message = sprintf(_('Ce mail est un test d\'envoi de mail depuis %s'), GV_ServerName);

    $to = array('email' => $email, 'name' => $email);

    return self::send_mail($subject, $message, $to, $from);
  }

  public static function send_validation_results($email, $subject, $from, $message)
  {
    $to = array('email' => $email, 'name' => $email);

    return self::send_mail($subject, $message, $to, $from);
  }

  public static function hack_alert($email, $body)
  {
    $subject = 'Hack on ' . GV_homeTitle;

    $to = array('email' => $email, 'name' => $email);

    return self::send_mail($subject, $body, $to);
  }

  public static function ftp_sent($email, $subject, $body)
  {
    $to = array('email' => $email, 'name' => $email);

    return self::send_mail($subject, $body, $to);
  }

  public static function ftp_receive($email, $body)
  {
    $subject = _("task::ftp:Someone has sent some files onto FTP server");

    $to = array('email' => $email, 'name' => $email);

    return self::send_mail($subject, $body, $to);
  }

  public static function send_documents($email, $url, $from, $endate_obj, $message='', $accuse)
  {
    $subject = _('export::vous avez recu des documents');

    $body = '<div>' . _('Vous avez recu des documents, vous pourrez les telecharger a ladresse suivante ') . "</div>\n";
    $body .= "<a title='' href='" . $url . "'>" . $url . "</a>\n";

    $body .= '<br><div>' .
            sprintf(
                    _('Attention, ce lien lien est valable jusqu\'au %s'),
                    phraseadate::getDate($endate_obj) . ' ' . phraseadate::getTime($endate_obj)
            )
            . '</div>';


    if ($message != '')
    {
      $body .= "<div>---------------------------------------------------</div>\n" . $message;
    }

    $to = array('email' => $email, 'name' => $email);

    return self::send_mail($subject, $body, $to, $from, array(), $accuse);
  }

  public static function forgot_passord($email, $login, $url)
  {
    $subject = _('login:: Forgot your password'); // Registration order on .

    $body = "<div>" . _('login:: Quelqu\'un a demande a reinitialiser le mode passe correspondant au login suivant : ') . "</div><div>\n\n" . $login . "</div>\n\n";
    $body .= "<div>" . _('login:: Visitez le lien suivant et suivez les instructions pour continuer, sinon ignorez cet email et il ne se passera rien') . "</div>\n\n";
    $body .= "<div>" . '<a href="' . $url . '">' . $url . '</a>' . "</div>\n";

    $to = array('email' => $email, 'name' => $email);

    return self::send_mail($subject, $body, $to);
  }

  public static function register_confirm($email, $accept, $deny)
  {

    $subject = sprintf(_('login::register:email: Votre compte %s'), GV_homeTitle);

    $body = '<div>' . _('login::register:email: Voici un compte rendu du traitement de vos demandes d\'acces :') . "</div>\n";

    if ($accept != '')
    {
      $body .= "<br/>\n<div>" . _('login::register:email: Vous avez ete accepte sur les collections suivantes : ') . "</div>\n<ul>" . $accept . "</ul>\n";
    }
    if ($deny != '')
    {
      $body .= "<br/>\n<div>" . _('login::register:email: Vous avez ete refuse sur les collections suivantes : ') . "</div>\n<ul>" . $deny . "</ul>\n";
    }

    $to = array('email' => $email, 'name' => $email);

    return self::send_mail($subject, $body, $to);
  }

  public static function register_user($email, $auto, $others)
  {

    $subject = sprintf(_('login::register:email: Votre compte %s'), GV_homeTitle);


    $body = "<div>" . _('login::register:Votre inscription a ete prise en compte') . "</div>\n";

    if ($auto != '')
    {
      $body .= "<br/>\n<div>" . _('login::register: vous avez des a present acces aux collections suivantes : ') . "</div>\n<ul>" . $auto . "</ul>\n";
    }

    if ($others != '')
    {
      $body .= "<br/>\n<div>" . _('login::register: vos demandes concernat les collections suivantes sont sujettes a approbation d\'un administrateur') . "</div>\n<ul>" . $others . "</ul>\n";
      $body .= "<br/>\n<div>" . _('login::register : vous serez avertis par email lorsque vos demandes seront traitees') . "</div>\n";
    }

    $to = array('email' => $email, 'name' => $email);

    return self::send_mail($subject, $body, $to);
  }

  public static function reset_email($email, $usr_id)
  {
    $date = new DateTime('1 day');
    $date = phraseadate::format_mysql($date);
    $token = random::getUrlToken('email', $usr_id, $date, $email);

    $url = GV_ServerName . 'login/reset-email.php?token=' . $token;

    $subject = _('login::register: sujet email : confirmation de votre adresse email');

    $body = "<div>" . _('admin::compte-utilisateur: email changement de mot d\'email Bonjour, nous avons bien recu votre demande de changement d\'adresse e-mail. Pour la confirmer, veuillez suivre le lien qui suit. SI vous recevez ce mail sans l\'avoir sollicite, merci de le detruire et de l\'ignorer.') . "</div>\n";
    $body .= '<div><a href="' . $url . '">' . $url . '</a></div>\n';

    $to = array('email' => $email, 'name' => $email);

    return self::send_mail($subject, $body, $to);
  }

  public static function mail_confirm_registered($email)
  {
    $subject = _('login::register: sujet email : confirmation de votre adresse email');

    $body = "<div>" . _('login::register: merci d\'avoir confirme votre adresse email') . "</div>\n";
    $body .= "<br/><div>" . _('login::register: vous pouvez maintenant vous connecter a l\'adresse suivante : ') . "</div>\n";
    $body .= "<div><a href='" . GV_ServerName . "' target='_blank'>" . GV_ServerName . "</a></div>\n";

    $to = array('email' => $email, 'name' => $email);

    return self::send_mail($subject, $body, $to);
  }

  public static function mail_confirm_unregistered($email, $others)
  {

    $subject = _('login::register: sujet email : confirmation de votre adresse email');

    $body = "<div>" . _('login::register: merci d\'avoir confirme votre adresse email') . "</div>\n";
    $body .= "<br/>\n<div>" . _('login::register: vous devez attendre la confirmation d\'un administrateur ; vos demandes sur les collections suivantes sont toujours en attente : ') . "</div>\n<ul>" . $others . "</ul>\n";
    $body .= "<br/>\n<div>" . _('login::register : vous serez avertis par email lorsque vos demandes seront traitees') . "</div>\n";

    $to = array('email' => $email, 'name' => $email);

    return self::send_mail($subject, $body, $to);
  }

  public static function mail_confirmation($email, $usr_id)
  {

    $expire = new DateTime('+3 days');
    $expire = phraseadate::format_mysql($expire);
    $token = random::getUrlToken('password', $usr_id, $expire, $email);

    $subject = _('login::register: sujet email : confirmation de votre adresse email');

    $body = "<div>" . _('login::register: email confirmation email Pour valider votre inscription a la base de donnees, merci de confirmer votre e-mail en suivant le lien ci-dessous.') . "</div>\n";
    $body .= "<br/>\n<div><a href='" . GV_ServerName . "register-confirm=" . $token . "' target='_blank'>" . GV_ServerName . "register-confirm=" . $token . "</a></div>\n";

    $to = array('email' => $email, 'name' => $email);

    return self::send_mail($subject, $body, $to);
  }

  public static function send_mail($subject, $body, $to, $from=false, $files=array(), $reading_confirm_to=false)
  {
    require_once(GV_RootPath . 'lib/PHPMailer_v5.1/class.phpmailer.php');

    if (!isset($to['email']) || !PHPMailer::ValidateAddress($to['email']))
      return false;

    $mail = new PHPMailer();

    $body = eregi_replace("[\]", '', $body);

    $body .= "<br/><br/><br/><br/>\n\n\n\n";
    $body .= '<div style="font-style:italic;">' . _('phraseanet::signature automatique des notifications par mail, infos a l\'url suivante') . "</div>\n";
    $body .= '<div><a href="' . GV_ServerName . '">' . GV_ServerName . "</a></div>\n";
    $body = '<body>' . $body . '</body>';

    try
    {
      $mail->CharSet = 'utf-8';
      $mail->Encoding = 'base64'; //'quoted-printable';

      if (GV_smtp)
      {
        $mail->IsSMTP();
        if (GV_smtp_host != '')
          $mail->Host = GV_smtp_host;
//				$mail->SMTPDebug  = 2;                     // enables SMTP debug information (for testing)
        if (GV_smtp_auth)
        {
          $mail->SMTPAuth = true;

          if (GV_smtp_secure === true)
          {
            $mail->SMTPSecure = "ssl";
          }
          $mail->Host = GV_smtp_host;
          $mail->Port = GV_smtp_port;
          $mail->Username = GV_smtp_user;
          $mail->Password = GV_smtp_password;
        }
      }

      if ($from && trim($from['email']) != '')
        $mail->AddReplyTo($from['email'], $from['name']);

      $mail->AddAddress($to['email'], $to['name']);

      $mail->SetFrom(GV_defaulmailsenderaddr, GV_homeTitle);

      $mail->Subject = $subject;

      $mail->AltBody = html_entity_decode(strip_tags($body), ENT_QUOTES, 'UTF-8');

      if ($reading_confirm_to)
      {
        $mail->ConfirmReadingTo = $reading_confirm_to;
      }

      $mail->MsgHTML(p4string::cleanTags($body));

      foreach ($files as $f)
      {
        $mail->AddAttachment($f);      // attachment
      }

      $mail->Send();
      return true;
    }
    catch (phpmailerException $e)
    {
      return $e->errorMessage();
    }
    catch (Exception $e)
    {
      return $e->getMessage();
    }
  }

}