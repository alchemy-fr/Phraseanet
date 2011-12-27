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

require_once __DIR__ . "/../../lib/classes/API/OAuth2/Autoloader.class.php";

API_OAuth2_Autoloader::register();

$appbox = appbox::get_instance();

require_once($appbox->get_registry()->get('GV_RootPath') . 'lib/classes/deprecated/inscript.api.php');

$request = http_request::getInstance();
$parm = $request->get_parms("form_gender", "form_lastname", "form_firstname", "form_job", "form_company"
        , "form_function", "form_activity", "form_phone", "form_fax", "form_address", "form_zip", "form_geonameid"
        , "form_destFTP", "form_defaultdataFTP", "form_prefixFTPfolder", "notice", "form_bases", "mail_notifications", "request_notifications", 'demand', 'notifications'
        , "form_activeFTP", "form_addrFTP", "form_loginFTP", "form_pwdFTP", "form_passifFTP", "form_retryFTP");

$lng = Session_Handler::get_locale();



$usr_id = $appbox->get_session()->get_usr_id();
$user = User_Adapter::getInstance($usr_id, $appbox);
$gatekeeper = gatekeeper::getInstance();
$gatekeeper->require_session();

if ($user->is_guest())
{
  phrasea::headers(403);
}
phrasea::headers();


appbox_register::clean_old_requests($appbox);

if ($request->has_post_datas())
{
  $accountFields = array(
      'form_gender',
      'form_firstname',
      'form_lastname',
      'form_address',
      'form_zip',
      'form_phone',
      'form_fax',
      'form_function',
      'form_company',
      'form_activity',
      'form_geonameid',
      'form_addrFTP',
      'form_loginFTP',
      'form_pwdFTP',
      'form_destFTP',
      'form_prefixFTPfolder'
  );

  $demandFields = array(
      'demand'
  );

  $parm['notice'] = 'account-update-bad';

  if (count(array_diff($demandFields, array_keys($request->get_post_datas()))) == 0)
  {
    $register = new appbox_register($appbox);

    foreach ($parm["demand"] as $unebase)
    {
      try
      {
        $register->add_request($user, collection::get_from_base_id($unebase));
        $parm['notice'] = 'demand-ok';
      }
      catch (Exception $e)
      {

      }
    }
  }
  if (count(array_diff($accountFields, array_keys($request->get_post_datas()))) == 0)
  {

    $defaultDatas = 0;
    if ($parm["form_defaultdataFTP"])
    {
      if (in_array('document', $parm["form_defaultdataFTP"]))
        $defaultDatas += 4;
      if (in_array('preview', $parm["form_defaultdataFTP"]))
        $defaultDatas += 2;
      if (in_array('caption', $parm["form_defaultdataFTP"]))
        $defaultDatas += 1;
    }
    try
    {
      $appbox->get_connection()->beginTransaction();
      $user->set_gender($parm["form_gender"])
              ->set_firstname($parm["form_firstname"])
              ->set_lastname($parm["form_lastname"])
              ->set_address($parm["form_address"])
              ->set_zip($parm["form_zip"])
              ->set_tel($parm["form_phone"])
              ->set_fax($parm["form_fax"])
              ->set_job($parm["form_activity"])
              ->set_company($parm["form_company"])
              ->set_position($parm["form_function"])
              ->set_geonameid($parm["form_geonameid"])
              ->set_mail_notifications(($parm["mail_notifications"] == '1'))
              ->set_activeftp($parm["form_activeFTP"])
              ->set_ftp_address($parm["form_addrFTP"])
              ->set_ftp_login($parm["form_loginFTP"])
              ->set_ftp_password($parm["form_pwdFTP"])
              ->set_ftp_passif($parm["form_passifFTP"])
              ->set_ftp_dir($parm["form_destFTP"])
              ->set_ftp_dir_prefix($parm["form_prefixFTPfolder"])
              ->set_defaultftpdatas($defaultDatas);

      $appbox->get_connection()->commit();

      $parm['notice'] = 'account-update-ok';
    }
    catch (Exception $e)
    {
      $appbox->get_connection()->rollBack();
    }
  }
}
if ($request->has_post_datas())
{
  $evt_mngr = eventsmanager_broker::getInstance($appbox, $Core);
  $notifications = $evt_mngr->list_notifications_available($appbox->get_session()->get_usr_id());

  $datas = array();

  foreach ($notifications as $notification => $nots)
  {
    foreach ($nots as $notification)
    {
      $current_notif = $user->getPrefs('notification_' . $notification['id']);

      if (!is_null($parm['notifications']) && isset($parm['notifications'][$notification['id']]))
        $datas[$notification['id']] = '1';
      else
        $datas[$notification['id']] = '0';
    }
  }

  foreach ($datas as $k => $v)
  {
    $user->setPrefs('notification_' . $k, $v);
  }
}
$geonames = new geonames();
$user = User_Adapter::getInstance($appbox->get_session()->get_usr_id(), $appbox);
?>
<html lang="<?php echo $appbox->get_session()->get_I18n(); ?>">
  <head>
    <title><?php echo $appbox->get_registry()->get('GV_homeTitle') ?> <?php echo _('login:: Mon compte') ?></title>
    <link REL="stylesheet" TYPE="text/css" HREF="/include/minify/f=login/home.css,login/geonames.css"/>
    <script type="text/javascript" language="javascript" src="/include/minify/f=include/jslibs/jquery-1.5.2.js"></script>
    <script type="text/javascript" language="javascript" src="/include/jslibs/jquery-ui-1.8.12/js/jquery-ui-1.8.12.custom.min.js"></script>
    <script type="text/javascript" src="/login/geonames.js"></script>
    <script type="text/javascript">
      $(document).ready(function(){

        var trans = {
          'confirm_delete' : "<?php echo _("etes vous sur de vouloir supprimer cette application"); ?>"
          ,'yes' : "<?php echo _("oui"); ?>"
          ,'no' : "<?php echo _("non"); ?>"
        }
        $( "#tab-account-cat").tabs({
          ajaxOptions: {
            error: function( xhr, status, index, anchor ) {
              $( anchor.hash ).html("<?php echo _('Erreur lors du chargement') ?>");
            }
          }
        });

        initialize_geoname_field($('#form_geonameid'));

        $("#generate_access").live("click", function(){
          var app_id = $(this).attr("value");
          var usr_id = $(this).closest("div").attr("id");
          var opts = {
            type:"POST",
            url : '/api/oauthv2/applications/'+ app_id +'/generate_access_token/',
            dataType: 'json',
            data : {
              usr_id : usr_id
            },
            success : function(data){
              if(data.ok)
                $("#my_access_token").empty().append(data.token);
            }
          }
          jQuery.ajax(opts);
        });

        var $url_callback_event = function(event) {
          if ( event.type == "mouseover" ) {
            $(this).find(".modifier_callback").show();
          } else {
            $(this).find(".modifier_callback").hide();
          }
        };

        var $event = function(event){
          if ( event.type == "mouseover" ) {
            $(this).find(".delete_app").show();
          } else {
            $(this).find(".delete_app").hide();
          }
        };

        $(".url_callback").live("mouseover mouseout", $url_callback_event);

        $(".app-list li").live("mouseover mouseout", $event);

        $(".modifier_callback").live("click", function(){
          $(this).hide();
          $(".save_callback").show();
          var cur_value = $(".url_callback_input").html();
          $(".url_callback_input")
          .empty()
          .wrapInner('<input value = "'+cur_value+'" name="oauth_callback" size="50" type="text"/>');
          $(".url_callback").die();
          $(".save_callback").live("click", function(){
            var callback = $("input[name=oauth_callback]").val();
            var app_id = $("input[name=app_id]").val();
            var $this = $(this);
            var option = {
              type:"POST",
              url : "/api/oauthv2/applications/oauth_callback",
              dataType: 'json',
              data :{app_id : app_id, callback : callback},
              success : function(data){
                if(data.success == true)
                  $(".url_callback_input").empty().append(callback);
                else
                  $(".url_callback_input").empty().append(cur_value);
                $this.hide();
                $(".url_callback").live("mouseover mouseout", $url_callback_event);
              }
            }
            $.ajax(option);
          });
        });

        $(".app_submit").live("click", function(){
          var form = $(this).closest("form");
          var action = form.attr("action");
          var option = {
            type:"POST",
            url : action,
            dataType: 'html',
            data : form.serializeArray(),
            success : function(data){
              $(".ui-tabs-panel:visible").empty().append(data);
            }
          }
          $.ajax(option);
        });

        $("#form_create input[name=type]").live("click", function(){
          if($(this).val() == "desktop")
            $("#form_create .callback td").hide().find("input").val('');
          else
            $("#form_create .callback td").show();
        });

        $(".app-btn").live("click", function(){

          if (!$(this).hasClass("authorize"))
          {
            var revoke = 1;
            var button_class = "authorize";
            var old_class ="revoke";
            var string  = "<?php echo _('Authoriser l\'access'); ?>";
          }

          if ($(this).hasClass("authorize"))
          {
            var revoke = 0;
            var button_class = "revoke";
            var old_class ="authorize";
            var string  = "<?php echo _('Revoquer l\'access'); ?>";
          }

          var acc_id = $(this).attr("value");
          var current = $(this);
          var opts = {
            type:"POST",
            url : '/api/oauthv2/applications/revoke_access/',
            dataType: 'json',
            data : {
              account_id : acc_id,
              revoke : revoke
            },
            success : function(data){
              if(data.ok)
              {
                div = current.closest("div");
                current.removeClass(old_class).addClass(button_class);
                current.attr("value", acc_id);
                current.empty().append(string);
              }
            }
          }
          $.ajax(opts);
        });


        $("#app_dev, #app_dev_new, #app_dev_create, a.dev_back").live("click", function(e){
          e.preventDefault();
          target = $(this).attr("href");
          var opts = {
            type:"GET",
            url : target,
            dataType: 'html',
            success : function(data){
              $(".ui-tabs-panel:visible").empty().append(data);
            }
          }
          $.ajax(opts);
        });


        $(".delete_app").die().live("click", function(){
          var id = $(this).closest("li").attr('id').split("_");;
          var app_id = id[1];
          var $this= $(this);
          $("body").append("<div id='confirm_delete'><p>"+trans.confirm_delete+" ? </p></div>")
          $("#confirm_delete").dialog({
            resizable: false,
            autoOpen :true,
            title: "",
            draggable: false,
            width:340,
            modal: true,
            buttons: [{
                id: "ybutton",
                text: trans.yes,
                click: function() {
                  var opts = {
                    type:"DELETE",
                    url : '/api/oauthv2/applications/'+ app_id,
                    dataType: 'json',
                    data : {},
                    success : function(data){
                      if(data.success == true)
                      {
                        $this.closest("li").remove();
                        $("#confirm_delete").dialog("close");
                      }
                    }
                  }
                  $.ajax(opts);
                }
              },
              {
                id: "nbutton",
                text: trans.no,
                click: function() {
                  $( this ).dialog( "close" );
                }
              }],
            close : function() {
              $( this ).remove();
            }
          });
        });

      });
    </script>
    <style type="text/css">
      .tab-content{
        height:auto;
      }
    </style>
  </head>
  <body>
    <div style="width:950px;margin-left:auto;margin-right:auto;">
      <div style="margin-top:70px;height:35px;">
        <table style="width:100%;">
          <tr style="height:35px;">
            <td style="width:580px;"><span class="title-name"><?php echo $appbox->get_registry()->get('GV_homeTitle') ?></span><span class="title-desc"><?php echo _('login:: Mon compte') ?></span></td>
            <td style="color:#b1b1b1;text-align:right;">

            </td>
          </tr>
        </table>
      </div>
      <div class="tab-pane">
        <div id="id-main" class="tab-content" style="display:block;text-align:center;overflow-y:auto;overflow-x:hidden;">
          <div id="tab-account-cat">

            <ul>
              <li><a href="#tab-account-info"><?php echo _('Informations'); ?></a></li>
              <li><a href="#tab-account-access"><?php echo _('Acces'); ?></a></li>
              <li><a href="#tab-account-session"><?php echo _('Sessions'); ?></a></li>
              <li><a href="/api/oauthv2/applications" title="tab-account-app"><?php echo _('Applications'); ?></a></li>
              <li><a href="/api/oauthv2/applications/dev" title="tab-account-dev"><?php echo _('Developpeur'); ?></a></li>
            </ul>

            <div id="tab-account-info">
              <table>
                <tr valign="top">
                  <td style="width:98%">

                    <?php
                    $notice = '';
                    if (!is_null($parm['notice']))
                    {
                      switch ($parm['notice'])
                      {
                        case 'password-update-ok':
                          $notice = _('login::notification: Mise a jour du mot de passe avec succes');
                          break;
                        case 'account-update-ok':
                          $notice = _('login::notification: Changements enregistres');
                          break;
                        case 'account-update-bad':
                          $notice = _('forms::erreurs lors de l\'enregistrement des modifications');
                          break;
                        case 'demand-ok':
                          $notice = _('login::notification: Vos demandes ont ete prises en compte');
                          break;
                      }
                    }
                    if ($notice != '')
                    {
                      ?>
                      <div class="notice"><?php echo $notice ?></div>
                      <?php
                    }
                    ?>
                    <form name="account" id="account" action="/login/account.php" method="post">
                      <table style="margin:20px auto;">
                        <tr>
                          <td></td>
                          <td><a href="/login/reset-password.php" class="link" target="_self"><?php echo _('admin::compte-utilisateur changer mon mot de passe'); ?></a></td>
                          <td></td>
                        </tr>
                        <tr>
                          <td colspan="3"></td>
                        </tr>
                        <tr>
                          <td class="form_label"><label for="form_login"><?php echo _('admin::compte-utilisateur identifiant'); ?></label></td>
                          <td class="form_input"><?php echo $user->get_login() ?></td>
                          <td class="form_alert"></td>
                        </tr>
                        <tr>
                          <td class="form_label"><label for="form_gender"><?php echo _('admin::compte-utilisateur sexe') ?></label></td>
                          <td class="form_input">
                            <select class="input_element" name="form_gender" id="form_gender"  >
                              <option <?php echo ($user->get_gender() == "0" ? "selected" : "") ?> value="0" ><?php echo _('admin::compte-utilisateur:sexe: mademoiselle'); ?></option>
                              <option <?php echo ($user->get_gender() == "1" ? "selected" : "") ?> value="1" ><?php echo _('admin::compte-utilisateur:sexe: madame'); ?></option>
                              <option <?php echo ($user->get_gender() == "2" ? "selected" : "") ?> value="2" ><?php echo _('admin::compte-utilisateur:sexe: monsieur'); ?></option>
                            </select>
                          </td>
                          <td class="form_alert"></td>
                        </tr>
                        <tr>
                          <td class="form_label"><label for="form_lastname"><?php echo _('admin::compte-utilisateur nom'); ?></label></td>
                          <td class="form_input">
                            <input class="input_element" type="text" name="form_lastname" id="form_lastname" value="<?php echo $user->get_lastname() ?>" >
                          </td>
                          <td class="form_alert"></td>
                        </tr>
                        <tr>
                          <td class="form_label"><label for="form_firstname"><?php echo _('admin::compte-utilisateur prenom'); ?></label></td>
                          <td class="form_input">
                            <input  class="input_element"  type="text" name="form_firstname" id="form_firstname" value="<?php echo $user->get_firstname() ?>" >
                          </td>
                          <td class="form_alert"></td>
                        </tr>
                        <tr>
                          <td colspan="3">
                        </tr>
                        <tr>
                          <td class="form_label"><label for=""><?php echo _('admin::compte-utilisateur email') ?></label></td>
                          <td class="form_input" colspan="2">
                            <?php echo $user->get_email() ?> <a class="link" href="/login/reset-email.php" target="_self"><?php echo _('login:: Changer mon adresse email') ?></a>
                          </td>
                        </tr>
                        <tr>
                          <td colspan="3"></td>
                        </tr>
                        <tr>
                          <td colspan="3">Notification par email</td>
                        </tr>
                        <?php
                        $evt_mngr = eventsmanager_broker::getInstance($appbox, $Core);
                        $notifications = $evt_mngr->list_notifications_available($appbox->get_session()->get_usr_id());

                        foreach ($notifications as $notification_group => $nots)
                        {
                          ?>
                          <tr>
                            <td style="font-weight:bold;" colspan="3"><?php echo $notification_group; ?></td>
                          </tr>
                          <?php
                          foreach ($nots as $notification)
                          {
                            ?>
                            <tr>
                              <td class="form_label" colspan="2"><label for="notif_<?php echo $notification['id'] ?>"><?php echo $notification['description'] ?></label></td>
                              <td class="form_input">
                                <input type="checkbox" id="notif_<?php echo $notification['id'] ?>" name="notifications[<?php echo $notification['id'] ?>]" <?php echo $user->getPrefs('notification_' . $notification['id']) == '0' ? '' : 'checked'; ?> value="1"/>
                              </td>
                            </tr>
                            <?php
                          }
                        }
                        ?>
                        <tr>
                          <td colspan="3"></td>
                        </tr>
                        <tr>
                          <td class="form_label"><label for="form_address"><?php echo _('admin::compte-utilisateur adresse') ?></label></td>
                          <td class="form_input">
                            <input  class="input_element" type="text" name="form_address" id="form_address" value="<?php echo $user->get_address() ?>"/>
                          </td>
                          <td class="form_alert"></td>
                        </tr>
                        <tr>
                          <td class="form_label"><label for="form_zip"><?php echo _('admin::compte-utilisateur code postal') ?></label></td>
                          <td class="form_input">
                            <input  class="input_element" type="text" name="form_zip", id="form_zip" value="<?php echo $user->get_zipcode() ?>"/>
                          </td>
                          <td class="form_alert"></td>
                        </tr>
                        <tr>
                          <td class="form_label"><label for="form_city"><?php echo _('admin::compte-utilisateur ville') ?></label></td>
                          <td class="form_input">
                            <input id="form_geonameid" type="text" geonameid="<?php echo $user->get_geonameid() ?>" value="<?php echo $geonames->name_from_id($user->get_geonameid()) ?>" class="input_element geoname_field" name="form_geonameid">
                          </td>
                          <td class="form_alert"></td>
                        </tr>
                        <tr>
                          <td class="form_label"></td>
                          <td class="form_input"><div id="test_city" style="position:absolute;width:200px;max-height:200px;overflow-y:auto;z-index:99999;"></div></td>
                          <td class="form_alert"></td>
                        </tr>
                        <tr>
                          <td colspan="3">
                        </tr>
                        <tr>
                          <td class="form_label"><label for="form_function"><?php echo _('admin::compte-utilisateur poste') ?></label></td>
                          <td class="form_input">
                            <input  class="input_element" type="text" name="form_function" id="form_function" value="<?php echo $user->get_position() ?>"/>
                          </td>
                          <td class="form_alert"></td>
                        </tr>
                        <tr>
                          <td class="form_label"><label for="form_company"><?php echo _('admin::compte-utilisateur societe') ?></label></td>
                          <td class="form_input">
                            <input  class="input_element" type="text" name="form_company" id="form_company" value="<?php echo $user->get_company() ?>"/>
                          </td>
                          <td class="form_alert"></td>
                        </tr>
                        <tr>
                          <td class="form_label"><label for="form_activity"><?php echo _('admin::compte-utilisateur activite') ?></label></td>
                          <td class="form_input">
                            <input  class="input_element" type="text" name="form_activity" id="form_activity" value="<?php echo $user->get_job() ?>"/>
                          </td>
                          <td class="form_alert"></td>
                        </tr>
                        <tr>
                          <td class="form_label"><label for="form_phone"><?php echo _('admin::compte-utilisateur telephone') ?></label></td>
                          <td class="form_input">
                            <input  class="input_element" type="text" name="form_phone" id="form_phone" value="<?php echo $user->get_tel() ?>"/>
                          </td>
                          <td class="form_alert"></td>
                        </tr>
                        <tr>
                          <td class="form_label"><label for="form_fax"><?php echo _('admin::compte-utilisateur fax') ?></label></td>
                          <td class="form_input">
                            <input  class="input_element" type="text" name="form_fax" id="form_fax" value="<?php echo $user->get_fax() ?>"/>
                          </td>
                          <td class="form_alert"></td>
                        </tr>
                        <tr>
                          <td colspan="3">
                        </tr>


                        <tr>
                          <td class="form_label"><label for="form_activeFTP"><?php echo _('admin::compte-utilisateur:ftp: Activer le compte FTP'); ?></label></td>
                          <td class="form_input">
                            <input onchange="if(this.checked){$('#ftpinfos').slideDown();}else{$('#ftpinfos').slideUp();}" style=""  type="checkbox" class="checkbox" <?php echo ($user->get_activeftp() ? "checked" : "") ?> name="form_activeFTP" id="form_activeFTP">
                          </td>
                          <td class="form_alert"></td>
                        </tr>
                        <tr>
                          <td colspan="3">
                            <div id="ftpinfos" style="display:<?php echo ($user->get_activeftp() ? "block" : "none") ?>;">
                              <table>
                                <tr>
                                  <td class="form_label"><label for="form_addrFTP"><?php echo _('phraseanet:: adresse') ?></label></td>
                                  <td class="form_input">
                                    <input  class="input_element" type="text" name="form_addrFTP" id="form_addrFTP" value="<?php echo $user->get_ftp_address() ?>"/>
                                  </td>
                                  <td class="form_alert"></td>
                                </tr>
                                <tr>
                                  <td class="form_label"><label for="form_loginFTP"><?php echo _('admin::compte-utilisateur identifiant') ?></label></td>
                                  <td class="form_input">
                                    <input  class="input_element" type="text" name="form_loginFTP" id="form_loginFTP" value="<?php echo $user->get_ftp_login() ?>"/>
                                  </td>
                                  <td class="form_alert"></td>
                                </tr>

                                <tr>
                                  <td class="form_label"><label for="form_pwdFTP"><?php echo _('admin::compte-utilisateur mot de passe') ?></label></td>
                                  <td class="form_input">
                                    <input class="input_element" type="password" name="form_pwdFTP" id="form_pwdFTP" value="<?php echo $user->get_ftp_password() ?>"/>
                                  </td>
                                  <td class="form_alert"></td>
                                </tr>

                                <tr>
                                  <td class="form_label"><label for="form_destFTP"><?php echo _('admin::compte-utilisateur:ftp:  repertoire de destination ftp') ?></label></td>
                                  <td class="form_input">
                                    <input class="input_element" type="text" name="form_destFTP" id="form_destFTP" value="<?php echo $user->get_ftp_dir() ?>"/>
                                  </td>
                                  <td class="form_alert"></td>
                                </tr>
                                <tr>
                                  <td class="form_label"><label for="form_prefixFTPfolder"><?php echo _('admin::compte-utilisateur:ftp: prefixe des noms de dossier ftp') ?></label></td>
                                  <td class="form_input">
                                    <input class="input_element" type="text" name="form_prefixFTPfolder" id="form_prefixFTPfolder" value="<?php echo $user->get_ftp_dir_prefix() ?>"/>
                                  </td>
                                  <td class="form_alert"></td>
                                </tr>
                                <tr>
                                  <td class="form_label"><label for="form_passifFTP"><?php echo _('admin::compte-utilisateur:ftp: Utiliser le mode passif') ?></label></td>
                                  <td class="form_input">
                                    <input type="checkbox" <?php echo ($user->get_ftp_passif() == "1" ? "checked" : "") ?> name="form_passifFTP" id="form_passifFTP"/>
                                  </td>
                                  <td class="form_alert"></td>
                                </tr>
                                <tr style="display:none;">
                                  <td class="form_label"><label for="form_retryFTP"><?php echo _('admin::compte-utilisateur:ftp: Nombre d\'essais max') ?></label></td>
                                  <td class="form_input">
                                    <input class="input_element" type="text" name="form_retryFTP" id="form_retryFTP" value="5"/>
                                  </td>
                                  <td class="form_alert"></td>
                                </tr>
                                <tr style="display:none;">
                                  <td class="form_label"><label for="form_defaultdataFTP"><?php echo _('admin::compte-utilisateur:ftp: Donnees envoyees automatiquement par ftp') ?></label></td>
                                  <td class="form_input">
                                    <input class="checkbox" type="checkbox" <?php echo ((($user->get_defaultftpdatas() >> 2) & 1) == 1 ? "checked" : "") ?> name="form_defaultdataFTP[]" value="document" id="form_defaultSendDocument"><label for="form_defaultSendDocument"><?php echo _('phraseanet:: original'); ?></label>
                                    <input class="checkbox" type="checkbox" <?php echo ((($user->get_defaultftpdatas() >> 1) & 1) == 1 ? "checked" : "") ?> name="form_defaultdataFTP[]" value="preview" id="form_defaultSendPreview"><label for="form_defaultSendPreview"><?php echo _('phraseanet:: preview'); ?></label>
                                    <input class="checkbox" type="checkbox" <?php echo (($user->get_defaultftpdatas() & 1) == 1 ? "checked" : "") ?> name="form_defaultdataFTP[]" value="caption" id="form_defaultSendCaption"><label for="form_defaultSendCaption"><?php echo _('phraseanet:: imagette'); ?></label>
                                  </td>
                                  <td class="form_alert"></td>
                                </tr>
                              </table>
                            </div>
                          </td>
                        </tr>
                      </table>
                      <div style="text-align:center;margin:5px 0;">
                        <input type="submit" value="<?php echo _('boutton::valider'); ?>">
                      </div>
                    </form>
                  </td>

                </tr>
              </table>
            </div>

            <!-- END TAB ACCOUNT -->
            <!-- START TAB ACCESS -->
            <div id="tab-account-access">


              <form name="updatingDemand" id="updatingDemand" action="/login/account.php" method="post">
                <?php
                $demandes = giveMeBaseUsr($usr_id, $lng);
                echo $demandes['tab'];
                ?>
                <input type="submit" value="<?php echo _('boutton::valider'); ?>"/>
              </form>


            </div>

            <!-- END TAB ACCESS -->
            <!-- START TAB SESSION -->

            <div id="tab-account-session">
              <table style="width:80%;margin:0 auto;">
                <thead>
                  <tr>
                    <th colspan="7" style="text-align:left;">
                      Mes session
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>

                    </td>
                    <td>
                      Date de connexion
                    </td>
                    <td>
                      Dernier access
                    </td>
                    <td>
                      IP
                    </td>
                    <td>
                      Browser
                    </td>
                    <td>
                      ecran
                    </td>
                    <td>
                      Session persistante
                    </td>
                  </tr>

                  <?php
                  foreach ($appbox->get_session()->get_my_sessions() as $row)
                  {
                    ?>
                    <tr>
                      <td>
                        <?php
                        if ($appbox->get_session()->get_ses_id() != $row['session_id'])
                        {
                          ?>
                          <img src="/skins/icons/delete.png"/>
                          <?php
                        }
                        ?>
                      </td>
                      <td>
                        <?php echo phraseadate::getDate(new DateTime($row['created_on'])) ?>
                      </td>
                      <td>
                        <?php echo phraseadate::getDate(new DateTime($row['lastaccess'])) ?>
                      </td>
                      <td>
                        <?php echo $row['ip'] ?>
                        <?php echo $row['ip_infos'] ?>
                      </td>
                      <td>
                        <?php echo $row['browser'];
                        echo ' ' . $row['browser_version'] ?>
                      </td>
                      <td>
                        <?php echo $row['screen'] ?>
                      </td>
                      <td>
                        <?php echo $row['token'] ? 'oui' : '' ?>
                      </td>
                    </tr>
                    <?php
                  }
                  ?>
                </tbody>
              </table>
            </div>
            <!-- END TAB SESSION -->
            <!-- START TAB APPLICATION -->
            <div id="tab-account-app">


            </div>
            <div id="tab-account-dev">


            </div>
          </div>
        </div>
        <div style="text-align:right;position:relative;margin:18px 10px 0 0;font-size:10px;font-weight:normal;"><span>&copy; Copyright Alchemy 2005-<?php echo date('Y') ?></span></div>
      </div>
    </div>
  </body>
</html>

