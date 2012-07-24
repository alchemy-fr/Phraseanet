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
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
/* @var $Core \Alchemy\Phrasea\Core */
$Core = require_once __DIR__ . "/../../lib/bootstrap.php";
$appbox = appbox::get_instance($Core);
$session = $appbox->get_session();
$registry = $appbox->get_registry();
require_once($registry->get('GV_RootPath') . "lib/classes/deprecated/inscript.api.php");

$request = http_request::getInstance();
$parm = $request->get_parms("action", "usr", "cgus", "date", "bas", "col");

$tab = null;

if ($parm['action'] == 'PRINT') {
    $inscriptions = giveMeBases();

    phrasea::headers();
    ?>
    <html lang="<?php echo $session->get_I18n(); ?>">
        <head>
            <style>
                p{
                    margin:15px;
                }
            </style>
        </head>
        <body>
    <?php
    foreach ($inscriptions as $sbasId => $baseInsc) {
        if (($baseInsc['CollsCGU'] || $baseInsc['Colls']) && $baseInsc['inscript'] && $sbasId == $parm['bas']) {// il y a des coll ou s'inscrire !
            $pot = false;
            if ($baseInsc['CGU']) {
                //je pr�sente la base
                echo '<h3 style="text-align:center;background:#EFEFEF;">' . phrasea::sbas_names($sbasId) . '</h3>';
                $pot = '<p>' . str_replace(array("\r\n", "\n", "\n"), "<br/>", (string) $baseInsc['CGU']) . '</p>';
            }
            $found = false;
            foreach ($baseInsc['CollsCGU'] as $collId => $collDesc) {
                if ($parm['col'] == $collId) {
                    echo '<p>' . str_replace(array("\r\n", "\n", "\n"), "<br/>", (string) $collDesc['CGU']) . '</p>';
                    $found = true;
                }
            }
        }
    }
    if ( ! $found)
        echo $pot;
    ?>
        </body>
            <?php
        }

