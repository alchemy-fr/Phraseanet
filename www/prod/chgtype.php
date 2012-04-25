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
$parm = $request->get_parms("ACT", "typelst");

$user = $Core->getAuthenticatedUser();

if ($parm['ACT'] == 'SEND') {
    $lst = $parm['typelst'];

    $lst = explode(';', $lst);
    foreach ($lst as $el) {
        if (strlen($el) > 0) {
            $el = explode('=', $el);
            if (strpos($el[0], 'img') !== false) {
                $basrec = explode('_', substr($el[0], 3));
                try {
                    $record = new record_adapter($basrec[0], $basrec[1]);
                    $record->set_type($el[1]);
                    unset($record);
                } catch (Exception $e) {

                }
            }
        }
    }
    ?>
    <html lang="<?php echo $session->get_I18n(); ?>">
        <head>
            <link type="text/css" rel="stylesheet" href="/include/minify/f=skins/prod/<?php echo $user->getPrefs('css') ?>/prodcolor.css" />
        </head>
        <body onload="parent.hideDwnl();">
    <?php
    echo '<div style="font-size:11px;text-align:center;">';
    echo '<a href="#" onclick="parent.hideDwnl();">', _('boutton::fermer'), '</a>';
    echo '</div>';
    ?>
        </body>
    </html>
            <?php
        }
