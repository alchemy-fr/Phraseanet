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

phrasea::headers();

$request = http_request::getInstance();
$parm = $request->get_parms("lst", "ACT", "subjectmail", "textmail", "lstusr", "nameBask", "ccmail", "rotation", "chu", "CHIM", "chimlist");

$user = User_Adapter::getInstance($session->get_usr_id(), $appbox);

?>
<hml>
  <head>
    <link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css,include/jslibs/jquery-ui-1.8.17/css/dark-hive/jquery-ui-1.8.17.custom.css,skins/prod/<?php echo $user->getPrefs('css') ?>/prodcolor.css" />
    <script type="text/javascript" src="/include/jslibs/jquery-1.7.1.js"></script>
    <script type="text/javascript" src="/include/jslibs/jquery-ui-1.8.17/js/jquery-ui-1.8.17.custom.min.js"></script>
    <script type="text/javascript" src="/include/minify/g=modalBox"></script>
  </head>
  <body>

    <?php
    if ($parm["ACT"] == "SEND")
    {

      if ($parm['rotation'] == null)
        exit('Choose a rotation !');

      $lst = explode(";", $parm["lst"]);

      $rot_value = in_array($parm['rotation'], array('-90', '90', '180')) ? $parm['rotation'] : 90;
      foreach ($lst as $basrec)
      {
        $basrec2 = explode("_", $basrec);

        try
        {
          $record = new record_adapter($basrec2[0], $basrec2[1]);
          $record->rotate_subdefs($rot_value);
        }
        catch (Exception $e)
        {

        }
      }
    ?>
      <script type="text/javascript">
        parent.hideDwnl();
      </script>
      <div style="text-align:center;"><input onclick="parent.hideDwnl();" value="<?php echo _('boutton::fermer') ?>" type="button" class="input-button" /></div>
<?php
    }
?>
  </body>
