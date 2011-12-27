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
phrasea::headers();

$request = http_request::getInstance();
$parm = $request->get_parms(
        'lst'
        , 'ACT'
        , 'SSTTID'
);
$usr_id = $session->get_usr_id();
$user = User_Adapter::getInstance($usr_id, $appbox);

if ($parm['ACT'] === null)
{
  ?>
  <html lang="<?php echo $session->get_I18n(); ?>">
    <head>
      <base target="_self">
      <link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css,include/jslibs/jquery-ui-1.8.12/css/dark-hive/jquery-ui-1.8.12.custom.css" />
      <link type="text/css" rel="stylesheet" href="/include/minify/f=skins/prod/<?php echo $user->getPrefs('css') ?>/prodcolor.css" />
      <script type="text/javascript" src="/include/minify/f=include/jslibs/jquery-1.5.2.js,include/jquery.p4.modal.js"></script>
      <script type="text/javascript" src="/include/jslibs/jquery-ui-1.8.12/js/jquery-ui-1.8.12.custom.min.js"></script>
    </head>
    <?php
    $nb_HD_Substit = 0;
    $nb_Thumb_Substit = 0;

    if ($parm['SSTTID'] != '' && ($parm['lst'] == null || $parm['lst'] == ''))
    {
      $em = $Core->getEntityManager();
      $repository = $em->getRepository('\Entities\Basket');

      /* @var $repository \Repositories\BasketRepository */

      $Basket = $repository->findUserBasket(
              $Core->getRequest()->get('SSTTID')
              , $Core->getAuthenticatedUser()
      );

      foreach ($Basket->getElements() as $basket_element)
      {

        $parm['lst'] .= $basket_element->getRecord()->get_serialize_key() . ';';
      }
    }

    $seeOngChgDoc = FALSE;

    $tmpbasrec = explode(';', $parm['lst']);

    $okbrec = array();

    //on enleve les reg et on prend les fils
    foreach ($tmpbasrec as $k => $basrec)
    {
      $basrec = explode('_', $basrec);

      if (count($basrec) !== 2)
        continue;

      try
      {
        $record = new record_adapter($basrec[0], $basrec[1]);
      }
      catch (Exception_Record_AdapterNotFound $e)
      {
        unset($tmpbasrec[$k]);
        continue;
      }

      if (!$record->is_grouping())
        continue;

      unset($tmpbasrec[$k]);

      foreach ($record->get_children() as $child)
      {
        $tmpbasrec[] = $child->get_sbas_id() . '_' . $child->get_base_id();
      }
    }

    $okbrec = liste::filter($tmpbasrec);

    $parm['lst'] = implode(';', $okbrec);
    $tmpbasrec = $okbrec;



//  $tmpbasrec = liste::addtype($tmpbasrec);

    foreach ($tmpbasrec as $rec)
    {
      $rec = explode('_', $rec);
      $record = new record_adapter($rec[0], $rec[1]);

      $tmpsd = $record->get_subdefs();

      if (isset($tmpsd['document']) && $tmpsd['document']->is_substituted())
        $nb_HD_Substit++;

      if (isset($tmpsd['thumbnail']) && $tmpsd['thumbnail']->is_substituted())
        $nb_Thumb_Substit++;
    }


    if (count($tmpbasrec) == 1)
    {
      $seeOngChgDoc = TRUE;
      foreach ($tmpbasrec as $basrec)
        $basrec2 = explode('_', $basrec);
    }
    ?>
    <body class="bodyprofile">
      <div class="boxCloser" onclick="parent.hideDwnl();"><?php echo _('boutton::fermer') ?></div>
      <div id="tabs">
        <ul>
          <li><a href="#subdefs"><?php echo _('prod::tools: regeneration de sous definitions') ?></a></li>
          <li><a href="#image"><?php echo _('prod::tools: outils image') ?></a></li>
          <?php
          if ($seeOngChgDoc && $registry->get('GV_seeOngChgDoc'))
          {
            ?>
            <li><a href="#hdsub"><?php echo _('prod::tools: substitution HD') ?></a></li>
            <?php
          } if ($seeOngChgDoc && $registry->get('GV_seeNewThumb'))
          {
            ?>
            <li><a href="#sdsub"><?php echo _('prod::tools: substitution de sous definition') ?></a></li>
            <?php
          } if ($registry->get('GV_exiftool') != "" && count($tmpbasrec) == 1)
          {
            ?>
            <li><a href="#exiftool"><?php echo _('prod::tools: meta-datas') ?></a></li>
          <?php } ?>
        </ul>

        <div id="subdefs" class="tabBox">
          <form name="formsubdef" id="formsubdef" target="_self" action="newimg.php" method="post">
            <?php
            if ($nb_Thumb_Substit > 0)
            {
              ?>
              <div style="color:#A00;"><?php echo _('prod::tools:regeneration: Attention, certain documents ont des sous-definitions substituees.'); ?></div>
              <input type="checkbox" name="ForceThumbSubstit" value="1" id="FTS"><label for="FTS"><?php echo _('prod::tools:regeneration: Forcer la reconstruction sur les enregistrements ayant des thumbnails substituees.'); ?></label><br/>
              <?php
            }
            else
            {
              ?>
              <input type="hidden" name="ForceThumbSubstit" value="1">
              <?php
            }
            ?>
            <div style="margin:5px 5px 5px 10px;"><h6 style="margin:0;"><?php echo _('prod::tools:regeneration: Reconstruire les sous definitions'); ?> :</h6>

              <select name="rebuild" style="border:1px solid black;">
                <option selected="selected" value="none"><?php echo _('prod::tools: option : recreer aucune les sous-definitions'); ?></option>
                <option value="all"><?php echo _('prod::tools: option : recreer toutes les sous-definitions'); ?></option>
              </select>

              <input type="hidden" name="ACT" value="SEND" />
              <input type="hidden" name="lst" value="<?php echo implode(';', $tmpbasrec); ?>" />
              <div style="text-align:center;margin:10px 0;">
                <input type="submit" onclick="parent.hideDwnl();" class="input-button" value="<?php echo _('boutton::valider') ?>" />
                <input type="button" class="input-button" value="<?php echo _('boutton::annuler') ?>" onclick="parent.hideDwnl();" />
              </div>
            </div>
          </form>
        </div>
        <div id="image" class="tabBox">
          <form name="formpushdoc" action="rotate.php" method="post">
            <?php echo _('prod::tools::image: Cette action n\'a d\'effet que sur les images :'); ?><br/>
            <input type="radio" name="rotation" id="ROTA_90" value="90"><label for="ROTA_90"><?php echo _('prod::tools::image: rotation 90 degres horaire'); ?></label>
            <br />
            <input type="radio" name="rotation" id="ROTA_C90" value="-90"><label for="ROTA_C90"><?php echo _('prod::tools::image rotation 90 degres anti-horaires') ?></label>

            <input type="hidden" name="ACT" value="SEND" />
            <input type="hidden" name="lst" value="<?php echo implode(';', $tmpbasrec); ?>" />
            <input type="hidden" name="element" value="" />
            <input type="hidden" name="cchd" value="" />
            <div style="text-align:center;margin:10px 0;">
              <input type="button" class="input-button" value="<?php echo _('boutton::valider') ?>" onclick="document.forms.formpushdoc.submit();" />
              <input type="button" class="input-button" value="<?php echo _('boutton::annuler') ?>" onclick="parent.hideDwnl();" />
            </div>
          </form>
        </div>

        <?php
        if ($seeOngChgDoc && $registry->get('GV_seeOngChgDoc'))
        {
          ?>

          <div id="hdsub" class="tabBox">
            <br />
            <form name="formchgHD" action="./chghddocument.php" enctype="multipart/form-data" method="post">
              <input type="hidden" name="MAX_FILE_SIZE" value="20000000" />
              <input name="newHD" type="file" />
              <br /><br />
              <input type="checkbox" name="ccfilename" id="CCFNALP" value="1"><label for="CCFNALP"><?php echo _('prod::tools:substitution : mettre a jour le nom original de fichier apres substitution') ?></label>

              <input type="hidden" name="ACT" value="SEND" />
              <input type="hidden" name="sbas_id" value="<?php echo $basrec2[0] ?>" />
              <input type="hidden" name="record_id" value="<?php echo $basrec2[1] ?>" />
              <div style="text-align:center;margin:10px 0;">
                <input type="button" class="input-button" value="<?php echo _('boutton::valider') ?>" onclick="document.forms.formchgHD.submit();" />
                <input type="button" class="input-button" value="<?php echo _('boutton::annuler') ?>" onclick="parent.hideDwnl();" />
              </div>
            </form>
          </div>
        <?php } ?>

        <?php
        if ($seeOngChgDoc && $registry->get('GV_seeNewThumb'))
        {
          ?>
          <div id="sdsub"  class="tabBox">
            <br />
            <form name="formchgthumb" action="./chgthumb.php" enctype="multipart/form-data" method="post">
              <input type="hidden" name="MAX_FILE_SIZE" value="20000000" />
              <input name="newThumb" type="file" />

              <input type="hidden" name="ACT" value="SEND" />
              <input type="hidden" name="sbas_id" value="<?php echo $basrec2[0] ?>" />
              <input type="hidden" name="record_id" value="<?php echo $basrec2[1] ?>" />
              <input type="hidden" name="element" value="" />
              <div style="text-align:center;margin:10px 0;">
                <input type="button" class="input-button" value="<?php echo _('boutton::valider') ?>" onclick="document.forms.formchgthumb.submit();" />
                <input type="button" class="input-button" value="<?php echo _('boutton::annuler') ?>" onclick="parent.hideDwnl();" />
              </div>
            </form>
          </div>
          <?php
        }
        if ($registry->get('GV_exiftool') != "" && count($tmpbasrec) == 1)
        {
          ?>
          <div id="exiftool"  class="tabBox">

            <?php
            $list = explode(';', $parm['lst']);

            foreach ($list as $rec)
            {
              unset($out);
              $rec2 = explode('_', $rec);
              if (sizeof($rec2) == 2)
              {
                $sbas_id = $rec2[0];
                $record = new record_adapter($sbas_id, $rec2[1]);
                try
                {
                  $tmpsd = $record->get_subdef('document');
                }
                catch (Exception $e)
                {
                  continue;
                }

                $file = $tmpsd->get_pathfile();

                echo '<div style="with:100%;text-align:center;font-size:12px;font-weight:bold;">Record ' . $rec2[1] . "</div><br/><br/>";
                $thumbnail = $record->get_thumbnail();
                echo '<img src="' . $thumbnail->get_url() . '" width="' . $thumbnail->get_width() . '" height="' . $thumbnail->get_height() . '" />';

                echo '<hr/>';
                print("<b>HTML</b><br/>\n");
                $cmd = $registry->get('GV_exiftool') . ' -h ' . escapeshellarg($file) . '';
                exec($cmd, $out);
                foreach ($out as $liout)
                {
                  if (strpos($liout, '<tr><td>Directory') === false)
                    echo $liout;
                }
                echo '<hr/>';

                print("<b>XML</b><br/>\n");
                $out = "";
                $cmd = $registry->get('GV_exiftool') . '  -X -n -fast ' . escapeshellarg($file) . '';
                exec($cmd, $out);
                foreach ($out as $liout)
                {
                  echo "<pre>" . htmlentities($liout) . "\r\n</pre>";
                }
                echo '<hr/>';

                flush();
              }
            }
            ?>
            <br /><br />
            <br /><br />
          </div>
          <?php
        }
        ?>
      </div>
    </body>
  </html>
  <?php
}


############## END ACT STEP2 ######################
?>
