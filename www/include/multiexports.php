<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */

require_once __DIR__ . "/../../lib/bootstrap.php";
$app = new Application();

$user = $app['phraseanet.user'];

$request = http_request::getInstance();
$parm = $request->get_parms("lst", "SSTTID", "story");

$gatekeeper = gatekeeper::getInstance($app);
$gatekeeper->require_session();

if ($app['phraseanet.registry']->get('GV_needAuth2DL') && $user->is_guest()) {
    ?>
    <script>
        parent.hideDwnl();
        parent.login('{act:"dwnl",lst:"<?php echo $parm['lst'] ?>",SSTTID:"<?php echo $parm['SSTTID'] ?>"}');
    </script>
    <?php
    exit();
}


$download = new set_export($app, $parm['lst'], $parm['SSTTID'], $parm['story']);
$user = $app['phraseanet.user'];

echo $app['twig']->render('common/dialog_export.html.twig', array(
    'download'             => $download,
    'ssttid'               => $parm['SSTTID'],
    'lst'                  => $download->serialize_list(),
    'user'                 => $user,
    'default_export_title' => $app['phraseanet.registry']->get('GV_default_export_title'),
    'choose_export_title'  => $app['phraseanet.registry']->get('GV_choose_export_title')
));



