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
phrasea::headers();

$request = http_request::getInstance();
$parm = $request->get_parms("act", "sbas_id", "record_id", "cchd", "ccfilename");

$user = User_Adapter::getInstance($session->get_usr_id(), $appbox);

$sbas_id = $parm["sbas_id"];

$pathhd = null;
$baseurl = null;
?>
<html lang="<?php echo $session->get_I18n(); ?>">
  <head>
    <link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css,include/jslibs/jquery-ui-1.8.12/css/dark-hive/jquery-ui-1.8.12.custom.css,skins/prod/<?php echo $user->getPrefs('css') ?>/prodcolor.css" />
  </head>
  <body onload="parent.hideDwnl();">

<?php
if (!isset($_FILES["newHD"]) || $_FILES["newHD"]["tmp_name"] == "" || $_FILES["newHD"]["size"] == "" || ($_FILES["newHD"]["size"] + 0) == 0)
{
  echo '<center>', _('prod::substitution::erreur : document de substitution invalide'), '<br/><br/>';
  echo "<a href=\"#\" onClick=\"parent.hideDwnl();return false;\">" . _('boutton::fermer') . "</a>";
  die('</body></html>');
}


try
{
  $record = new record_adapter($parm['sbas_id'], $parm['record_id']);
  $record->substitute_subdef('document', new system_file($_FILES["newHD"]["tmp_name"]));
  if($parm['ccfilename'] == '1')
  {
    $record->set_original_name($_FILES["newHD"]["name"]);
  }
catch (Exception $e)
{
  echo '<center>', $e->getMessage(), '<br/><br/>';
  echo "<a href=\"#\" onClick=\"parent.hideDwnl();return false;\">" . _('boutton::fermer') . "</a>";
  die('</body></html>');
}

echo '<center>', _('prod::substitution::document remplace avec succes'), '<br/><br/>';
echo "<a href=\"#\" onClick=\"parent.hideDwnl();return false;\">" . _('boutton::fermer') . "</a>";
?>

  </body>
</html>
