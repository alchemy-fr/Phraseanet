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
$request = http_request::getInstance();
$parm = $request->get_parms(
    'sbid'
    , 'type'
    , 'id'
    , 'lng'
    , 'sortsy' // trier la liste des sy (='1') ou pas
    , 'debug'
    , 'root'
    , 'last' // the node to browse has the 'last' class in the tree, so keep it
);


phrasea::headers(200, true, 'application/json', 'UTF-8', false);


if ( ! $parm['lng']) {
    $lng2 = $app['locale'];
    $lng2 = explode('_', $lng2);
    if (count($lng2) > 0)
        $parm['lng'] = $lng2[0];
}

$lng = $parm['lng'];


if ($parm['debug'])
    print('<pre>');

$html = '';


$sbid = (int) $parm['sbid'];

// root = 'TX_P.' . $xbas_id . '.T ....
$tids = explode('.', $parm['id']);
$thid = implode('.', $tids);


$loaded = false;

try {
    $connbas = connection::getPDOConnection($app, $sbid);
    $dbname = phrasea::sbas_names($sbid, $app);

    $t_nrec = array();
    $lthid = strlen($thid);

    // count occurences
    if ($lthid == 1) {
        $dthid = str_replace('.', 'd', $thid);

        $sql = 'SELECT COUNT(DISTINCT record_id) AS n FROM thit WHERE value LIKE :like ';

        $stmt = $connbas->prepare($sql);
        $stmt->execute(array(':like' => $dthid . '%'));
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ($parm['debug'])
            printf("/*\n  thid=%s\n  %s \n */\n", $thid, $sql);

        foreach ($rs as $rowbas)
            $t_nrec[$thid] = $rowbas;

        $sql = 'SELECT SUBSTRING_INDEX(SUBSTR(value, ' . ($lthid + 1) . '), "d", 1) AS k ,
              COUNT(DISTINCT record_id) AS n
            FROM thit
            WHERE value LIKE :like
            GROUP BY k';

        $stmt = $connbas->prepare($sql);
        $stmt->execute(array(':like' => $dthid . '%'));
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ($parm['debug'])
            printf("/*\n  thid=%s\n  %s \n */\n", $thid, $sql);

        foreach ($rs as $rowbas)
            $t_nrec[$thid . $rowbas['k']] = $rowbas;
    }
    elseif (strlen($thid) > 1) {
        $dthid = str_replace('.', 'd', $thid);
        $sql = 'SELECT SUBSTRING_INDEX(SUBSTR(value, ' . ($lthid) . '), \'d\', 1) AS k ,
              COUNT(DISTINCT record_id) AS n
            FROM thit
            WHERE value LIKE :like
            GROUP BY k';

        $stmt = $connbas->prepare($sql);
        $stmt->execute(array(':like' => $dthid . '%'));
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ($parm['debug'])
            printf("/*\n  thid=%s\n  %s \n */\n", $thid, $sql);

        foreach ($rs as $rowbas)
            $t_nrec[$thid] = $rowbas;

        $sql = 'SELECT SUBSTRING_INDEX(SUBSTR(value, ' . ($lthid + 2) . '), \'d\', 1) AS k ,
              COUNT(DISTINCT record_id) AS n
            FROM thit
            WHERE value LIKE :like
            GROUP BY k';

        $stmt = $connbas->prepare($sql);
        $stmt->execute(array(':like' => $dthid . '%'));
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ($parm['debug'])
            printf("/*\n  thid=%s\n  %s \n */\n", $thid, $sql);

        foreach ($rs as $rowbas)
            $t_nrec[$thid . '.' . $rowbas['k']] = $rowbas;
    }

    if ($parm['debug'])
        printf("/* %s */\n", var_export($t_nrec, true));

    $databox = $app['phraseanet.appbox']->get_databox($sbid);
    if ($parm['type'] == 'T') {
        $xqroot = 'thesaurus';
        $dom = $databox->get_dom_thesaurus();
    } else { // C
        $xqroot = 'cterms';
        $dom = $databox->get_dom_cterms();
    }

    if ($dom) {
        $term0 = '';
        $firstTerm0 = '';

        $xpath = new DOMXPath($dom);
        if ($thid == 'T' || $thid == 'C') {
            $q = '/' . $xqroot;
            $term0 = $dbname;
        } else {
            $q = '/' . $xqroot . '//te[@id=\'' . $thid . '\']';
        }
        if ($parm['debug'])
            print("q:" . $q . "<br/>\n");

        $nodes = $xpath->query($q);
        if ($nodes->length > 0) {
            $node0 = $nodes->item(0);


            $key0 = null; // key of the sy in the current language (or key of the first sy if we can't find good lng)
            $nts0 = 0;  // count of ts under this term

            $label = buildLabel($node0, $key0, $nts0);

            if ($parm['debug'])
                printf("buildLabel(...)\n  label=%s\n  key0=%s\n  nts0=%s \n", $label, $key0, $nts0);

            $class = '';
            if ($nts0 > 0)
                $class .= ( $class == '' ? '' : ' ') . 'expandable';
            if ($parm['last'])
                $class .= ( $class == '' ? '' : ' ') . 'last';

            $html .= '<LI id="' . $parm['type'] . 'X_P.' . $sbid . '.' . $thid . '" class="' . $class . '" loaded="1">' . "\n";
            $html .= '  <div class="hitarea expandable-hitarea"></div>' . "\n";
            $html .= '  <span>' . $label . '</span>';

            if (isset($t_nrec[$thid]))
                $html .= ' <I>' . $t_nrec[$thid]['n'] . '</I>';

            $html .= "\n";

            // on dresse la liste des termes specifiques avec comme cle le synonyme dans la langue pivot
            $nts = 0;
            $tts = array();
            for ($n = $node0->firstChild; $n; $n = $n->nextSibling) {
                if ($n->nodeName == 'te' && ! $n->getAttribute('delbranch')) {
                    $nts ++;

                    $key0 = null; // key of the sy in the current language (or key of the first sy if we can't find good lng)
                    $nts0 = 0;  // count of ts under this term

                    $label = buildLabel($n, $key0, $nts0);

                    if ($parm['debug'])
                        printf("buildLabel(...)\n  label=%s\n  key0=%s\n  nts0=%s \n", $label, $key0, $nts0);

                    for ($uniq = 0; $uniq < 9999; $uniq ++ ) {
                        if ( ! isset($tts[$key0 . '_' . $uniq]))
                            break;
                    }
                    $tts[$key0 . '_' . $uniq] = array('label' => $label, 'nts'   => $nts0, 'n'     => $n);
                }
            }

            if ($parm['debug'])
                printf("tts(%s) : <pre>%s</pre><br/>\n", $nts, var_export($tts, true));

            if ($nts > 0) {
                $field0 = $node0->getAttribute('field');
                if ($field0)
                    $field0 = 'field="' . $field0 . '"';

                $html .= '  <UL ' . $field0 . '>' . "\n";

                // dump every ts
                if ($parm['sortsy'] && $parm['lng'])
                    ksort($tts, SORT_STRING);
                elseif ($parm['type'] == 'C')
                    $tts = array_reverse($tts);
                if ($parm['debug'])
                    printf("%s: type=%s : <pre>%s</pre><br/>\n", __LINE__, $parm['type'], var_export($tts, true));

                foreach ($tts as $ts) {
                    $class = '';
                    if ($ts['nts'] > 0)
                        $class .= ( $class == '' ? '' : ' ') . 'expandable';
                    if ( -- $nts == 0)
                        $class .= ( $class == '' ? '' : ' ') . 'last';

                    $tid = $ts['n']->getAttribute('id');

                    $html .= '    <LI id="' . $parm['type'] . 'X_P.' . $sbid . '.' . $tid . '" class="' . $class . '">' . "\n";
                    if ($ts['nts'] > 0)
                        $html .= '      <div class="hitarea expandable-hitarea" />' . "\n";
                    else
                        $html .= '      <div />' . "\n";
                    $html .= '      <span>' . $ts['label'] . '</span>';

                    if (isset($t_nrec[$tid]))
                        $html .= ' <I>' . $t_nrec[$tid]['n'] . '</I>';

                    $html .= "\n";

                    if ($ts['nts'] > 0)
                        $html .= '      <UL style="display:none">loading</UL>' . "\n";
                    $html .= '    </LI>' . "\n";
                }
                $html .= '  </UL>' . "\n";
            }

            $html .= '</LI>' . "\n";
        }
    }
} catch (Exception $e) {

}

print(p4string::jsonencode(array('parm' => $parm, 'html' => $html)));

if ($parm['debug'])
    print("</pre>");

function buildLabel($n, &$key0, &$nts0)
{
    global $parm, $dbname;

    $key0 = null;  // key of the sy in the current language (or key of the first sy if we can't find good lng)
    $label = '';
    $nts0 = 0;  //

    if ( ! $n->getAttribute('id')) {
        // root of thesurus or root of cterms
        $label = $dbname;
        $key0 = $dbname;
        $nts0 = 999;
    } elseif (($csfield = $n->getAttribute('field')) != '') {
        // we display a first level (field) branch in candidates
        $label = $csfield;
        $key0 = $csfield;
        $nts0 = 999;
    } else {
        $lngfound = false; // true when wet met a first synonym in the current language
        // compute the label of the term, regarding the current language.
        for ($n2 = $n->firstChild; $n2; $n2 = $n2->nextSibling) {
            if ($n2->nodeName == 'sy') {
                $lng = $n2->getAttribute('lng');
                $t = $n2->getAttribute('v');
                $key = $n2->getAttribute('w');  // key of the current sy
                if ($k = $n2->getAttribute('k'))
                    $key .= ' (' . $k . ')';
                if ( ! $key0)       // first sy gives the key
                    $key0 = $key;
                $class = $n2->getAttribute('bold') ? 'class="h"' : '';
                if ( ! $lngfound && $lng == $parm['lng']) { // overwrite the key if we found the good lng
                    $key0 = $key;
                    $lngfound = true;

                    $label = '<span ' . $class . '>' . $t . '</span>' . ($label == '' ? '' : ' <span class="separator">;</span> ') . $label;
                } else {
                    $label = $label . ($label == '' ? '' : ' ; ') . '<span ' . $class . '>' . $t . '</span>';
                }
            } elseif ($n2->nodeName == 'te') {
                $nts0 ++;
            }
        }
    }

    return($label);
}
