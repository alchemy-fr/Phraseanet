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

$request = http_request::getInstance();
$parm = $request->get_parms('token');

$updated = $error = false;
if ( ! is_null($parm['token'])) {
    try {
        $datas = random::helloToken($parm['token']);

        $new_mail = $datas['datas'];
        $usr_id = $datas['usr_id'];

        $user = User_Adapter::getInstance($usr_id, $appbox);
        $old_email = $user->get_email();

        $user->set_email($new_mail);

        if ($old_email != $new_mail) {
            \mail::change_mail_information($user->get_display_name(), $old_email, $new_mail);
        }

        random::removeToken($parm['token']);

        phrasea::headers();
        ?>
        <html lang="<?php echo $session->get_I18n(); ?>">
            <head><title><?php echo $registry->get('GV_homeTitle') ?> - <?php echo _('admin::compte-utilisateur changer mon mot de passe') ?></title>

                <link REL="stylesheet" TYPE="text/css" HREF="/login/home.css" />
                <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
                <script type="text/javascript" language="javascript" src="/include/minify/f=include/jslibs/jquery-1.7.1.js,include/jslibs/jquery.validate.js"></script>
            </head>
            <body>
                <div style="width:950px;margin-left:auto;margin-right:auto;">
                    <div style="margin-top:70px;height:35px;">
                        <table style="width:100%;">
                            <tr style="height:35px;">
                                <td><span class="title-name"><?php echo $registry->get('GV_homeTitle') ?></span><span class="title-desc"><?php echo _('admin::compte-utilisateur changer mon mot de passe') ?></span></td>
                            </tr>
                        </table>
                    </div>
                    <div style="height:530px;" class="tab-pane">
                        <div id="id-main" class="tab-content" style="display:block;text-align:center;overflow-y:auto;overflow-x:hidden;">

                            <div style="margin-top:100px;"><?php echo _('admin::compte-utilisateur: L\'email a correctement ete mis a jour') ?></div>
                            <a href="/" target="_self"><?php echo _('accueil:: retour a l\'accueil'); ?></a>
                        </div>
                    </div>
                </div>
            </body>
        </html>
        <?php

        return;
    } catch (Exception $e) {
        ?>
        <html lang="<?php echo $session->get_I18n(); ?>">
            <head><title><?php echo $registry->get('GV_homeTitle') ?> - <?php echo _('admin::compte-utilisateur changer mon mot de passe') ?></title>

                <link REL="stylesheet" TYPE="text/css" HREF="/login/home.css" />
                <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
                <script type="text/javascript" language="javascript" src="/include/minify/f=include/jslibs/jquery-1.7.1.js,include/jslibs/jquery.validate.js"></script>
            </head>
            <body>
                <div style="width:950px;margin-left:auto;margin-right:auto;">
                    <div style="margin-top:70px;height:35px;">
                        <table style="width:100%;">
                            <tr style="height:35px;">
                                <td><span class="title-name"><?php echo $registry->get('GV_homeTitle') ?></span><span class="title-desc"><?php echo _('admin::compte-utilisateur changer mon mot de passe') ?></span></td>
                            </tr>
                        </table>
                    </div>
                    <div style="height:530px;" class="tab-pane">
                        <div id="id-main" class="tab-content" style="display:block;text-align:center;overflow-y:auto;overflow-x:hidden;">

                            <div style="margin-top:100px;"><?php echo _('admin::compte-utilisateur: erreur lors de la mise a jour') . $e->getMessage() ?></div>
                        </div>
                    </div>
                </div>
            </body>
        </html>
        <?php

        return;
    }
}


$gatekeeper = gatekeeper::getInstance($Core);
$gatekeeper->require_session();

$usr_id = $session->get_usr_id();
$user = User_Adapter::getInstance($usr_id, $appbox);

if ($user->is_guest()) {
    return;
}

$user = User_Adapter::getInstance($session->get_usr_id(), $appbox);

$parm = $request->get_parms('form_password', 'form_email', 'form_email_confirm');

if (isset($parm['form_password']) && isset($parm['form_email']) && isset($parm['form_email_confirm'])) {
    $nonce = $user->get_nonce();
    $login = $user->get_login();

    try {
        $auth = new Session_Authentication_Native($appbox, $login, $parm["form_password"]);
        $auth->challenge_password();

        if (str_replace(array("\r\n", "\r", "\n", "\t"), '_', trim($parm['form_email'])) == $parm['form_email_confirm']) {
            if (PHPMailer::ValidateAddress($parm['form_email'])) {
                if (mail::reset_email($parm['form_email'], $session->get_usr_id()) === true)
                    $updated = true;
                else
                    $error = _('phraseanet::erreur: echec du serveur de mail');
            }
            else
                $error = _('forms::l\'email semble invalide');
        }
        else
            $error = _('forms::les emails ne correspondent pas');
    } catch (Exception $e) {
        $error = _('admin::compte-utilisateur:ftp: Le mot de passe est errone');
    }
}
phrasea::headers();
?>
<head>
    <title><?php echo $registry->get('GV_homeTitle') ?> - <?php echo _('admin::compte-utilisateur changer mon mot de passe') ?></title>
    <link REL="stylesheet" TYPE="text/css" HREF="/login/home.css" />
    <link rel="icon" type="image/png" href="/favicon.ico" />
    <script type="text/javascript" language="javascript" src="/include/minify/f=include/jslibs/jquery-1.7.1.js,include/jslibs/jquery.validate.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            $("#mainform").validate(
            {
                rules: {
                    form_password : {
                        required:true
                    },
                    form_email : {
                        required:true,
                        email:true
                    },
                    form_email_confirm : {
                        required:true,
                        equalTo:'#form_email'
                    }
                },
                messages: {
                    form_password : {
                        required :  "<?php echo str_replace('"', '\"', _('forms::ce champ est requis')) ?>"
                    },
                    form_email : {
                        required :  "<?php echo str_replace('"', '\"', _('forms::ce champ est requis')) ?>",
                        email:"<?php echo str_replace('"', '\"', _('forms::l\'email semble invalide')) ?>"
                    },
                    form_email_confirm : {
                        required :  "<?php echo str_replace('"', '\"', _('forms::ce champ est requis')) ?>",
                        equalTo :  "<?php echo str_replace('"', '\"', _('forms::les emails ne correspondent pas')) ?>"
                    }

                },
                errorPlacement: function(error, element) {
                    error.prependTo( element.parent().next() );
                }
            }
        );
        });
    </script>
</head>

<body>
    <div style="width:950px;margin-left:auto;margin-right:auto;">
        <div style="margin-top:70px;height:35px;">
            <table style="width:100%;">
                <tr style="height:35px;">
                    <td><span class="title-name"><?php echo $registry->get('GV_homeTitle') ?></span><span class="title-desc"><?php echo _('admin::compte-utilisateur changer mon mot de passe') ?></span></td>
                </tr>
            </table>
        </div>
        <div style="height:530px;" class="tab-pane">
            <div id="id-main" class="tab-content" style="display:block;text-align:center;overflow-y:auto;overflow-x:hidden;">
<?php
if ($updated) {
    ?><div style="margin:200px 0 0;"><?php
    echo _('admin::compte-utilisateur un email de confirmation vient de vous etre envoye. Veuillez suivre les instructions contenue pour continuer');
    ?>
                    </div>
                    <div>
                        <a href="/login/account.php" target="_self" class="link"><?php echo _('admin::compte-utilisateur retour a mon compte') ?></a>
                    </div>
    <?php
} else {
    if ($error) {
        ?>
                        <div class="notice" style="text-align:center;margin:20px 0"><?php echo _('phraseanet::erreur : oups ! une erreur est survenue pendant l\'operation !') ?></div>
                        <div class="notice" style="text-align:center;margin:20px 0"><?php echo $error ?></div>
                        <?php
                    }
                    ?>
                    <form method="post" action="/login/reset-email.php" id="mainform">
                        <table style="margin:70px  auto 0;">
                            <tr>
                                <td class="form_label"><label for="form_login"><?php echo _('admin::compte-utilisateur identifiant') ?></label></td>
                                <td class="form_input"><?php echo $user->get_login() ?></td>
                                <td class="form_alert"></td>
                            </tr>
                            <tr>
                                <td class="form_label"><label for="form_password"><?php echo _('admin::compte-utilisateur mot de passe') ?></label></td>
                                <td class="form_input"><input autocomplete="off" type="password" name="form_password" id="form_password"/></td>
                                <td class="form_alert"><?php echo isset($needed['form_password']) ? $needed['form_password'] : '' ?></td>
                            </tr>
                            <tr style="height:10px;">
                                <td colspan="3"></td>
                            </tr>
                            <tr>
                                <td class="form_label"><label for="form_email"><?php echo _('admin::compte-utilisateur nouvelle adresse email') ?></label></td>
                                <td class="form_input"><input type="text" name="form_email" id="form_email"/></td>
                                <td class="form_alert"><?php echo isset($needed['form_email']) ? $needed['form_email'] : '' ?></td>
                            </tr>
                            <tr>
                                <td class="form_label"><label for="form_email_confirm"><?php echo _('admin::compte-utilisateur confirmer la nouvelle adresse email') ?></label></td>
                                <td class="form_input"><input autocomplete="off" type="text" name="form_email_confirm" id="form_email_confirm"/></td>
                                <td class="form_alert"><?php echo isset($needed['form_email_confirm']) ? $needed['form_email_confirm'] : '' ?></td>
                            </tr>
                        </table>
                        <input type="submit" style="margin:20px auto;">
                        <input type="button" value="Cancel" onclick="self.location.replace('account.php');">
                    </form>
                    <div>
                        <div><?php echo _('admin::compte-utilisateur: Pourquoi me demande-t-on mon mot de passe pour changer mon adresse email ?') ?></div>
                        <div><?php echo _('admin::compte-utilisateur: Votre adresse e-mail sera utilisee lors de la perte de votre mot de passe afin de pouvoir le reinitialiser, il est important que vous soyez la seule personne a pouvoir la changer.') ?></div>
                    </div>
    <?php
}
?>
            </div>
        </div>
    </div>
</body>
