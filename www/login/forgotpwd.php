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

$request = http_request::getInstance();
$parm = $request->get_parms('salt', 'error', 'sent', 'token', 'form_password', 'form_password_confirm', 'mail');

$needed = array();

if (isset($parm["mail"]) && trim($parm["mail"]) != "") {
    if ( ! PHPMailer::ValidateAddress($parm['mail'])) {
        return phrasea::redirect('/login/forgotpwd.php?error=noaccount');
    }

    try {
        $usr_id = User_Adapter::get_usr_id_from_email($parm['mail']);
        $user = User_Adapter::getInstance($usr_id, $appbox);
    } catch (Exception $e) {
        return phrasea::redirect('/login/forgotpwd.php?error=noaccount');
    }

    $date = new DateTime('1 day');
    $url = random::getUrlToken(\random::TYPE_PASSWORD, $user->get_id(), $date);

    if ($url !== false) {
        $url = $registry->get('GV_ServerName') . 'login/forgotpwd.php?token=' . $url;
        if (mail::forgot_passord($parm['mail'], $user->get_login(), $url) === true) {
            return phrasea::redirect('/login/forgotpwd.php?sent=ok');
        } else {
            return phrasea::redirect('/login/forgotpwd.php?error=mailserver');
        }
    }

    return phrasea::redirect('/login/forgotpwd.php?error=noaccount');
}
if (isset($parm['token']) && isset($parm['form_password']) && isset($parm['form_password_confirm'])) {
    if ($parm['form_password'] !== $parm['form_password_confirm'])
        $needed['form_password'] = $needed['form_password_confirm'] = _('forms::les mots de passe ne correspondent pas');
    elseif (strlen(trim($parm['form_password'])) < 5)
        $needed['form_password'] = _('forms::la valeur donnee est trop courte');
    elseif (trim($parm['form_password']) != str_replace(array("\r\n", "\n", "\r", "\t", " "), "_", $parm['form_password']))
        $needed['form_password'] = _('forms::la valeur donnee contient des caracteres invalides');

    if (count($needed) == 0) {

        try {
            $datas = random::helloToken($parm['token']);
            $user = User_Adapter::getInstance($datas['usr_id'], $appbox);
            $user->set_password($parm['form_password_confirm']);
            random::removeToken($parm['token']);

            return phrasea::redirect('/login/index.php?confirm=password-update-ok');
        } catch (Exception_NotFound $e) {

        }
    }
}

phrasea::headers();
?>
<html lang="<?php echo $session->get_I18n(); ?>">
    <head>
        <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
        <link type="text/css" rel="stylesheet" href="/login/home.css" />
        <title><?php echo _('admin::compte-utilisateur changer mon mot de passe'); ?></title>
    </head>
    <body >
        <div style="width:950px;margin:0 auto;">
            <div style="margin-top:70px;height:35px;">
                <table style="width:100%;">
                    <tr style="height:35px;">
                        <td style="width:auto;"><div style="font-size:28px;color:#b1b1b1;"><?php echo $registry->get('GV_homeTitle') ?></div></td>
                        <td style="color:#b1b1b1;text-align:right;">
                        </td>
                    </tr>
                </table>
            </div>
            <div style="height:530px;background-color:#525252;">
                <div id="id-main" class="tab-content" style="display:block;">
                    <!--<div style="width:560px;float:left;height:490px;">
                      <img src="/skins/icons/home.jpg" style="margin: 85px 10px; width: 540px;"/>
                              </div>-->
                    <div xstyle="width:360px;float:right;height:490px;">
                        <div style="margin:40px 25px;float:left;width:880px;">
<?php
$tokenize = false;
if ($parm['token'] !== null) {
    try {
        random::helloToken($parm['token']);
        $tokenize = true;
        ?>
                                    <script type="text/javascript" language="javascript" src="/include/minify/f=include/jslibs/jquery-1.7.1.js"></script>
                                    <script type="text/javascript" language="javascript" src="/include/minify/f=include/jslibs/jquery.validate.js"></script>
                                    <script type="text/javascript" language="javascript" src="/include/minify/f=include/jslibs/jquery.validate.password.js"></script>

                                    <script type="text/javascript" >
        <?php
        $rules = 'form_password_confirm:{required:true}';
        $msg = '
              form_password_confirm : {equalTo:"' . _('forms::les mots de passe ne correspondent pas') . '"}';
        ?>
                          $(document).ready(function() {

                              $.validator.passwordRating.messages = {
                                  "similar-to-username": "<?php echo _('forms::le mot de passe est trop similaire a l\'identifiant'); ?>",
                                  "too-short": "<?php echo _('forms::la valeur donnee est trop courte') ?>",
                                  "very-weak": "<?php echo _('forms::le mot de passe est trop simple') ?>",
                                  "weak": "<?php echo _('forms::le mot de passe est trop simple') ?>",
                                  "good": "<?php echo _('forms::le mot de passe est bon') ?>",
                                  "strong": "<?php echo _('forms::le mot de passe est tres bon') ?>"
                              }

                              $("#password-reset").validate(
                              {
                                  rules: {
        <?php echo $rules ?>
                                  },
                                  messages: {
        <?php echo $msg ?>
                                  },
                                  errorPlacement: function(error, element) {
                                      error.prependTo( element.parent().parent().next().find('.form_alert') );
                                  }
                              }
                          );

                              $('#form_password').rules("add",{password: "#form_login"});
                              $('#form_password_confirm').rules("add",{equalTo: "#form_password"});
                              $("#form_password").valid();

                          });
                                    </script>

        <?php
        if ($parm['salt']) {
            ?>
                                        <div class="notice" style="text-align:center;margin:20px 40px;padding:10px;font-weight:bold;font-size:14px;">
            <?php echo _('Pour ameliorer la securite de l\'application, vous devez mettre a jour votre mot de passe.'); ?><br/>
            <?php echo _('Cette tache ne pouvant etre automatisee, merci de bien vouloir la realiser.'); ?>
                                        </div>
            <?php
        }
        ?>
                                    <form name="send" action="forgotpwd.php" method="post" id="password-reset" style="width:600px;margin:0 auto;">
                                        <table cellspacing="0" cellpadding="0" border="0">
                                            <tr style="height:30px;">
                                                <td style="width:33%;"><label for="form_password"><?php echo _('admin::compte-utilisateur nouveau mot de passe') ?> :</label></td>
                                                <td style="width:33%;">
                                                    <div class="form_input">
                                                        <input autocomplete="off" type="password" value="" id="form_password" name="form_password"/>
                                                    </div>
                                                </td>
                                                <td style="width:33%;">
                                                    <div class="form_alert">
                                        <?php echo isset($needed['form_password']) ? $needed['form_password'] : ''; ?>
                                                        <div class="password-meter">
                                                            <div class="password-meter-message">&nbsp;</div>
                                                            <div class="password-meter-bg">
                                                                <div class="password-meter-bar"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr style="height:30px;">
                                                <td>
                                                    <label for="form_password" ><?php echo _('admin::compte-utilisateur confirmer le mot de passe') ?> :</label></td>
                                                <td>
                                                    <div class="form_input">
                                                        <input autocomplete="off" type="password" value="" id="form_password_confirm" name="form_password_confirm"/>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="form_alert">
        <?php echo isset($needed['form_password_confirm']) ? $needed['form_password_confirm'] : ''; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr style="height:30px;">
                                                <td></td>
                                                <td>
                                                    <input type="hidden" value="<?php echo $parm['token']; ?>" name="token"/>
                                                    <input type="submit" value="valider"/>
                                                </td>
                                                <td>
                                                    <a class="link" href="index.php" target="_self"><?php echo _('login:: Retour a l\'accueil'); ?></a>
                                                </td>
                                            </tr>
                                        </table>
                                    </form>

                                                        <?php
                                                    } catch (Exception_NotFound $e) {

                                                    }
                                                    if ( ! $tokenize) {
                                                        $parm['error'] = 'token';
                                                    }
                                                }

                                                if ( ! $tokenize) {
                                                    echo '<form name="send" action="forgotpwd.php" method="post" style="width:600px;margin:0 auto;">';

                                                    if ($parm['error'] !== null) {
                                                        switch ($parm['error']) {
                                                            case 'mailserver':
                                                                echo '<div style="background:#00a8FF;">' . _('phraseanet::erreur: Echec du serveur mail') . '</div>';
                                                                break;
                                                            case 'noaccount':
                                                                echo '<div style="background:#00a8FF;">' . _('phraseanet::erreur: Le compte n\'a pas ete trouve') . '</div>';
                                                                break;
                                                            case 'mail':
                                                                echo '<div style="background:#00a8FF;">' . _('phraseanet::erreur: Echec du serveur mail') . '</div>';
                                                                break;
                                                            case 'token':
                                                                echo '<div style="background:#00a8FF;">' . _('phraseanet::erreur: l\'url n\'est plus valide') . '</div>';
                                                                break;
                                                        }
                                                    }
                                                    if ($parm['sent'] !== null) {
                                                        switch ($parm['sent']) {
                                                            case 'ok':
                                                                echo '<div style="background:#00a8FF;">' . _('phraseanet:: Un email vient de vous etre envoye') . '</div>';
                                                                break;
                                                        }
                                                    }
                                                    ?>

                                <div style="margin-top:20px;font-size:16px;font-weight:bold;">
                                <?php echo _('login:: Forgot your password') ?>
                                </div>
                                <div style="margin-top:20px;">
                                <?php echo _('login:: Entrez votre adresse email') ?>
                                </div>
                                <div style="margin-top:20px;">
                                    <input name="mail" type="text" style="width:100%">
                                </div>
                                <div style="margin-top:10px;">
                                    <input type="submit" value="<?php echo _('boutton::valider'); ?>"/>
                                    <a style="margin-left:120px;" class="link" href="index.php" target="_self"><?php echo _('login:: Retour a l\'accueil'); ?></a>
                                </div>
                                </form>
                                <?php
                            }
                            ?>
                        </div>

                    </div>
                </div>
                <div style="text-align:right;position:relative;margin:18px 10px 0 0;font-size:10px;font-weight:normal;"><span>&copy; Copyright Alchemy 2005-<?php echo date('Y') ?></span></div>
            </div>
        </div>
    </body>
</html>

