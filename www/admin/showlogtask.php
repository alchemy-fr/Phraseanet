<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\Finder\Finder;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
/* @var $Core \Alchemy\Phrasea\Core */
$Core = require_once __DIR__ . "/../../lib/bootstrap.php";
$appbox = appbox::get_instance($Core);
$session = $appbox->get_session();

$request = http_request::getInstance();
$parm = $request->get_parms('fil', 'id', 'clr');
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
        <?php
        $registry = $appbox->get_registry();
        $logdir = p4string::addEndSlash($registry->get('GV_RootPath') . 'logs');

        $name = str_replace('..', '', $parm['fil']);

        $rname = $name;
        if ($parm['id']) {
            $rname .= '_' . $parm['id'];
        }
        $rname = '/' . $rname . '((\.log)|(-.*\.log))$/';

        $finder = new Finder();
        $finder
            ->files()->name($rname)
            ->in($logdir)
            ->date('> now - 1 days')
            ->sortByModifiedTime()
            ->sort(function($a, $b) {
                    return -1;
                });

        $found = false;
        foreach ($finder->getIterator() as $file) {
            if ($parm['clr'] == $file->getFilename()) {
                file_put_contents($file->getRealPath(), '');
                $found = true;
            }
        }
        if ($found) {
            return phrasea::redirect(sprintf("/admin/showlogtask.php?fil=%s&id=%s"
                        , urlencode($parm['fil'])
                        , urlencode($parm['id']))
            );
        }

        $found = false;
        foreach ($finder->getIterator() as $file) { {
                printf("<h4>%s\n", $file->getRealPath());
                printf("&nbsp;<a href=\"/admin/showlogtask.php?fil=%s&id=%s&clr=" . urlencode($file->getFilename()) . "\">" . _('Clear') . "</a>"
                    , urlencode($parm['fil'])
                    , urlencode($parm['id']));
                print("</h4>\n<pre>\n");
                print(htmlentities(file_get_contents($file->getRealPath())));
                print("</pre>\n");
            }
            $found = true;
        }
        if ( ! $found) {
            printf("<h4>file <b>%s</b> does not exists</h4>\n", $logdir . $name);
        }
        ?>
    </body>
</html>
