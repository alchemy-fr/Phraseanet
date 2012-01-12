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
require_once __DIR__ . "/../../lib/bootstrap.php";

$appbox = appbox::get_instance();
$session = $appbox->get_session();
$registry = $appbox->get_registry();

$request = http_request::getInstance();
$parm = $request->get_parms("srt", "ord", "act", "p0", "p1", "p2", "sta", 'admins', 'pub_wm');

$usr_id = $session->get_usr_id();
$base_id = (int) $parm['p1'];

$ACL = User_Adapter::getInstance($usr_id, $appbox)->ACL();

if (!$ACL->has_access_to_base($base_id))
{
  phrasea::headers(403);
}

$collection = collection::get_from_base_id($parm['p1']);

$sbas_id = $collection->get_databox()->get_sbas_id();
$distant_coll_id = $collection->get_coll_id();
$addr = $collection->get_databox()->get_serialized_server_info();

$msg = array();

$refreshFinder = false;


if (is_array($parm['admins']))
{
  $admins = array();

  foreach ($parm['admins'] as $a)
  {
    if (trim($a) == '')
      continue;

    $admins[] = $a;
  }

  if ($admins > 0)
  {
    set_exportorder::set_order_admins($admins, $base_id);
  }
}

switch ($parm['act'])
{
  case 'ENABLED':
    $collection->enable($appbox);
    break;
  case 'DISABLED';
    $collection->disable($appbox);
    break;
  case 'pub_wm':
    if ($ACL->has_right_on_base($base_id, 'canadmin') == 1)
    {
      $collection->set_public_presentation($parm['pub_wm']);
    }
    break;
  case 'APPLYNEWNAMECOLL':
    $collection->set_name($parm['p2']);
    $refreshFinder = true;
    break;
  case 'UMOUNTCOLL':
    $collection->unmount_collection($appbox);
    $msg['ACTDONE'] = $collection->get_name() . ' ' . _('forms::operation effectuee OK');
    $refreshFinder = true;
    break;
  case 'DODELETECOLL':
    if ($collection->get_record_amount() > 0)
    {
      $msg['ACTDONE'] = _('admin::base:collection: vider la collection avant de la supprimer');
    }
    else
    {
      $collection->unmount_collection($appbox);
      $collection->delete();

      $msg['ACTDONE'] = _('forms::operation effectuee OK');
      $refreshFinder = true;
    }
    break;

  case 'SENDMINILOGO':
    if (isset($_FILES['newLogo']))
    {
      if ($_FILES['newLogo']['size'] > 65535)
      {
        $msg['SENDMINILOGO'] = '<div style="color:#FF0000">' . _('admin::base:collection le fichier envoye est trop volumineux.') . ' 64Ko </div>';
      }
      elseif ($_FILES['newLogo']['error'])
      {
        $msg['SENDMINILOGO'] = '<div style="color:#FF0000">' . _('forms::erreur lors de l\'envoi du fichier') . '</div>';
      }
      elseif (( $_FILES['newLogo']['error'] == UPLOAD_ERR_OK))
      {
        try
        {
          $appbox->write_collection_pic($collection, new system_file($_FILES['newLogo']['tmp_name']), collection::PIC_LOGO);
        }
        catch (Exception $e)
        {
          $msg['SENDMINILOGO'] = $e->getMessage();
        }
      }
    }
    break;

  case 'DELMINILOGO':
    try
    {
      $collection->update_logo(null);
      $appbox->write_collection_pic($collection, null, collection::PIC_LOGO);
    }
    catch (Exception $e)
    {
      $msg['DELMINILOGO'] = $e->getMessage();
    }
    break;

  case 'SENDWM':
  case 'DELWM':
    $collection->reset_watermark();

    if ($parm['act'] == 'SENDWM' && isset($_FILES['newWm']))
    {
      if ($_FILES['newWm']['size'] > 65535)
      {
        $msg['SENDWM'] = '<div style="color:#FF0000">' . _('admin::base:collection le fichier envoye est trop volumineux.') . " 64Ko" . "</div>";
      }
      elseif ($_FILES['newWm']['error'])
      {
        $msg['SENDWM'] = '<div style="color:#FF0000">' . _('forms::erreur lors de l\'envoi du fichier') . "</div>"; // par le serveur (fichier php.ini)
      }
      elseif (($_FILES['newWm']['error'] == UPLOAD_ERR_OK))
      {
        try
        {
          $appbox->write_collection_pic($collection, new system_file($_FILES['newWm']["tmp_name"]), collection::PIC_WM);
        }
        catch(Exception $e)
        {
          $msg['SENDWM'] = '<div style="color:#FF0000">' . $e->getMessage() . "</div>";
        }
        unlink($_FILES['newWm']["tmp_name"]);
      }
    }
    elseif ($parm['act'] == "DELWM")
    {
      try
      {
        $appbox->write_collection_pic($collection, null, collection::PIC_WM);
      }
      catch(Exception $e)
      {
        $msg['DELWM'] = '<div style="color:#FF0000">' . $e->getMessage() . "</div>";
      }
    }
    break;

  case 'SENDSTAMPLOGO':
    if (isset($_FILES['newStampLogo']))
    {
      if ($_FILES['newStampLogo']['size'] > 1024 * 1024)
      {
        $msg['SENDSTAMPLOGO'] = '<div style="color:#FF0000">' . _('admin::base:collection le fichier envoye est trop volumineux.') . ' 1Mo </div>';
      }
      elseif ($_FILES['newStampLogo']['error'])
      {
        $msg['SENDSTAMPLOGO'] = '<div style="color:#FF0000">' . _('forms::erreur lors de l\'envoi du fichier') . '</div>'; // par le serveur (fichier php.ini)
      }
      elseif (( $_FILES['newStampLogo']['error'] == UPLOAD_ERR_OK))
      {
        try
        {
          $appbox->write_collection_pic($collection, new system_file($_FILES['newStampLogo']["tmp_name"]), collection::PIC_STAMP);
        }
        catch(Exception $e)
        {
          $msg['SENDSTAMPLOGO'] = '<div style="color:#FF0000">' . $e->getMessage() . "</div>";
        }
        unlink($_FILES['newStampLogo']["tmp_name"]);
      }
    }
    break;

  case 'DELSTAMPLOGO':
    try
    {
      $appbox->write_collection_pic($collection, null, collection::PIC_STAMP);
    }
    catch(Exception $e)
    {
      $msg['DELSTAMPLOGO'] = '<div style="color:#FF0000">' . $e->getMessage() . "</div>";
    }
    break;

  case 'SENDPRESENTPICT':
    if (isset($_FILES['newPresentPict']))
    {
      if ($_FILES['newPresentPict']['size'] > 1024 * 1024 * 2)
      {
        $msg['SENDPRESENTPICT'] = '<div style="color:#FF0000">' . _('admin::base:collection le fichier envoye est trop volumineux.') . ' 2Mo </div>';
      }
      elseif ($_FILES['newPresentPict']['error'])
      {
        $msg['SENDPRESENTPICT'] = '<div style="color:#FF0000">' . _('forms::erreur lors de l\'envoi du fichier') . '</div>'; // par le serveur (fichier php.ini)
      }
      elseif ($_FILES['newPresentPict']['error'] == UPLOAD_ERR_OK)
      {
        try
        {
          $appbox->write_collection_pic($collection, new system_file($_FILES['newPresentPict']["tmp_name"]), collection::PIC_PRESENTATION);
        }
        catch(Exception $e)
        {
          $msg['SENDPRESENTPICT'] = '<div style="color:#FF0000">' . $e->getMessage() . "</div>";
        }
        unlink($_FILES['newPresentPict']["tmp_name"]);
      }
    }
    break;

  case 'DELPRESENTPICT':
    try
    {
      $appbox->write_collection_pic($collection, null, collection::PIC_PRESENTATION);
    }
    catch(Exception $e)
    {
      $msg['DELPRESENTPICT'] = '<div style="color:#FF0000">' . $e->getMessage() . "</div>";
    }
    @unlink($registry->get('GV_RootPath') . 'config/presentation/' . $base_id);
    break;
}

function showMsg($k)
{
  global $msg;
  if (isset($msg[$k]))
    echo($msg[$k]);
}

phrasea::headers();
?>
<html lang="<?php echo $session->get_I18n(); ?>">
  <head>
    <link type="text/css" rel="stylesheet" href="/include/minify/f=include/jslibs/jquery-ui-1.8.12/css/ui-lightness/jquery-ui-1.8.12.custom.css,skins/common/main.css,skins/admin/admincolor.css" />
    <script type="text/javascript" src="/include/minify/f=include/jslibs/jquery-1.5.2.js"></script>
    <script type="text/javascript" src="/include/jslibs/jquery-ui-1.8.12/js/jquery-ui-1.8.12.custom.min.js"></script>
    <script type="text/javascript">
      var ntask = 0 ;

      function sendForm(act)
      {
        document.forms["manageColl"].target = "";
        document.forms["manageColl"].act.value = act;
        document.forms["manageColl"].submit();
      }

      function emptyColl(collname)
      {
        if(confirm("<?php echo _('admin::base:collection: etes vous sur de vider la collection ?') ?>"))
        {
          $.ajax({
            type: "POST",
            url: "/admin/adminFeedback.php?action=EMPTYCOLL",
            dataType: 'json',
            data: {
              sbas_id:<?php echo $sbas_id ?>,
              coll_id:<?php echo $distant_coll_id ?>

            },
            success: function(data){
              return;
            }
          });
        }
      }

      function askUnmountColl()
      {
        if(confirm("<?php echo _('admin::base:collection: etes vous sur de demonter cette collection ?') ?>"))
        sendForm('UMOUNTCOLL');
      }

      function showDetails(sta)
      {
        document.forms["manageColl"].sta.value = sta;
        sendForm('');
      }

      function enabledPublication(bool)
      {
        if(bool)
        {
          if(confirm("<?php echo _('admin::base:collection: etes vous sur de publier cette collection ?') ?>"))
          sendForm('ENABLED');
        }
        else
        {
          if(confirm("<?php echo _('admin::base:collection: etes vous sur darreter la publication de cette collection') ?>"))
          sendForm('DISABLED');
        }
      }
<?php
if ($refreshFinder)
{
  print("      parent.reloadTree('base:" . $sbas_id . "');\n");
}
?>
    </script>
    <style>
      .logo_boxes
      {
        margin:5px 5px 5px 10px;
        padding-top:5px;
        border-top:2px solid black;
      }
      a:link,a:visited{
        text-decoration:none;
        color:#666;
      }
      a:hover{
        text-decoration:underline;
        color:black;
      }
    </style>

  <style>
    .ui-autocomplete {
      max-height: 200px;
      overflow-y: auto;
      /* prevent horizontal scrollbar */
      overflow-x: hidden;
      /* add padding to account for vertical scrollbar */
      padding-right: 20px;
    }
    /* IE 6 doesn't support max-height
     * we use height instead, but this forces the menu to always be this tall
     */
    * html .ui-autocomplete {
      height: 200px;
    }
    .ui-autocomplete-loading { background: white url('/skins/icons/ui-anim_basic_16x16.gif') right center no-repeat; }
  </style>
  <script type="text/javascript">

    $(document).ready(function(){
      $( ".admin_adder" ).autocomplete({
        source: "/admin/users/typeahead/search/?have_not_right[]=order_master&on_base[]=<?php echo $base_id; ?>",
        minLength: 2,
        select: function( event, ui ) {
          var form = $('#admin_adder');
          $('input[name="admins[]"]', form).val(ui.item.id);
          form.submit();
        }
      }).data( "autocomplete" )._renderItem = function( ul, item ) {
        var email = item.email ? '<br/>'+item.email : '';
        var login = item.login != item.name ? " ("+ item.login +")" : '';

        return $( "<li></li>" )
          .data( "item.autocomplete", item )
          .append( "<a>" + item.name + login + email + "</a>" )
          .appendTo( ul );
      };
    });
  </script>
  </head>
  <body>
    <h1>
      <?php echo _('phraseanet:: collection'); ?> <b><?php echo $collection->get_name(); ?></b>
    </h1>
    <div style='margin:3px 0 3px 10px;'>
      <?php echo _('phraseanet:: adresse'); ?> : <?php echo $addr; ?>&nbsp;
    </div>
    <?php showMsg('ACTDONE') ?>
      <div style='margin:3px 0 3px 10px;'>
      <?php echo _('admin::base:collection: numero de collection distante'); ?> : <?php echo $distant_coll_id; ?>&nbsp;
    </div>

        <div style="margin:3px 0 3px 10px;">
      <?php echo _('admin::base:collection: etat de la collection') . " : " . ( $collection->is_active() ? _('admin::base:collection: activer la collection') : _('admin::base:collection: descativer la collection') ) ?>&nbsp;
      </div>

      <div style="margin:3px 0 3px 10px;">
      <?php
        echo $collection->get_record_amount() . ' records' . "\n";

        if ($parm["sta"] == "" || $parm["sta"] == NULL || $parm["sta"] == 0)
        {
      ?>
          (<a href="javascript:void(0);" onclick="showDetails(1);return(false);">
        <?php echo _('phraseanet:: details') ?>
        </a>)
        <br />
      <?php
        }
        else
        {
          $trows = $collection->get_record_details();
      ?>
                                                                        (<a href="javascript:void(0);" onclick="showDetails(0);return(false);">
      <?php echo _('admin::base: masquer les details') ?>
          </a>)
          <br />
          <br />
          <table class="ulist">
            <col width=180px>
            <col width=100px>
            <col width=60px>
            <col width=80px>
            <col width=70px>
            <thead>
              <tr>
                <th>
<?php
          if ($parm["srt"] == "obj")
            print('<img src="/skins/icons/tsort_desc.gif">&nbsp;');
          print(_('admin::base: objet'));
?>
            </th>
            <th>
<?php echo _('admin::base: nombre') ?>
            </th>
            <th>
<?php echo _('admin::base: poids') ?> (Mo)
            </th>
            <th>
<?php echo _('admin::base: poids') ?> (Go)
            </th>
          </tr>
        </thead>
        <tbody>
<?php
          $totobj = 0;
          $totsiz = "0";  // les tailles de fichiers sont calculees avec bcmath

          foreach ($trows as $vrow)
          {
            $midobj = 0;
            $midsiz = "0";
            $last_k1 = $last_k2 = null;
            if ($vrow["amount"] > 0 || $last_k1 !== $vrow["coll_id"])
            {
              if (extension_loaded("bcmath"))
                $midsiz = bcadd($midsiz, $vrow["size"], 0);
              else
                $midsiz += $vrow["size"];
              if (extension_loaded("bcmath"))
                $mega = bcdiv($vrow["size"], 1024 * 1024, 5);
              else
                $mega = $vrow["size"] / (1024 * 1024);
              if (extension_loaded("bcmath"))
                $giga = bcdiv($vrow["size"], 1024 * 1024 * 1024, 5);
              else
                $giga = $vrow["size"] / (1024 * 1024 * 1024);
?>
              <tr>
                <td>
<?php
              if ($last_k2 !== $vrow["name"])
              {
                print($last_k2 = $vrow["name"]);
              }
?>
            </td>
            <td style="text-align:right">
              &nbsp;
<?php echo $vrow["amount"] ?>
              &nbsp;
            </td>
            <td style="text-align:right">
              &nbsp;
<?php printf("%.2f", $mega) ?>
              &nbsp;
            </td>
            <td style="text-align:right">
              &nbsp;
<?php sprintf("%.2f", $giga) ?>
              &nbsp;
            </td>
          </tr>
<?php
            }
            $totobj += $midobj;
            if (extension_loaded("bcmath"))
              $totsiz = bcadd($totsiz, $midsiz, 0);
            else
              $totsiz += $midsiz;
            if (extension_loaded("bcmath"))
              $mega = bcdiv($midsiz, 1024 * 1024, 5);
            else
              $mega = $midsiz / (1024 * 1024);

            if (extension_loaded("bcmath"))
              $giga = bcdiv($midsiz, 1024 * 1024 * 1024, 5);
            else
              $giga = $midsiz / (1024 * 1024 * 1024);
?>
            <tr>
              <td style="text-align:right">
                <i>total</i>
              </td>
              <td style="text-align:right; TEXT-DECORATION:overline">
                &nbsp;
<?php echo $midobj ?>
                &nbsp;
              </td>
              <td style="text-align:right; TEXT-DECORATION:overline">
                &nbsp;
<?php printf("%.2f", $mega) ?>
              &nbsp;
            </td>
            <td style="text-align:right; TEXT-DECORATION:overline">
              &nbsp;
<?php printf("%.2f", $giga) ?>
              &nbsp;
            </td>
          </tr>
          <tr>
            <td colspan="4">
              <hr />
            </td>
          </tr>
<?php
          }
          if (extension_loaded("bcmath"))
            $mega = bcdiv($totsiz, 1024 * 1024, 5);
          else
            $mega = $totsiz / (1024 * 1024);
          if (extension_loaded("bcmath"))
            $giga = bcdiv($totsiz, 1024 * 1024 * 1024, 5);
          else
            $giga = $totsiz / (1024 * 1024 * 1024);
?>
          <tr>
            <td colspan="" style="text-align:right">
              <b>total</b>
            </td>
            <td style="text-align:right;">
              &nbsp;
              <b><?php echo $totobj ?></b>
              &nbsp;
            </td>
            <td style="text-align:right;">
              &nbsp;
              <b><?php printf("%.2f", $mega) ?></b>
              &nbsp;
            </td>
            <td style="text-align:right;">
              &nbsp;
              <b><?php printf("%.2f", $giga) ?></b>
              &nbsp;
            </td>
          </tr>
        </tbody>
      </table>
<?php
        }
?>
      </div>
<?php
        if ($ACL->has_right_on_base($base_id, 'manage'))
        {
          $pub_wm = $collection->get_pub_wm();
?>
          <form id="admin_adder" action="/admin/collection.php" method="post">
            <input type="hidden" name="p0"  value="<?php echo $sbas_id ?>" />
            <input type="hidden" name="p1"  value="<?php echo $base_id ?>" />
<?php echo _('admin::collection:: Gestionnaires des commandes') ?>
            <div>
<?php
          $query = new User_Query($appbox);
          $admins = $query->on_base_ids(array($base_id))
                  ->who_have_right(array('order_master'))
                  ->execute()->get_results();

          foreach ($admins as $usr_id => $user)
          {
?>
            <div><input name="admins[]" type="checkbox" value="<?php echo $usr_id ?>" id="adm_<?php echo $usr_id ?>" checked /><label for="adm_<?php echo $usr_id ?>"><?php echo $user->get_display_name(); ?></label></div>
<?php
          }
?>
          <div><?php echo _('setup:: ajouter un administrateur des commandes') ?></div>

          <input class="admin_adder"/>
          <input type="hidden" name="admins[]"/>
        <input type="submit" value="<?php echo _('boutton::valider') ?>" />
      </div>
    </form>

    <form method="post" name="manageColl" action="./collection.php" target="???" onsubmit="return(false);" ENCTYPE="multipart/form-data" >
      <input type="hidden" name="srt" value="<?php echo $parm["srt"] ?>" />
      <input type="hidden" name="ord" value="<?php echo $parm["ord"] ?>" />
      <input type="hidden" name="act" value="???" />
      <input type="hidden" name="p0"  value="<?php echo $sbas_id ?>" />
      <input type="hidden" name="p1"  value="<?php echo $base_id ?>" />
      <input type="hidden" name="sta" value="<?php echo $parm["sta"] ?>" />



<?php echo _('admin::collection:: presentation des elements lors de la diffusion aux utilisateurs externes (publications)') ?>
      <div>
        <input type='radio' name='pub_wm' onchange="sendForm('pub_wm');return(false);" <?php echo ($pub_wm == 'none' ? 'checked' : '') ?> value='none'  /> <?php echo _('admin::colelction::presentation des elements : rien') ?>
            <input type='radio' name='pub_wm' onchange="sendForm('pub_wm');return(false);" <?php echo ($pub_wm == 'wm' ? 'checked' : '') ?> value='wm'    /> <?php echo _('admin::colelction::presentation des elements : watermark') ?>
            <input type='radio' name='pub_wm' onchange="sendForm('pub_wm');return(false);" <?php echo ($pub_wm == 'stamp' ? 'checked' : '') ?> value='stamp' /> <?php echo _('admin::colelction::presentation des elements : stamp') ?>
          </div>

          <div style='margin:13px 0 3px 10px;'>
            <a href="javascript:void();return(false);" onclick="sendForm('ASKRENAMECOLL');return(false);">
              <img src="/skins/icons/edit_0.gif" style='vertical-align:middle'/>
<?php echo _('admin::base:collection: renommer la collection') ?>
            </a>
<?php
          if ($parm['act'] == "ASKRENAMECOLL")
          {
?>
            <div style='margin:13px 0 3px 10px;'>
<?php echo _('admin::base:collection: Nom de la nouvelle collection : ') ?>
              <input type="text"   name="p2" id="p2" value="<?php echo $collection->get_name(); ?>" />
            <input type="button" value="<?php echo _('boutton::envoyer') ?>" onclick="sendForm('APPLYNEWNAMECOLL');"/>
            <input type="button" value="<?php echo _('boutton::annuler') ?>" onclick="sendForm('');"/>
          </div>
<?php
          }
          else
          {
?>
            <input type="hidden" name="p2" value="<?php echo $parm["p2"] ?>" />
<?php
          }
?>
        </div>

        <div style='margin:13px 0 3px 10px;'>
          <a href="javascript:void();return(false);" onclick="enabledPublication(<?php echo($collection->is_active() ? "false" : "true") ?>);return(false);">
            <img src='/skins/icons/db-remove.png' style='vertical-align:middle'/>
<?php echo( $collection->is_active() ? _('admin::base:collection: descativer la collection') : _('admin::base:collection: activer la collection')) ?>
          </a>
        </div>
        <div style='margin:3px 0 3px 10px;'>
          <a href="javascript:void();return(false);" onclick="emptyColl('<?php p4string::MakeString($collection->get_name(), "js") ?>');return(false);">
          <img src='/skins/icons/trash.png' style='vertical-align:middle'/>
<?php echo _('admin::base:collection: vider la collection') ?>
        </a>
      </div>
      <div style='margin:3px 0 3px 10px;'>
        <a href="javascript:void();return(false);" onclick="sendForm('ASKDELETECOLL');return(false);">
          <img src='/skins/icons/delete.gif' style='vertical-align:middle'/>
<?php echo _('boutton::supprimer') ?>
        </a>
      </div>
<?php
          if ($parm['act'] == "ASKDELETECOLL")
          {
?>
            <div style='margin:13px 0 3px 10px;'>
<?php echo _('admin::collection: Confirmez vous la suppression de cette collection ?') ?><br/>
              <div style='margin:5px 0;'>
                <input type="button" value="<?php echo _('boutton::valider') ?>" onclick="sendForm('DODELETECOLL');"/>
              <input type="button" value="<?php echo _('boutton::annuler') ?>" onclick="sendForm('');"/>
            </div>
          </div>
<?php
          }
        }

?>
        <div class='logo_boxes'>
          <div style="font-size:11px;font-weight:bold;margin:0px 3px 10px 0px;">
<?php echo _('admin::base:collection: minilogo actuel') ?> :
          <?php showMsg('SENDMINILOGO') ?>
        </div>
<?php
          if ($ACL->has_right_on_base($base_id, 'manage'))
          {
            if (file_exists($registry->get('GV_RootPath') . 'config/minilogos/' . $base_id))
            {
?>
              <div style='margin:0 0 5px 0;'>
<?php echo $collection->getLogo($base_id) ?>
                <a href="javascript:void();return(false);" onclick="sendForm('DELMINILOGO');return(false);">
<?php echo _('boutton::supprimer') ?>
              </a>
            </div>
<?php
            }
            else
            {
?>
                                                          <!-- <?php echo _('admin::base:collection: aucun fichier (minilogo, watermark ...)') ?><br /><br /> -->
              <input name="newLogo" type="file" />
              <input type="button" value="<?php echo _('boutton::envoyer') ?>" onclick="sendForm('SENDMINILOGO');"/>
<?php
            }
          }
?>
        </div>
          <div class='logo_boxes'>
            <div style="font-size:11px;font-weight:bold;margin:0px 3px 10px 0px;">
                                                                                                      Watermark :
          <?php showMsg('SENDWM') ?>
        </div>
<?php
          if ($ACL->has_right_on_base($base_id, 'manage'))
          {
            if (file_exists($registry->get('GV_RootPath') . 'config/wm/' . $collection->get_base_id()))
            {
?>
              <div style='margin:0 0 5px 0;'>
<?php echo $collection->getWatermark($base_id) ?>
                <a href="javascript:void();return(false);" onclick="sendForm('DELWM');return(false);">
<?php echo _('boutton::supprimer') ?>
              </a>
            </div>
<?php
            }
            else
            {
?>
                                                                <!--  <?php echo _('admin::base:collection: aucun fichier (minilogo, watermark ...)') ?><br /><br /> -->
              <input name="newWm" type="file" />
              <input type="button" value="<?php echo _('boutton::envoyer') ?>" onclick="sendForm('SENDWM');"/>
<?php
            }
          }
?>
        </div>
          <div class='logo_boxes'>
            <div style="font-size:11px;font-weight:bold;margin:0px 3px 10px 0px;">
                                                                                                      StampLogo :
          <?php showMsg('SENDSTAMPLOGO') ?>
        </div>
<?php
          if ($ACL->has_right_on_base($base_id, 'manage'))
          {
            if (file_exists($registry->get('GV_RootPath') . 'config/stamp/' . $base_id))
            {
?>
              <div style='margin:0 0 5px 0;'>
<?php echo $collection->getStamp($base_id) ?>
                <a href="javascript:void();return(false);" onclick="sendForm('DELSTAMPLOGO');return(false);">
<?php echo _('boutton::supprimer') ?>
              </a>
            </div>
<?php
            }
            else
            {
?>
              <input name="newStampLogo" type="file" />
              <input type='button' value="<?php echo _('boutton::envoyer') ?>" onclick="sendForm('SENDSTAMPLOGO');"/>
<?php
            }
          }
?>
        </div>
          <div class='logo_boxes'>
            <div style="font-size:11px;font-weight:bold;margin:0px 3px 10px 0px;">
<?php echo _('admin::base:collection: image de presentation : ') ?>
          <?php showMsg('SENDPRESENTPICT') ?>
        </div>
<?php
          if ($ACL->has_right_on_base($base_id, 'manage'))
          {
            if (file_exists($registry->get('GV_RootPath') . 'config/presentation/' . $base_id))
            {
?>
              <div style='margin:0 0 5px 0;'>
<?php echo $collection->getPresentation($base_id) ?>
                <a href="javascript:void();return(false);" onclick="sendForm('DELPRESENTPICT');return(false);">
<?php echo _('boutton::supprimer') ?>
              </a>
            </div>
<?php
            }
            else
            {
?>
              <input name="newPresentPict" type="file" />
              <input type="button" value="<?php echo _('boutton::envoyer') ?>" onclick="sendForm('SENDPRESENTPICT');return(false);"/>
              <br/>( max : 650x200 )
<?php
            }
          }
?>
        </div>

      </form>
  </body>

</html>
