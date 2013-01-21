<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
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

require_once __DIR__ . "/../../vendor/autoload.php";

$app = new Application();
$usr_id = $app['phraseanet.user']->get_id();


$request = http_request::getInstance();
$parm = $request->get_parms(
    'act', 'sbas', 'presetid', 'title', 'f', 'debug'
);

$ret = array('parm' => $parm);

switch ($parm['act']) {
    case 'DELETE':
        $sql = 'DELETE FROM edit_presets
            WHERE edit_preset_id = :editpresetid AND usr_id = :usr_id';

        $params = array(
            ':editpresetid' => $parm['presetid'],
            ':usr_id'       => $usr_id
        );

        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $ret['html'] = xlist($app, $parm['sbas'], $usr_id);
        break;
    case 'SAVE':
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->standalone = true;
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes" ?><edit_preset>' . $parm['f'] . '</edit_preset>';
        $dom->loadXML($xml);

        $sql = 'INSERT INTO edit_presets (creation_date, sbas_id, usr_id, title, xml)
            VALUES (NOW(), :sbas_id, :usr_id, :title, :presets)';

        $params = array(
            ':sbas_id' => $parm['sbas']
            , ':usr_id'  => $usr_id
            , ':title'   => $parm['title']
            , ':presets' => $dom->saveXML()
        );

        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute($params);

        $ret['html'] = xlist($app, $parm['sbas'], $usr_id);
        break;
    case 'LIST':
        $ret['html'] = xlist($app, $parm['sbas'], $usr_id);
        break;
    case "LOAD":
        $sql = 'SELECT edit_preset_id, creation_date, title, xml
            FROM edit_presets
            WHERE edit_preset_id = :edit_preset_id';

        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':edit_preset_id' => $parm['presetid']));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $fields = array();
        if ($row && ($sx = simplexml_load_string($row['xml']))) {
            foreach ($sx->fields->children() as $fn => $fv) {
                if ( ! array_key_exists($fn, $fields))
                    $fields[$fn] = array();
                $fields[$fn][] = trim($fv);
            }
        }

        $ret['fields'] = $fields;
        break;
}

function xlist(Application $app, $sbas_id, $usr_id)
{
    $conn = connection::getPDOConnection($app);

    $html = '';
    $sql = 'SELECT edit_preset_id, creation_date, title, xml FROM edit_presets
          WHERE usr_id = :usr_id AND sbas_id = :sbas_id
          ORDER BY creation_date ASC';

    $stmt = $conn->prepare($sql);
    $stmt->execute(array(':sbas_id' => $sbas_id, ':usr_id'  => $usr_id));
    $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    foreach ($rs as $row) {
        if ( ! ($sx = simplexml_load_string($row['xml'])))
            continue;
        $t_desc = array();
        foreach ($sx->fields->children() as $fn => $fv) {
            if ( ! array_key_exists($fn, $t_desc))
                $t_desc[$fn] = trim($fv);
            else
                $t_desc[$fn] .= ' ; ' . trim($fv);
        }
        $desc = '';
        foreach ($t_desc as $fn => $fv)
            $desc .= '    <p><b>' . $fn . ':&nbsp;</b>' . str_replace(array('&', '<', '>'), array('&amp;', '&lt;', '&gt;'), $fv) . '</p>' . "\n";

        ob_start();
        ?>
        <li id="EDIT_PRESET_<?php echo $row['edit_preset_id'] ?>">
            <h1 style="position:relative; top:0px; left:0px; width:100%; height:auto;">
                <a class="triangle" href="#"><span class='triRight'>&#x25BA;</span><span class='triDown'>&#x25BC;</span></a>
                <a class="title" href="#"><?php echo $row['title'] ?></a>
                <a class="delete" style="position:absolute;right:0px;" href="#"><?php echo _('boutton::supprimer') ?></a>
            </h1>
            <div>
        <?php echo $desc ?>
            </div>
        </li>
        <?php
        $html .= ob_get_clean();
    }

    return($html);
}
if ( ! $parm['debug']) {
    phrasea::headers(200, true, 'application/json', 'UTF-8', false);
    print(p4string::jsonencode($ret));
}
