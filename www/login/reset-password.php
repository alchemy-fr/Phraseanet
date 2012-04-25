<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package
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

$parm = $request->get_parms('form_old_password', 'form_password', 'form_password_confirm');


$gatekeeper = gatekeeper::getInstance($Core);
$gatekeeper->require_session();
$user = User_Adapter::getInstance($session->get_usr_id(), $appbox);

if ($user->is_guest()) {
    phrasea::headers(403);
}

$needed = array();
$updated = $error = false;

if ( ! is_null($parm['form_old_password']) && ! is_null($parm['form_password']) && ! is_null($parm['form_password_confirm'])) {
    $user = User_Adapter::getInstance($session->get_usr_id(), $appbox);


    // 1 - on verifie les password
    if ($parm['form_password'] !== $parm['form_password_confirm'])
        $needed['form_password'] = $needed['form_password_confirm'] = _('forms::les mots de passe ne correspondent pas');
    elseif (strlen(trim($parm['form_password'])) < 5)
        $needed['form_password'] = _('forms::la valeur donnee est trop courte');
    elseif (trim($parm['form_password']) != str_replace(array("\r\n", "\n", "\r", "\t", " "), "_", $parm['form_password']))
        $needed['form_password'] = _('forms::la valeur donnee contient des caracteres invalides');

    if (count($needed) == 0) {
        try {
            $auth = new Session_Authentication_Native($appbox, $user->get_login(), $parm['form_old_password']);
            $auth->challenge_password();
            $user->set_password($parm['form_password_confirm']);

            return phrasea::redirect('/login/account.php?notice=password-update-ok');
        } catch (Exception $e) {
            $error = true;
            $needed['form_old_password'] = _('admin::compte-utilisateur:ftp: Le mot de passe est errone');
        }
    }
}

$user = User_Adapter::getInstance($session->get_usr_id(), $appbox);

phrasea::headers();
?>



<head>
    <title></title>
    <link REL="stylesheet" TYPE="text/css" HREF="/login/home.css" />
    <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
    <script type="text/javascript" language="javascript" src="/include/minify/f=include/jslibs/jquery-1.7.1.js,include/jslibs/jquery.validate.js,include/jslibs/jquery.validate.password.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {

            $.validator.passwordRating.messages = {
                "similar-to-username": "<?php echo _('forms::le mot de passe est trop similaire a l\'identifiant') ?>",
                "too-short": "<?php echo _('forms::la valeur donnee est trop courte') ?>",
                "very-weak": "<?php echo _('forms::le mot de passe est trop simple') ?>",
                "weak": "<?php echo _('forms::le mot de passe est simple') ?>",
                "good": "<?php echo _('forms::le mot de passe est bon') ?>",
                "strong": "<?php echo _('forms::le mot de passe est tres bon') ?>"
            }

            $("#mainform").validate(
            {
                rules: {
                    form_old_password : {
                        required:true
                    },
                    form_password : {
                        password:'#form_login'
                    },
                    form_password_confirm : {
                        required:true,
                        equalTo:'#form_password'
                    }
                },
                messages: {
                    form_old_password : {
                        required :  "<?php echo str_replace('"', '\"', _('forms::ce champ est requis')) ?>"
                    },
                    form_password : {
                        required :  "<?php echo str_replace('"', '\"', _('forms::ce champ est requis')) ?>"
                    },
                    form_password_confirm : {
                        required :  "<?php echo str_replace('"', '\"', _('forms::ce champ est requis')) ?>",
                        equalTo :  "<?php echo str_replace('"', '\"', _('forms::les mots de passe ne correspondent pas')) ?>"
                    }

                },
                errorPlacement: function(error, element) {
                    error.prependTo( element.parent().next() );
                }
            }
        );
            $("#form_password").valid();


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
            <div id="id-main" class="tab-content" style="height:auto;display:block;text-align:center;overflow-y:auto;overflow-x:hidden;">
<?php
if ($error) {
    ?>
                    <div class="notice" style="text-align:center;margin:20px 0"><?php echo _('phraseanet::erreur : oups ! une erreur est survenue pendant l\'operation !') ?></div>
    <?php
}
?>
                <form method="post" action="/login/reset-password.php" id="mainform">
                    <table style="margin:50px  auto 0;">
                        <tr>
                            <td class="form_label"><label for="form_login"><?php echo _('admin::compte-utilisateur identifiant') ?></label></td>
                            <td class="form_input"><?php echo $user->get_login() ?></td>
                            <td class="form_alert"></td>
                        </tr>
                        <tr>
                            <td class="form_label"><label for="form_old_password"><?php echo _('admin::compte-utilisateur ancien mot de passe') ?></label></td>
                            <td class="form_input"><input autocomplete="off" type="password" name="form_old_password" id="form_old_password"/></td>
                            <td class="form_alert"><?php echo isset($needed['form_old_password']) ? $needed['form_old_password'] : '' ?></td>
                        </tr>
                        <tr>
                            <td colspan="3"></td>
                        </tr>
                        <tr>
                            <td class="form_label">
                                <label for="form_password"><?php echo _('admin::compte-utilisateur nouveau mot de passe') ?></label>
                                <br/><label style="font-size:9px;"><?php echo _('8 caracteres minimum') ?></label>
                            </td>
                            <td class="form_input"><input autocomplete="off" type="hidden" value="<?php echo $user->get_login() ?>" id="form_login"/><input type="password" name="form_password" id="form_password"></td>
                            <td class="form_alert"><?php echo isset($needed['form_password']) ? $needed['form_password'] : '' ?>
                                <div class="password-meter">
                                    <div class="password-meter-message">&nbsp;</div>
                                    <div class="password-meter-bg">
                                        <div class="password-meter-bar"></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="form_label"><label for="form_password_confirm"><?php echo _('admin::compte-utilisateur confirmer le mot de passe') ?></label></td>
                            <td class="form_input"><input autocomplete="off" type="password" name="form_password_confirm" id="form_password_confirm"/></td>
                            <td class="form_alert"><?php echo isset($needed['form_password_confirm']) ? $needed['form_password_confirm'] : '' ?></td>
                        </tr>
                    </table>
                    <input type="submit" value="<?php echo _('boutton::valider'); ?>" style="margin:20px auto;">
                    <input type="button" value="<?php echo _('boutton::annuler'); ?>" onclick="self.location.replace('account.php');">
                </form>
                <div>
<?php
echo '<div style="text-align:center;font-weight:bold;font-size:13px;margin:60px 0 0;">' . _('admin::compte-utilisateur A propos de la securite des mots de passe :') . '</div>';
echo '<div style="text-align:center;margin:20px 0 0;">' . _('admin::compte-utilisateur Les mots de passe doivent etre clairement distincts du login et contenir au moins deux types parmis les caracteres suivants :') . '</div>';
echo '<div style="text-align:left;margin:10px auto;width:300px;"><ul>';
echo '<li>' . _('admin::compte-utilisateur::securite caracteres speciaux') . '</li>';
echo '<li>' . _('admin::compte-utilisateur::securite caracteres majuscules') . '</li>';
echo '<li>' . _('admin::compte-utilisateur::securite caracteres minuscules') . '</li>';
echo '<li>' . _('admin::compte-utilisateur::securite caracteres numeriques') . '</li>';
echo '</ul></div>';
?>
                </div>
            </div>
        </div>
    </div>
</body>


