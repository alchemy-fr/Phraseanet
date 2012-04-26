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

$request = http_request::getInstance();
$parm = $request->get_parms('fil', 'log', 'id', 'act');
?>
<html lang="<?php echo($session->get_I18n()); ?>">
    <head>
        <style>
            * {font-family: monospace}
            BODY {margin: 0px; padding: 0px}
            H1 { font-size: 18px; background-color:#CCCCCC; padding: 0px}
            A { padding: 3px; color: #000000 }
            A.current {background-color: #ffffff}
            PRE {padding-left: 3px; padding-right: 3px}
        </style>
    </head>
    <body>
        <h1>
            logfile :
            <?php
            foreach (array('l' => 'log', 'o' => 'stdout', 'e' => 'stderr') as $k => $v) {
                $cls = '';
                if ($k == $parm['log'])
                    $cls = 'current';
                printf("<a class=\"%s\" href=\"/admin/showlogtask.php?fil=%s&log=%s&id=%s\">(%s)</a>\n"
                    , $cls
                    , urlencode($parm['fil'])
                    , urlencode($k)
                    , urlencode($parm['id'])
                    , $v);
            }
            ?>
        </h1>
        <?php
        $registry = $appbox->get_registry();
        $logdir = p4string::addEndSlash($registry->get('GV_RootPath') . 'logs');
        $logfile = $logdir . $parm['fil'];
        if ($parm['log'])
            $logfile .= '_' . $parm['log'];
        if ($parm['id'])
            $logfile .= '_' . $parm['id'];
        $logfile .= '.log';

        if (file_exists($logfile)) {
            if ($parm['act'] == 'CLR') {
                file_put_contents($logfile, '');

                return phrasea::redirect(sprintf("/admin/showlogtask.php?fil=%s&log=%s&id=%s"
                            , urlencode($parm['fil'])
                            , urlencode($parm['log'])
                            , urlencode($parm['id']))
                );
            } else {
                printf("<h4>%s\n", $logfile);
                printf("&nbsp;<a href=\"/admin/showlogtask.php?fil=%s&log=%s&id=%s&act=CLR\">effacer</a>\n"
                    , urlencode($parm['fil'])
                    , urlencode($parm['log'])
                    , urlencode($parm['id']));
                print("</h4>\n<pre>\n");
                print(htmlentities(file_get_contents($logfile)));
                print("</pre>\n");
            }
        } else {
            printf("<h4>file <b>%s</b> does not exists</h4>\n", $logfile);
        }
        ?>
    </body>
</html>
