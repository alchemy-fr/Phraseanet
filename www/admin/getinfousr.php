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
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
/* @var $Core \Alchemy\Phrasea\Core */
$Core = require_once __DIR__ . "/../../lib/bootstrap.php";
$appbox = appbox::get_instance($Core);

$request = http_request::getInstance();
$parm = $request->get_parms("u");
$appbox = appbox::get_instance(\bootstrap::getCore());
$user = User_Adapter::getInstance($parm['u'], $appbox);

phrasea::headers();


$info = "";
$info2 = "";

$info .= _('admin::compte-utilisateur identifiant') . " : " . $user->get_login();

$info2 .= "<br>" . _('admin::compte-utilisateur nom') . "/" . _('admin::compte-utilisateur prenom') . " : ";
$info2 .= $user->get_display_name();
if ($user->get_email()) {
    $info2 .= "<br>" . _('admin::compte-utilisateur email') . " : " . $user->get_email();
}
if ($user->get_tel()) {
    $info2 .= "<br>" . _('admin::compte-utilisateur telephone') . " : " . $user->get_tel();
}
if ($user->get_job()) {
    $info2 .= "<br>" . _('admin::compte-utilisateur poste') . " : " . $user->get_job();
}
if ($user->get_company()) {
    $info2 .= "<br>" . _('admin::compte-utilisateur societe') . " : " . $user->get_company();
}
if ($user->get_position()) {
    $info2 .= "<br>" . _('admin::compte-utilisateur activite') . " : " . $user->get_position();
}
$info2 .= "<br><div style='background-color:#777777'><font color=#FFFFFF>" . _('admin::compte-utilisateur adresse') . "</font>";
$info2 .= "<br>" . $user->get_address() . "<br>" . $user->get_zipcode() . " " . $user->get_city();
$info2 .= "</div>";
if ($info2 != "")
    $info .= "<font color=#EEEEEE>" . $info2 . "</font>";
$info = str_replace("<br><br>", "<br>", $info);
$info = str_replace("\n", "", $info);
$info = str_replace("\r", "", $info);
?>
<script type="text/javascript">
    parent.usrDesc[<?php echo $parm["u"] ?>] = "<?php echo p4string::MakeString($info, "js") ?>";
    parent.redrawUsrDesc(<?php echo $parm["u"] ?>);
</script>
