<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
/* @var $Core \Alchemy\Phrasea\Core */
$Core = require_once __DIR__ . "/../../lib/bootstrap.php";
$appbox = appbox::get_instance($Core);
$session = $appbox->get_session();
$registry = $appbox->get_registry();

require_once($registry->get('GV_RootPath') . 'lib/classes/deprecated/inscript.api.php');
if ($registry->get('GV_captchas') && trim($registry->get('GV_captcha_private_key')) !== '' && trim($registry->get('GV_captcha_public_key')) !== '')
    include($registry->get('GV_RootPath') . 'lib/vendor/recaptcha/recaptchalib.php');

$request = http_request::getInstance();
$parm = $request->get_parms('lng', 'error', 'confirm', 'badlog', 'postlog', 'usr', 'redirect', 'logged_out');

if ($parm['postlog']) {
    $session->set_postlog(true);

    return phrasea::redirect("/login/index.php?redirect=" . $parm['redirect']);
}

if ( ! $session->isset_postlog() && $session->is_authenticated() && $parm['error'] != 'no-connection') {
    $parm['redirect'] = trim($parm['redirect']) == '' ? '/prod' : $parm['redirect'];

    return phrasea::redirect($parm['redirect']);
}

try {
    $conn = $appbox->get_connection();
} catch (Exception $e) {
    $parm['error'] = 'no-connection';
}

phrasea::headers();


$client = Browser::getInstance();

$errorWarning = $confirmWarning = '';

if ($registry->get('GV_maintenance'))
    $parm['error'] = 'maintenance';

if ($parm['error'] !== null) {
    switch ($parm['error']) {
        case 'auth':
            $errorWarning = '<div class="notice">' . _('login::erreur: Erreur d\'authentification') . '</div>';
            break;
        case 'captcha':
            $errorWarning = '<div class="notice">' . _('login::erreur: Erreur de captcha') . '</div>';
            break;
        case 'mailNotConfirm' :
            $errorWarning = '<div class="notice">' . _('login::erreur: Vous n\'avez pas confirme votre email') . '</div>';
            if (ctype_digit($parm['usr']))
                $errorWarning .= '<div class="notice"><a href="/login/sendmail-confirm.php?usr_id=' . $parm['usr'] . '" target ="_self" style="color:black;text-decoration:none;">' . _('login:: Envoyer a nouveau le mail de confirmation') . '</a></div>';
            break;
        case 'no-base' :
            $errorWarning = '<div class="notice">' . _('login::erreur: Aucune base n\'est actuellment accessible') . '</div>';
            break;
        case 'no-connection':
            $errorWarning = '<div class="notice">' . _('login::erreur: No available connection - Please contact sys-admin') . '</div>';
            break;
        case 'maintenance':
            $errorWarning = '<div class="notice">' . _('login::erreur: maintenance en cours, merci de nous excuser pour la gene occasionee') . '</div>';
            break;
    }
}
if ($parm['confirm'] !== null) {
    switch ($parm['confirm']) {
        case 'ok':
            $confirmWarning = '<div class="notice">' . _('login::register: sujet email : confirmation de votre adresse email') . '</div>';
            break;
        case 'already':
            $confirmWarning = '<div class="notice">' . _('login::notification: cette email est deja confirmee') . '</div>';
            break;
        case 'mail-sent':
            $confirmWarning = '<div class="notice">' . _('login::notification: demande de confirmation par mail envoyee') . '</div>';
            break;
        case 'register-ok':
            $confirmWarning = '<div class="notice">' . _('login::notification: votre email est desormais confirme') . '</div>';
            break;
        case 'register-ok-wait':
            $confirmWarning = '<div class="notice">' . _('login::notification: votre email est desormais confirme') . '</div>';
            $confirmWarning .= '<div class="notice">' . _('login::register : vous serez avertis par email lorsque vos demandes seront traitees') . '</div>';
            break;
        case 'password-update-ok':
            $confirmWarning = '<div class="notice">' . _('login::notification: Mise a jour du mot de passe avec succes') . '</div>';
            break;
    }
}
$captchaSys = '';
if ( ! $registry->get('GV_maintenance')
    && $registry->get('GV_captchas')
    && trim($registry->get('GV_captcha_private_key')) !== ''
    && trim($registry->get('GV_captcha_public_key')) !== ''
    && $parm['error'] == 'captcha') {
    $captchaSys = '<div style="margin:0;float: left;width:330px;"><div id="recaptcha_image" style="float: left;margin:10px 15px 5px"></div>
                                                        <div style="text-align:center;float: left;margin:0 15px 5px;width:300px;">
                                                        <a href="javascript:Recaptcha.reload()" class="link">' . _('login::captcha: obtenir une autre captcha') . '</a>
                                                        </div>
                                                        <div style="text-align:center;float: left;width:300px;margin:0 15px 0px;">
                                                            <span class="recaptcha_only_if_image">' . _('login::captcha: recopier les mots ci dessous') . ' : </span>
                                                            <input name="recaptcha_response_field" id="recaptcha_response_field" value="" type="text" style="width:180px;"/>
                                                        </div>' . recaptcha_get_html($registry->get('GV_captcha_public_key')) . '</div>';
}

$public_feeds = Feed_Collection::load_public_feeds($appbox);
$feeds = array_merge(array($public_feeds->get_aggregate()), $public_feeds->get_feeds());

//$twig = new supertwig(array('Escaper' => false));
$core = \bootstrap::getCore();
$twig = $core->getTwig();

echo $twig->render('login/index.twig', array(
    'module_name'    => _('Accueil'),
    'confirmWarning' => $confirmWarning,
    'errorWarning'   => $errorWarning,
    'redirect'       => $parm['redirect'],
    'logged_out'     => $parm['logged_out'],
    'captcha_system' => $captchaSys,
    'login'          => new login(),
    'feeds'          => $feeds,
    'sso'            => new sso(),
    'display_layout' => $registry->get('GV_home_publi')
));
