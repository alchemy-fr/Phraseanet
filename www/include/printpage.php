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
require_once dirname(__FILE__) . "/../../lib/bootstrap.php";
$appbox = appbox::get_instance();
$session = $appbox->get_session();
$registry = $appbox->get_registry();
require($registry->get('GV_RootPath') . 'lib/vendor/fpdf/fpdf.php');



###########################
###########################
# Pour Affichage du viewname dans le bandeau en haut a gauche
$printViewName = FALSE; // viewname = base
$printlogosite = TRUE;

###########################

$presentationpage = false;

$request = http_request::getInstance();
$parm = $request->get_parms("lst"
                , "ACT"
                , "lay"
                , "callclient"
                , "SSTTID"
);


$gatekeeper = gatekeeper::getInstance();
$gatekeeper->require_session();

$usr_id = $session->get_usr_id();

if ($parm["ACT"] != "PRINT")
  phrasea::headers();

// les variables
$tot_record = 0;
$tot_hd = 0;
$tot_prev = 0;



$regid = NULL;
$printReg = FALSE;
$child = 0;

############## ACT STEP2 ######################
if ($parm["ACT"] === null)
{
  $user = User_Adapter::getInstance($usr_id, $appbox);
  $ACL = $user->ACL();

  if ($parm["SSTTID"] != "")
  {
    $basket = basket_adapter::getInstance($appbox, $parm['SSTTID'], $usr_id);
    foreach ($basket->get_elements() as $basket_element)
    {
      $parm["lst"] .= $basket_element->get_record()->get_serialize_key() . ";";
    }
  }

  $lstTable = explode(";", $parm["lst"]);

  $unsets = array();
  foreach ($lstTable as $k => $br)
  {
    $br = explode('_', $br);
    if (count($br) == 2)
    {
      try
      {
        $record = new record_adapter($br[0], $br[1]);
      }
      catch (Exception $e)
      {
        continue;
      }
      if ($record->is_grouping())
      {
        foreach ($record->get_children() as $child)
        {
          $lstTable[] = implode('_', $child);
        }
        $unsets[] = $k;
      }
    }
  }

  foreach ($unsets as $u)
    unset($lstTable[$u]);

  $okbrec = liste::filter($lstTable);

  $lstTable = $okbrec;

  $parm['lst'] = implode(';', $lstTable);



  foreach ($lstTable as $basrec)
  {
    $basrec = explode("_", $basrec);
    if (!$basrec || count($basrec) !== 2)
      continue;
    $tot_record++;
    $sbas_id = $basrec[0];
    $record = new record_adapter($sbas_id, $basrec[1]);
    $base_id = $record->get_base_id();
    $sd = $record->get_subdefs();

    if (isset($sd['document']) && $ACL->has_right_on_base($base_id, 'candwnldhd'))
    {
      $tot_hd++;
    }
    if (isset($sd['preview']) && $ACL->has_right_on_base($base_id, 'candwnldpreview'))
    {
      $tot_prev++;
    }
  }
  ?>

  <html lang="<?php echo $session->get_I18n(); ?>">
    <head>

      <base target="_self">
      <link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css,include/jslibs/jquery-ui-1.8.12/css/dark-hive/jquery-ui-1.8.12.custom.css,skins/prod/<?php echo $user->getPrefs('css') ?>/prodcolor.css" />
      <script type="text/javascript">
        function doPrint()
        {
  <?php
  $zurl = 'printpage_pdf.php?ACT=LOAD&form=formprintpage&callclient=' . $parm['callclient'];
  ?>
      window.open("<?php echo $zurl ?>", "_blank", "width=600, height=500, directories=no, location=no, menubar=no, toolbar=no, help=no, status=no, resizable=yes, scrollbars=no");

      parent.hideDwnl();

    }
      </script>
      <script type="text/javascript" src="/include/minify/f=include/jslibs/jquery-1.5.2.js,include/jquery.p4.modal.js"></script>
      <script type="text/javascript" src="/include/jslibs/jquery-ui-1.8.12/js/jquery-ui-1.8.12.custom.min.js"></script>
    </head>
    <body class="bodyprofile">
      <div class="boxCloser" onclick="parent.hideDwnl();"><?php echo _('boutton::fermer') ?></div>
      <div id="tabs">
        <ul>
          <li><a href="#print"><?php echo _('action : print') ?></a></li>
        </ul>
        <div id="print" class="tabBox" >

          <?php
          if ($printReg)
          {
            echo sprintf(_('export:: export du regroupement : %d fichiers'), $child);
            ?><br/><br/><?php
      }
      ?>
          <form name="formprintpage" action="" onsubmit="return(false);">
            <?php
            if ($tot_record > 0)
            {
              if ($tot_prev > 0)
              {
                ?>
                <u><?php echo _('phraseanet:: preview') ?></u><br/>
                <?php
                if ($tot_record > 1)
                {
                  ?>
                  <input type="radio" name="lay" value="preview" id="RADI_PRE_LAB" /><label for="RADI_PRE_LAB"><?php echo _('print:: image de choix seulement') ?></label><br/>
                  <?php
                }
                ?>

                <input type="radio" name="lay" value="previewCaption" id="RADI_PRE_CAP" /><label for="RADI_PRE_CAP"><?php echo _('print:: image de choix et description') ?></label><br/>
                <?php
                if ($tot_record > 1)
                {
                  ?>
                  <input type="radio" name="lay" value="previewCaptionTdm" id="RADI_PRE_TDM" /><label for="RADI_PRE_TDM"><?php echo _('print:: image de choix et description avec planche contact'); ?></label><br/>
                  <?php
                }

                if ($tot_prev != $tot_record)
                  printf("&nbsp;<small>*( %s&nbsp;preview(s)&nbsp;/&nbsp;%s&nbsp;)</small>", $tot_prev, $tot_record);
              }
              ?>
              <br /><br /><u><?php echo _('print:: imagette'); ?></u>  <br/>
              <?php
              if ($tot_record > 1)
              {
                ?>
                <input type="radio" name="lay" value="thumbnailList" id="RADI_PRE_THUM" /><label for="RADI_PRE_THUM"><?php echo _('print:: liste d\'imagettes'); ?></label><br/>
                <?php
              }
              ?>
              <input type="radio" name="lay" checked value="thumbnailGrid" id="RADI_PRE_THUMGRI" /><label for="RADI_PRE_THUMGRI"><?php echo _('print:: planche contact (mosaique)'); ?></label><br/>
              <?php
            }
            else
            {
              ?>
              <?php echo _('export:: erreur : aucun document selectionne') ?>
              <?php
            }
            ?>
            <input type="hidden" name="lst" value="<?php echo $parm["lst"] ?>" />
          </form>
          <div style="text-align:center;margin-top : 10px;">
            <input type="button" class="input-button" value="<?php echo _('boutton::imprimer'); ?>" onclick="doPrint();" />
            <input type="button" class="input-button" value="<?php echo _('boutton::annuler'); ?>" onclick="parent.hideDwnl();" />
          </div>
        </div>
      </div>
    </body>
  </html>
  <?php
}


