<?php

require_once dirname(__FILE__) . "/../../lib/bootstrap.php";

$session = session::getInstance();
if (GV_captchas && trim(GV_captcha_private_key) !== '' && trim(GV_captcha_public_key) !== '')
  include(GV_RootPath . 'lib/recaptcha/recaptchalib.php');

skins::merge();

$request = httpRequest::getInstance();
$parm = $request->get_parms('login', 'pwd', 'nolog', 'app', 'remember', 'recaptcha_response_field', 'recaptcha_challenge_field');


$avApps = array('client', 'admin', 'prod', 'upload', 'thesaurus', 'report', 'lightbox');

$app = 'prod';
if (in_array($parm['app'], $avApps))
  $app = $parm['app'];

$lng = isset($session->locale) ? $session->locale : GV_default_lng;

$conn = connection::getInstance();

if (!$conn)
{
  header("Location: /login/?error=base");
  exit();
}

$is_guest = false;

if (!is_null($parm['nolog']) && phrasea::guest_allowed())
{
  $is_guest = true;
}

if ((!is_null($parm['login']) && !is_null($parm['pwd'])) || $is_guest)
{
  if (file_exists(GV_RootPath . 'config/personnalisation/prelog.class.php'))
  {
    include(GV_RootPath . 'config/personnalisation/prelog.class.php');
    $prelog = new prelog($parm['login'], $parm['pwd']);
  }

  if ($is_guest)
  {
    $logged = p4::signOnasGuest(); //p4::signOn($parm['login'],$parm['pwd'],$captcha);
  }
  else
  {
    $oldPwd = $parm['pwd'];
    $oldLogin = $parm['login'];

    $captcha = false;

    if (GV_captchas && trim(GV_captcha_private_key) !== '' && trim(GV_captcha_public_key) !== '' && !is_null($parm["recaptcha_challenge_field"]) && !is_null($parm["recaptcha_response_field"]))
    {
      $checkCaptcha = recaptcha_check_answer(GV_captcha_private_key, $_SERVER["REMOTE_ADDR"], $parm["recaptcha_challenge_field"], $parm["recaptcha_response_field"]);

      if ($checkCaptcha->is_valid)
      {
        $captcha = true;
      }
    }

    $logged = p4::signOn($parm['login'], $parm['pwd'], $captcha);
  }

  if ($logged['error'])
  {
    switch ($logged['error'])
    {
      case 'captcha':
        header("Location: /login/?app=" . $app . "&error=captcha");
        exit();
        break;
      case 'session':
        header("Location: /login/?app=" . $app . "&error=session");
        exit();
        break;
      case 'bad':
        header("Location: /login/?app=" . $app . "&error=auth");
        exit();
        break;
      case 'mail_lock':
        header("Location: /login/?app=" . $app . "&error=mailNotConfirm&usr=" . $logged['usr_id']);
        exit();
        break;
    }
  }

  $authenticated = (is_numeric($session->usr_id) && is_numeric($session->ses_id)) ? true : false;

  if ($authenticated)// && $readyToWork)
  {
    $browser = browser::getInstance();
    if (!$browser->isNewGeneration())
      $app = 'client';

    $expire = -60 * 24 * 3600;
    if (isset($parm['remember']) && $parm['remember'])
    {
      $expire = 60 * 24 * 3600;
    }
  }

  $browser = browser::getInstance();
  if ($browser->isMobile())
  {
    header("Location: /lightbox/");
    exit();
  }
  else
  {
    header("Location: /" . $app . "/");
    exit();
  }
}
else
{
  header("Location: /login/");
  exit();
}
?>
