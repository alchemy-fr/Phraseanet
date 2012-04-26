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
$session = $appbox->get_session();
$registry = $appbox->get_registry();

$user = $Core->getAuthenticatedUser();

$request = http_request::getInstance();
$parm = $request->get_parms("lst", "SSTTID", "story");

$gatekeeper = gatekeeper::getInstance($Core);
$gatekeeper->require_session();

if ($registry->get('GV_needAuth2DL') && $user->is_guest()) {
    ?>
    <script>
        parent.hideDwnl();
        parent.login('{act:"dwnl",lst:"<?php echo $parm['lst'] ?>",SSTTID:"<?php echo $parm['SSTTID'] ?>"}');
    </script>
    <?php
    exit();
}


$download = new set_export($parm['lst'], $parm['SSTTID'], $parm['story']);
$user = User_Adapter::getInstance($session->get_usr_id(), $appbox);

$core = \bootstrap::getCore();
$twig = $core->getTwig();

echo $twig->render('common/dialog_export.twig', array(
    'download'             => $download,
    'ssttid'               => $parm['SSTTID'],
    'lst'                  => $download->serialize_list(),
    'user'                 => $user,
    'default_export_title' => $registry->get('GV_default_export_title'),
    'choose_export_title'  => $registry->get('GV_choose_export_title')
));



