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
$Core = require_once __DIR__ . "/../../lib/bootstrap.php";

$appbox = appbox::get_instance();
$session = $appbox->get_session();
$registry = $appbox->get_registry();

require($registry->get('GV_RootPath') . 'lib/classes/deprecated/countries.php');
require_once($registry->get('GV_RootPath') . 'lib/classes/deprecated/inscript.api.php');

$register_enabled = login::register_enabled();

if (!$register_enabled)
{
  return phrasea::redirect('/login/index.php?no-register-available');
}


$request = http_request::getInstance();


$lng = Session_Handler::get_locale();

$needed = array();

/**
 * @todo ameliorer this shit
 */
$arrayVerif = array("form_login" => true,
    "form_password" => true,
    "form_password_confirm" => true,
    "form_gender" => true,
    "form_lastname" => true,
    "form_firstname" => true,
    "form_email" => true,
    "form_job" => true,
    "form_company" => true,
    "form_activity" => true,
    "form_phone" => true,
    "form_fax" => true,
    "form_address" => true,
    "form_zip" => true,
    "form_geonameid" => true,
    "demand" => true);

if (is_file($registry->get('GV_RootPath') . 'config/register-fields.php'))
  include($registry->get('GV_RootPath') . 'config/register-fields.php');

$arrayVerif['form_login'] = true;
$arrayVerif['form_password'] = true;
$arrayVerif['form_password_confirm'] = true;
$arrayVerif['demand'] = true;
$arrayVerif['form_email'] = true;

$lstreceiver = array();

$parm = $request->get_parms("form_login", "form_password", "form_city", "form_password_confirm",
                "form_gender", "form_lastname", "form_firstname", "form_email", "form_job", "form_company", 'demand',
                "form_activity", "form_phone", "form_fax", "form_address", "form_zip", "form_geonameid", "demand");

/**
 * @todo transactionner cette page
 */
if ($request->has_post_datas())
{

  $needed = array_diff_key($arrayVerif, $request->get_post_datas());

  if (sizeof($needed) === 0 || (sizeof($needed) === 1 && isset($needed['form_login']) && $needed['form_login'] === true))
  {

    foreach ($parm as $field => $value)
    {
      if (is_string($value) && isset($arrayVerif[$field]) && $arrayVerif[$field] === true)
      {
        if (trim($value) == '')
          $needed[$field] = _('forms::ce champ est requis');
      }
    }

    // 1 - on verifie les password
    if ($parm['form_password'] !== $parm['form_password_confirm'])
      $needed['form_password'] = $needed['form_password_confirm'] = _('forms::les mots de passe ne correspondent pas');
    elseif (strlen(trim($parm['form_password'])) < 8)
      $needed['form_password'] = _('forms::la valeur donnee est trop courte');
    elseif (trim($parm['form_password']) != str_replace(array("\r\n", "\n", "\r", "\t", " "), "_", $parm['form_password']))
      $needed['form_password'] = _('forms::la valeur donnee contient des caracteres invalides');

    //2 - on verifie que lemail a lair correcte si elle est requise
    require_once(__DIR__ . '/../../lib/vendor/PHPMailer_v5.1/class.phpmailer.php');
    if (trim($parm['form_email']) != '' && !PHPMailer::ValidateAddress($parm['form_email']))
      $needed['form_email'] = _('forms::l\'email semble invalide');

        //on verifie le login
        if(strlen($parm['form_login'])<8)
            $needed['form_login'] = _('forms::la valeur donnee est trop courte');

    if (sizeof($needed) === 1 && isset($needed['form_login']) && $needed['form_login'] === true)
    {
      unset($needed['form_login']);
      $parm['form_login'] = $parm['form_email'];
    }

    $usr_mail = mb_strtolower(trim($parm['form_email']));

    $usr_id = User_Adapter::get_usr_id_from_email($usr_mail);

    if ($usr_id && $usr_mail !== '')
      $needed['form_email'] = _('forms::un utilisateur utilisant cette adresse email existe deja');

    $usr_id = User_Adapter::get_usr_id_from_login($parm['form_login']);

    if ($usr_id)
      $needed['form_login'] = _('forms::un utilisateur utilisant ce login existe deja');

    if (!isset($parm['demand']) || sizeof($parm['demand']) === 0)
      $needed['demandes'] = true;

    //4 on verifieles demandes
    //5 on insere l'utilisateur

    $inscriptions = giveMeBases();
    $inscOK = array();

    if (sizeof($needed) === 0)
    {

      $newUsrEmail = (trim($parm['form_email']) != '') ? $parm['form_email'] : false;

      foreach ($appbox->get_databoxes() as $databox)
      {
        $mailsBas = array();

        $mailColl = array();
        $parm['demand'] = array_unique($parm['demand']);
        foreach ($databox->get_collections() as $collection)
        {
          if (!in_array($collection->get_base_id(), $parm['demand']))
            continue;

          $sbas_id = $databox->get_sbas_id();
          if (isset($inscriptions[$sbas_id]) && $inscriptions[$sbas_id]['inscript'] === true && (isset($inscriptions[$sbas_id]['Colls'][$collection->get_coll_id()]) || isset($inscriptions[$sbas_id]['CollsCGU'][$collection->get_coll_id()])))
            $inscOK[$collection->get_base_id()] = true;
        }
      }

      try
      {

        $user = User_Adapter::create($appbox, $parm['form_login'], $parm["form_password"], $parm["form_email"], false);

        $user->set_gender($parm['form_gender'])
                ->set_firstname($parm['form_firstname'])
                ->set_lastname($parm['form_lastname'])
                ->set_address($parm['form_address'])
                ->set_zip($parm['form_zip'])
                ->set_tel($parm['form_phone'])
                ->set_fax($parm['form_fax'])
                ->set_job($parm['form_job'])
                ->set_company($parm['form_company'])
                ->set_position($parm['form_activity'])
                ->set_geonameid($parm['form_geonameid']);

        $newid = $user->get_id();

        $demandOK = array();

        if ($registry->get('GV_autoregister'))
        {
          $template_user_id = User_Adapter::get_usr_id_from_login('autoregister');

          $template_user = User_Adapter::getInstance($template_user_id, appbox::get_instance());

          $user->ACL()->apply_model($template_user, array_keys($inscOK[$base_id]));
        }

        $autoReg = $user->ACL()->get_granted_base();

        $appbox_register = new appbox_register($appbox);

        foreach ($parm['demand'] as $base_id)
        {
          if (!$inscOK[$base_id] || $user->ACL()->has_access_to_base($base_id))
          {
            continue;
          }
          $collection = collection::get_from_base_id($base_id);
          $appbox_register->add_request($user, $collection);
          unset($collection);
          $demandOK[$base_id] = true;
        }

        $event_mngr = eventsmanager_broker::getInstance($appbox, $Core);

        $params = array(
            'demand' => $demandOK
            , 'autoregister' => $autoReg
            , 'usr_id' => $newid
        );

        $event_mngr->trigger('__REGISTER_AUTOREGISTER__', $params);
        $event_mngr->trigger('__REGISTER_APPROVAL__', $params);


        if ($newUsrEmail)
        {
          $user->set_mail_locked(true);

          return phrasea::redirect('/login/sendmail-confirm.php?usr_id=' . $newid);
        }

        if (count($autoReg) > 0 || count($demandOK) > 0)
        {
          if (count($autoReg) > 0)
          {
            return phrasea::redirect('/login/index.php?confirm=register-ok');
          }
          else
          {
            return phrasea::redirect('/login/index.php?confirm=register-ok-wait');
          }
        }
      }
      catch (Exception $e)
      {

      }
    }
  }
}


phrasea::headers();
?>
<html lang="<?php echo $session->get_I18n(); ?>">
  <head>
    <link REL="stylesheet" TYPE="text/css" HREF="/login/home.css" />
    <link REL="stylesheet" TYPE="text/css" HREF="/login/geonames.css" />
    <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
    <title><?php echo $registry->get('GV_homeTitle') ?> - <?php echo _('login:: register') ?></title>
    <script type="text/javascript" language="javascript" src="/include/minify/f=include/jslibs/jquery-1.5.2.js,include/jslibs/jquery.validate.js,include/jslibs/jquery.validate.password.js,include/jslibs/jquery.validate.login.js"></script>
    <script type="text/javascript">

<?php
$geonames = new geonames();
$first = true;
$sep = $msg = $rules = '';
foreach ($arrayVerif as $ar => $ver)
{
  if($ver === false)
    continue;
  if ($ar != 'form_password')
  {
    if (!$first)
      $sep = ',';
    $first = false;
    $rules .= $sep . $ar . ':{required:true}';
    $msg .= $sep . $ar . ': {';
    $msg .= 'required : "' . _('forms::ce champ est requis') . '"';

    if ($ar == 'form_login' || $ar == 'form_password')
      $msg .= ' ,minlength: "' . _('forms::la valeur donnee est trop courte') . '"';

    if ($ar == 'form_password')
      $msg .= ' ,minlength: "' . _('forms::la valeur donnee est trop courte') . '"';
    if ($ar == 'form_password_confirm')
      $msg .= ' ,equalTo: "' . _('forms::les mots de passe ne correspondent pas') . '"';
    if ($ar == 'form_email')
      $msg .= ',email:"' . (str_replace('"', '\"', _('forms::l\'email semble invalide'))) . '"';

     $msg .= ',login:"'.(str_replace('"','\"',_('login invalide (8 caracteres sans accents ni espaces)'))).'"';
    $msg .= '}';
  }
}
?>

  $(document).ready(function() {

    $.validator.passwordRating.messages = {
      "similar-to-username": "<?php echo _('forms::le mot de passe est trop similaire a l\'identifiant') ?>",
      "too-short": "<?php echo _('forms::la valeur donnee est trop courte') ?>",
      "very-weak": "<?php echo _('forms::le mot de passe est trop simple') ?>",
      "weak": "<?php echo _('forms::le mot de passe est simple') ?>",
      "good": "<?php echo _('forms::le mot de passe est bon') ?>",
      "strong": "<?php echo _('forms::le mot de passe est tres bon') ?>"
    }

    $("#register").validate(
    {
      rules: {
<?php echo $rules ?>
      },
      messages: {
<?php echo $msg ?>
      },
      errorPlacement: function(error, element) {
        error.prependTo( element.parent().next() );
      }
    }
  );

    $('#form_email').rules("add",{email:true});

//            $('#form_login').rules("add",{
//              minlength: 5
//            });

                        $('#form_login').rules("add",{login : true});

    $('#form_password').rules("add",{password: "#form_login"});
    $('#form_password_confirm').rules("add",{equalTo: "#form_password"});


    $("#form_password").valid();

    initialize_geoname_field($('#form_geonameid'));
  });

    </script>
    <script type="text/javascript" src="/login/geonames.js"></script>
  </head>
  <body>

    <div style="width:950px;margin-left:auto;margin-right:auto;">
      <div style="margin-top:70px;height:35px;">
        <table style="width:100%;">
          <tr style="height:35px;">
            <td style="width:600px;"><span style="white-space:nowrap;font-size:28px;color:#b1b1b1;"><?php echo $registry->get('GV_homeTitle') ?> - <?php echo _('login:: register') ?></span></td>
            <td></td>
            <td style="color:#b1b1b1;text-align:right;">
              <?php
              if ($register_enabled)
              {
              ?>
                <a href="register.php" class="tab active" id="register-tab"><?php echo _('login:: register') ?></a>
              <?php
              }
              ?>
              <a href="index.php" class="tab" id="main-tab"><?php echo _('login:: accueil') ?></a>
            </td>
          </tr>
        </table>
      </div>
      <div style="height:530px;" class="tab-pane">
        <div id="id-main" class="tab-content" style="display:block;text-align:center;overflow-y:auto;overflow-x:hidden;">




          <form id="register" name="creation" action="register.php" method="post">

            <table id="form_register_table" cellspacing="0" cellpadding="0" style="font-size:11px;margin:0 auto;">
              <tr style="height:10px;">
                <td colspan="3">
                </td>
              </tr>
              <tr>
                <td class="form_label">
                  <label for="form_login">
                    <?php echo (isset($arrayVerif['form_login']) && $arrayVerif['form_login'] === true) ? '<span class="requiredField">*</span>' : '' ?> <?php echo _('admin::compte-utilisateur identifiant') ?> <br/><span style="font-size:9px;"><?php echo _('8 caracteres minimum') ?></span> :
                  </label>
                </td>
                <td class="form_input">
                  <input id="form_login" autocomplete="off" type="text" value="<?php echo $parm ["form_login"] ?>" class="input_element" name="form_login">
                </td>
                <td class="form_alert">
                  <?php echo isset($needed['form_login']) ? $needed['form_login'] : '' ?>
                  </td>
                </tr>
                                                <tr style="height:10px;">
                                                    <td colspan="3">
                                                    </td>
                                                </tr>
                <tr>
                  <td class="form_label">
                    <label for="form_password">
                    <?php echo (isset($arrayVerif['form_password']) && $arrayVerif['form_password'] === true) ? '<span class="requiredField">*</span>' : '' ?> <?php echo _('admin::compte-utilisateur mot de passe') ?> <br/><span style="font-size:9px;"><?php echo _('8 caracteres minimum') ?></span> :
                  </label>
                </td>
                <td class="form_input">
                  <input autocomplete="off" type="password" value="<?php echo $parm ["form_password"] ?>" class="input_element password" name="form_password" id="form_password" />

                </td>
                <td class="form_alert">
                            <span style="color:white;"><?php echo _('Resistance du mot de passe');?></span><br/>
                                                        <?php echo isset($needed['form_password'])?$needed['form_password']:''?>
                    <div class="password-meter">
                      <div class="password-meter-message">&nbsp;</div>
                      <div class="password-meter-bg">
                        <div class="password-meter-bar"></div>
                      </div>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td class="form_label">
                    <label for="form_password_confirm">
                    <?php echo (isset($arrayVerif['form_password_confirm']) && $arrayVerif['form_password_confirm'] === true) ? '<span class="requiredField">*</span>' : '' ?>  <span style="font-size:9px;">Confirmation</span> :
                  </label>
                </td>
                <td class="form_input">
                  <input id="form_password_confirm" autocomplete="off" type="password" value="<?php echo $parm ["form_password_confirm"] ?>" class="input_element" name="form_password_confirm">
                </td>
                <td class="form_alert">
                  <?php echo isset($needed['form_password_confirm']) ? $needed['form_password_confirm'] : '' ?>
                  </td>
                </tr>
                                                <tr style="height:10px;">
                                                    <td colspan="3">
                            <div style="margin:20px 0;">
                              <a href="#" onclick="$('#password_infos').slideToggle();return false;" style="color:white;font-size:13px;"><?php echo _('admin::compte-utilisateur A propos de la securite des mots de passe');?></a>
                              <div id="password_infos" style="display:none;">
                                <div style="text-align:center;margin:20px 0 0;">
                                  <?php echo _('admin::compte-utilisateur Les mots de passe doivent etre clairement distincts du login et contenir au moins deux types parmis les caracteres suivants :');?>
                                </div>
                                <div style="text-align:left;margin:10px auto;width:300px;">
                                  <ul>
                                    <li><?php echo _('admin::compte-utilisateur::securite caracteres speciaux');?></li>
                                    <li><?php echo _('admin::compte-utilisateur::securite caracteres majuscules');?></li>
                                    <li><?php echo _('admin::compte-utilisateur::securite caracteres minuscules');?></li>
                                    <li><?php echo _('admin::compte-utilisateur::securite caracteres numeriques');?></li>
                                  </ul>
                                </div>
                              </div>
                            </div>
                                                    </td>
                                                </tr>
                <tr>
                  <td colspan="3">
                    <hr/>
                  </td>
                </tr>
                <tr>
                  <td class="form_label">
                    <label for="form_email">
                    <?php echo (isset($arrayVerif['form_email']) && $arrayVerif['form_email'] === true) ? '<span class="requiredField">*</span>' : '' ?> <?php echo _('admin::compte-utilisateur email') ?> :
                  </label>
                </td>
                <td class="form_input">
                  <input id="form_email" autocomplete="off" type="text" value="<?php echo $parm["form_email"] ?>" class="input_element" name="form_email">
                </td>
                <td class="form_alert">
                  <?php echo isset($needed['form_email']) ? $needed['form_email'] : '' ?>
                  </td>
                </tr>
                <tr><td colspan="3">&nbsp;</td></tr>
                <tr>
                  <td class="form_label">
                    <label for="form_city">
                    <?php echo (isset($arrayVerif['form_geonameid']) && $arrayVerif['form_geonameid'] === true) ? '<span class="requiredField">*</span>' : '' ?> <?php echo _('admin::compte-utilisateur ville') ?> :
                  </label>
                </td>
                <td class="form_input">
                  <input id="form_geonameid" type="text" geonameid="<?php echo $parm["form_geonameid"] ?>" value="<?php echo $geonames->name_from_id($parm["form_geonameid"]) ?>" class="input_element geoname_field" name="form_geonameid">
                </td>
                <td class="form_alert">
                  <?php echo isset($needed['form_geonameid']) ? $needed['form_geonameid'] : '' ?>
                  </td>
                </tr>
                <tr>
                  <td class="form_label">
                  <?php echo _('admin::compte-utilisateur sexe') ?> :
                  </td>
                  <td class="form_input">
                    <input type="radio" class="checkbox" name="form_gender" style="width:10px;" <?php echo (($parm ["form_gender"] == 0) ? "checked" : "") ?> value="0"><?php echo _('admin::compte-utilisateur:sexe: mademoiselle') ?>
                    <input type="radio" class="checkbox" name="form_gender" style="width:10px;" <?php echo (($parm ["form_gender"] == 1) ? "checked" : "") ?> value="1"><?php echo _('admin::compte-utilisateur:sexe: madame') ?>
                    <input type="radio" class="checkbox" name="form_gender" style="width:10px;" <?php echo (($parm ["form_gender"] == 2) ? "checked" : "") ?> value="2"><?php echo _('admin::compte-utilisateur:sexe: monsieur') ?>
                  </td>
                  <td class="form_alert">
                  <?php echo isset($needed['form_gender']) ? $needed['form_gender'] : '' ?>
                  </td>
                </tr>
                <tr>
                  <td class="form_label">
                    <label for="form_lastname">
                    <?php echo (isset($arrayVerif['form_lastname']) && $arrayVerif['form_lastname'] === true) ? '<span class="requiredField">*</span>' : '' ?> <?php echo _('admin::compte-utilisateur nom') ?> :
                  </label>
                </td>
                <td class="form_input">
                  <input id="form_lastname" autocomplete="off" type="text" value="<?php echo $parm["form_lastname"] ?>" class="input_element" name="form_lastname">
                </td>
                <td class="form_alert">
                  <?php echo isset($needed['form_lastname']) ? $needed['form_lastname'] : '' ?>
                  </td>
                </tr>
                <tr>
                  <td class="form_label">
                    <label for="form_firstname">
                    <?php echo (isset($arrayVerif['form_firstname']) && $arrayVerif['form_firstname'] === true) ? '<span class="requiredField">*</span>' : '' ?> <?php echo _('admin::compte-utilisateur prenom') ?> :
                  </label>
                </td>
                <td class="form_input">
                  <input id="form_firstname" autocomplete="off" type="text" value="<?php echo $parm["form_firstname"] ?>" class="input_element" name="form_firstname">
                </td>
                <td class="form_alert">
                  <?php echo isset($needed['form_firstname']) ? $needed['form_firstname'] : '' ?>
                  </td>
                </tr>
                <tr>
                  <td class="form_label">
                    <label for="form_job">
                    <?php echo (isset($arrayVerif['form_job']) && $arrayVerif['form_job'] === true) ? '<span class="requiredField">*</span>' : '' ?> <?php echo _('admin::compte-utilisateur poste') ?> :
                  </label>
                </td>
                <td class="form_input">
                  <input id="form_job" autocomplete="off" type="text" value="<?php echo $parm["form_job"] ?>" class="input_element" name="form_job">
                </td>
                <td class="form_alert">
                  <?php echo isset($needed['form_job']) ? $needed['form_job'] : '' ?>
                  </td>
                </tr>
                <tr>
                  <td class="form_label">
                    <label for="form_activity">
                    <?php echo (isset($arrayVerif['form_activity']) && $arrayVerif['form_activity'] === true) ? '<span class="requiredField">*</span>' : '' ?> <?php echo _('admin::compte-utilisateur activite') ?> :
                  </label>
                </td>
                <td class="form_input">
                  <input id="form_activity" autocomplete="off" type="text" value="<?php echo $parm["form_activity"] ?>" class="input_element" name="form_activity">
                </td>
                <td class="form_alert">
                  <?php echo isset($needed['form_activity']) ? $needed['form_activity'] : '' ?>
                  </td>
                </tr>
                <tr>
                  <td class="form_label">
                    <label for="form_phone">
                    <?php echo (isset($arrayVerif['form_phone']) && $arrayVerif['form_phone'] === true) ? '<span class="requiredField">*</span>' : '' ?> <?php echo _('admin::compte-utilisateur telephone') ?> :
                  </label>
                </td>
                <td class="form_input">
                  <input id="form_phone" autocomplete="off" type="text" value="<?php echo $parm["form_phone"] ?>" class="input_element" name="form_phone">
                </td>
                <td class="form_alert">
                  <?php echo isset($needed['form_phone']) ? $needed['form_phone'] : '' ?>
                  </td>
                </tr>
                <tr>
                  <td class="form_label">
                    <label for="form_fax">
                    <?php echo (isset($arrayVerif['form_fax']) && $arrayVerif['form_fax'] === true) ? '<span class="requiredField">*</span>' : '' ?> <?php echo _('admin::compte-utilisateur fax') ?> :
                  </label>
                </td>
                <td class="form_input">
                  <input id="form_fax" autocomplete="off" type="text" value="<?php echo $parm["form_fax"] ?>" class="input_element" name="form_fax">
                </td>
                <td class="form_alert">
                  <?php echo isset($needed['form_fax']) ? $needed['form_fax'] : '' ?>
                  </td>
                </tr>
                <tr>
                  <td class="form_label">
                    <label for="form_company">
                    <?php echo (isset($arrayVerif['form_company']) && $arrayVerif['form_company'] === true) ? '<span class="requiredField">*</span>' : '' ?> <?php echo _('admin::compte-utilisateur societe') ?> :
                  </label>
                </td>
                <td class="form_input">
                  <input id="form_company" autocomplete="off" type="text" value="<?php echo $parm["form_company"] ?>" class="input_element" name="form_company">
                </td>
                <td class="form_alert">
                  <?php echo isset($needed['form_company']) ? $needed['form_company'] : '' ?>
                  </td>
                </tr>
                <tr>
                  <td class="form_label">
                    <label for="form_address">
                    <?php echo (isset($arrayVerif['form_address']) && $arrayVerif['form_address'] === true) ? '<span class="requiredField">*</span>' : '' ?> <?php echo _('admin::compte-utilisateur adresse') ?> :
                  </label>
                </td>
                <td class="form_input">
                  <input id="form_address" autocomplete="off" type="text" value="<?php echo $parm["form_address"] ?>" class="input_element" name="form_address">
                </td>
                <td class="form_alert">
                  <?php echo isset($needed['form_address']) ? $needed['form_address'] : '' ?>
                  </td>
                </tr>

                <tr>
                  <td class="form_label">
                    <label for="form_zip">
                    <?php echo (isset($arrayVerif['form_zip']) && $arrayVerif['form_zip'] === true) ? '<span class="requiredField">*</span>' : '' ?> <?php echo _('admin::compte-utilisateur code postal') ?> :
                  </label>
                </td>
                <td class="form_input">
                  <input id="form_zip" autocomplete="off" type="text" value="<?php echo $parm["form_zip"] ?>" class="input_element" name="form_zip">
                </td>
                <td class="form_alert">
                  <?php echo isset($needed['form_zip']) ? $needed['form_zip'] : '' ?>
                  </td>
                </tr>


                <tr>
                  <td colspan="3">
                    <hr/>
                  </td>
                </tr>
              </table>


            <?php
                    if ($registry->get('GV_autoselectDB'))
                    {
            ?>
                      <div style="display:none;">
              <?php
                    }
              ?>
                    <div style="width:600px;height:20px;text-align:center;margin:0 auto;"><?php echo _('admin::compte-utilisateur actuellement, acces aux bases suivantes : ') ?></div>
                    <div class="requiredField" style="width:600px;height:20px;text-align:center;margin:0 auto;"><?php echo isset($needed['demand']) ? 'Vous n\'avez selectionne aucune base' : '' ?></div>

                    <div style="width:600px;center;margin:0 5px;">


                <?php
                    $demandes = null;
                    if (is_array($parm['demand']))
                      foreach ($parm['demand'] as $id)
                        $demandes[$id] = true;

                    echo giveInscript($lng, $demandes);
                ?>
                  </div>

              <?php
                    if ($registry->get('GV_autoselectDB'))
                    {
              ?>
                    </div>
            <?php
                    }
            ?>
                    <input type="hidden" value="<?php echo $lng ?>" name="lng">
                    <div style="margin:10px 0;text-align:center;"><input type="submit" value="<?php echo _('boutton::valider') ?>"/></div>
                  </form>

                </div>
                <div style="text-align:right;position:relative;margin:18px 10px 0 0;font-size:10px;font-weight:normal;"><span>&copy; Copyright Alchemy 2005-<?php echo date('Y') ?></span></div>
      </div>
    </div>

    <script type="text/javascript">

      $('.tab').hover(function(){
        $(this).addClass('active');
      }, function(){
        $(this).removeClass('active');
      });
    </script>

  </body>
</html>
