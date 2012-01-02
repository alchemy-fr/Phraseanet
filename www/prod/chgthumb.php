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
phrasea::headers();

$request = http_request::getInstance();
$parm = $request->get_parms("act", "sbas_id", "record_id");

$sbas_id = $parm["sbas_id"];
$user = User_Adapter::getInstance($session->get_usr_id(), $appbox);

$pathThumb = null;
$baseurl = null;
$size = null;
?>
<html lang="<?php echo $session->get_I18n(); ?>">
  <head>
    <link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css,include/jslibs/jquery-ui-1.8.12/css/dark-hive/jquery-ui-1.8.12.custom.css,skins/prod/<?php echo $user->getPrefs('css') ?>/prodcolor.css" />
  </head>
  <body>

<?php
if (!isset($_FILES["newThumb"]) || $_FILES["newThumb"]["tmp_name"] == "" || $_FILES["newThumb"]["size"] == "" || ($_FILES["newThumb"]["size"] + 0) == 0)
{
  echo "<center>", _('prod::substitution::erreur : impossible d\'ajouter ce document'), "<br><br>";
  echo "<a onClick=\"parent.hideDwnl();\">", _('boutton::fermer'), "</a>";
  die('</body></html>');
}

$message = _('prod::substitution::document remplace avec succes');
try
{

  $tmp_file = $registry->get('GV_RootPath') . 'tmp/' . $_FILES["newThumb"]['name'];
  rename($_FILES["newThumb"]["tmp_name"], $tmp_file);

  $sbas_id = $parm['sbas_id'];
  $record = new record_adapter($sbas_id, $parm['record_id']);
  $record->substitute_subdef('thumbnail', new system_file($tmp_file));
}
catch (Exception $e)
{
  $message = $e->getMessage();
}
printf("<center>%s<br><br>", $message);
echo "<a onClick=\"parent.hideDwnl();\">", _('boutton::fermer'), "</a>";
?>
  </body>
</html>
