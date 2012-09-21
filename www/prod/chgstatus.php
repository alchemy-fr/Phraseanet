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
use Alchemy\Phrasea\Core\Configuration;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */

require_once __DIR__ . "/../../lib/bootstrap.php";
$app = new Application();
$appbox = $app['phraseanet.appbox'];
phrasea::headers();

$user = $app['phraseanet.user'];

$request = http_request::getInstance();
$parm = $request->get_parms(
    "act"
    , "lst"
    , "mska"
    , "msko"
    , "chg_status_son"
    , 'dlgW'
    , 'dlgH'
);
?>
<html lang="<?php echo $app['locale.I18n']; ?>">
    <head>
        <link type="text/css" rel="stylesheet" href="/include/minify/f=skins/prod/<?php echo $user->getPrefs('css') ?>/prodcolor.css" />
        <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
        <script type="text/javascript">

            function loaded()
            {
                parent.hideDwnl();
            }

        </script>
    </head>

    <body onload="loaded();" style="overflow:hidden; padding:0px; margin:0px;">
        <?php
        if ($parm["act"] == "START" || $parm["act"] == "WORK") {
            $ACL = $app['phraseanet.user']->ACL();

            if ($parm["act"] == "WORK") {
                if ($parm["chg_status_son"] == "1") {
                    $lst = explode(";", $parm["lst"]);
                    foreach ($lst as $basrec) {
                        $basrec = explode('_', $basrec);
                        $record = new record_adapter($app, $basrec[0], $basrec[1]);

                        if ($record->is_grouping()) {
                            foreach ($record->get_children() as $oneson) {
                                if ( ! $ACL->has_right_on_base($oneson->get_base_id(), 'chgstatus'))
                                    continue;
                                if ($parm["lst"] != "" && $parm["lst"] != null)
                                    $parm["lst"].=",";
                                $parm["lst"] .= ';' . $oneson->get_sbas_id() . '_' . $oneson->get_record_id();
                            }
                        }
                    }
                }

                $mska = $msko = null;

                $sbA = explode(';', $parm["mska"]);
                $sbO = explode(';', $parm["msko"]);

                foreach ($sbA as $sbAnd) {
                    $sbAnd = explode('_', $sbAnd);
                    $mska[$sbAnd[0]] = $sbAnd[1];
                }
                foreach ($sbO as $sbOr) {
                    $sbOr = explode('_', $sbOr);
                    $msko[$sbOr[0]] = $sbOr[1];
                }

                $lst = explode(";", $parm["lst"]);
                $maj = 0;
                foreach ($lst as $basrec) {
                    $basrec = explode('_', $basrec);
                    if (count($basrec) !== 2)
                        continue;

                    try {
                        $record = new record_adapter($app, $basrec[0], $basrec[1]);
                        $base_id = $record->get_base_id();
                        if (isset($mska[$basrec[0]]) && isset($msko[$basrec[0]])) {
                            $record = new record_adapter($app, $basrec[0], $basrec[1]);
                            $status = $record->get_status();
                            $status = databox_status::operation_and($app, $status, $mska[$basrec[0]]);
                            $status = databox_status::operation_or($app, $status, $msko[$basrec[0]]);
                            $record->set_binary_status($status);

                            $app['phraseanet.logger']($record->get_databox())
                                ->log($record, Session_Logger::EVENT_STATUS, '', '');

                            $maj ++;
                            unset($record);
                        }
                    } catch (Exception $e) {

                    }
                }
                ?>
                <div style="font-size:11px;text-align:center;">
                <?php echo sprintf(_('prod::proprietes : %d documents modifies'), $maj) ?><br>
                    <a href="#" onclick="parent.hideDwnl();"><?php echo _('boutton::fermer') ?></a>
                </div>
                <?php
            }
        }
        ?>
    </body>
</html>
