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

$request = http_request::getInstance();
$parm = $request->get_parms(
    'sbid'
    , 't'
    , 'field' // search only in the branch(es) linked to this field
    , 'lng'
    , 'debug'
);

phrasea::headers(200, true, 'application/json', 'UTF-8', false);

if ( ! $parm['lng']) {
    $lng2 = Session_Handler::get_locale();
    $lng2 = explode('_', $lng2);
    if (count($lng2) > 0)
        $parm['lng'] = $lng2[0];
}

$lng = $parm['lng'];

if ($parm['debug'])
    print("<pre>");

$html = '';

$sbid = (int) $parm['sbid'];

$dbname = '';

$loaded = false;

try {
    $databox = databox::get_instance($sbid);
    $unicode = new unicode();

    $html = "" . '<LI id="TX_P.' . $sbid . '.T" class="expandable">' . "\n";
    $html .= "\t" . '<div class="hitarea expandable-hitarea"></div>' . "\n";
    $html .= "\t" . '<span>' . phrasea::sbas_names($sbid) . '</span>' . "\n";

    if ($parm['t']) {
        if ($parm['field'] != '') {
            $domth = $databox->get_dom_thesaurus();
            $dom_struct = $databox->get_dom_structure();
        } else {
            $domth = $databox->get_dom_thesaurus();
        }

        $q = null;
        if ($parm['field'] != '') {
            // search only in the branch(es) linked to this field
            if ($dom_struct) {
                $xpath = new DOMXPath($dom_struct);
                if (($znode = $xpath->query('/record/description/' . $parm['field'])->item(0))) {
                    $q = '(' . $znode->getAttribute('tbranch') . ')';
                }
            }
        } else {
            // search in the whole thesaurus
            $q = '/thesaurus';
        }

        if (($q !== null) && $domth) {
            $xpath = new DOMXPath($domth);

            if ($parm['debug'])
                print('q:' . $q . "\n");

            $t = splitTermAndContext($parm['t']);
            $q2 = 'starts-with(@w, \'' . thesaurus::xquery_escape($unicode->remove_indexer_chars($t[0])) . '\')';
            if ($t[1])
                $q2 .= ' and starts-with(@k, \'' . thesaurus::xquery_escape($unicode->remove_indexer_chars($t[1])) . '\')';
            $q2 = '//sy[' . $q2 . ' and @lng=\'' . $lng . '\']';

            if ($parm['debug'])
                print('q2:' . $q2 . "\n");

            $q .= $q2;
            if ($parm['debug'])
                print('q:' . $q . "\n");

            $nodes = $xpath->query($q);

            for ($i = 0; $i < $nodes->length; $i ++ ) {
                $nodes->item($i)->setAttribute('bold', '1');
                for ($n = $nodes->item($i)->parentNode; $n && $n->nodeType == XML_ELEMENT_NODE && $n->nodeName == 'te'; $n = $n->parentNode) {
                    $n->setAttribute('open', '1');
                    if ($parm['debug'])
                        printf("opening node te id=%s \n", $n->getAttribute('id'));
                }
            }

            getHTML($domth->documentElement, $html);
        }
    } else {
        $html .= "\t" . '<ul style="display: none;">loading</ul>' . "\n";
    }

    $html .= "" . '</LI>' . "\n";
} catch (Exception $e) {

}

if ($parm['debug'])
    print(htmlentities($html));
else
    print(p4string::jsonencode(array('parm' => $parm, 'html' => $html)));

if ($parm['debug'])
    print("</pre>");

function getHTML($srcnode, &$html, $depth = 0)
{
    global $parm;
    if ($parm['debug'])
        printf("in: depth:%s \n", $depth);

    $bid = $parm['sbid'];
    $tid = $srcnode->getAttribute('id');

    // let's work on each 'te' (=ts) subnode
    $nts = 0;
    $ntsopened = 0;
    $tts = array();
    for ($n = $srcnode->firstChild; $n; $n = $n->nextSibling) {
        if ($n->nodeName == 'te') {
            if ($n->getAttribute('open')) {
                $key0 = null; // key of the sy in the current language (or key of the first sy if we can't find good lng)
                $nts0 = 0;  // count of ts under this term

                $label = buildLabel($n, $key0, $nts0);

                for ($uniq = 0; $uniq < 9999; $uniq ++ ) {
                    if ( ! isset($tts[$key0 . '_' . $uniq]))
                        break;
                }
                $tts[$key0 . '_' . $uniq] = array('label' => $label, 'nts'   => $nts0, 'n'     => $n);
                $ntsopened ++;
            }
            $nts ++;
        }
    }

    if ($parm["debug"])
        printf("%s : tts(%s) : <pre>%s</pre><br/>\n", __LINE__, $nts, var_export($tts, true));

    if ($nts > 0) {
        $tab = str_repeat("\t", 1 + $depth * 2);

        if ($ntsopened == 0) {
            $html .= $tab . '<UL style="display:none">' . "\n";
            $html .= $tab . '</UL>' . "\n";
        } else {
            $html .= $tab . '<UL>' . "\n";
            // dump every ts
            foreach ($tts as $ts) {
                $class = '';
                if ($ts['nts'] > 0)
                    $class .= ( $class == '' ? '' : ' ') . 'expandable';
                if ( -- $ntsopened == 0)
                    $class .= ( $class == '' ? '' : ' ') . 'last';

                $tid = $ts['n']->getAttribute('id');

                $html .= $tab . "\t" . '<LI id="TX_P.' . $bid . '.' . $tid . '" class="' . $class . '">' . "\n";
                if ($ts['nts'] > 0) {
                    $html .= $tab . "\t\t" . '<div class="hitarea expandable-hitarea" />' . "\n";
                } else {
                    $html .= $tab . "\t\t" . '<div />' . "\n";
                }
                $html .= $tab . "\t\t" . '<span>' . $ts['label'] . '</span>' . "\n";
//        if($ts['nts']>0)
//          $html .= $tab . "\t\t\t" . '<ul style="display: none;">loading</ul>' . "\n";

                if ($parm['debug'])
                    printf("==== recurs on %s ====\n", $ts['label']);

                getHTML($ts['n'], $html, $depth + 1);

                $html .= $tab . "\t" . '</LI>' . "\n";
            }
            $html .= $tab . '</UL>' . "\n";
        }
    }

    if ($parm['debug'])
        printf("out: depth:%s \n", $depth);
}

function buildLabel($n, &$key0, &$nts0)
{
    global $parm;
    $lngfound = false; // true when wet met a first synonym in the current language
    $key0 = null;  // key of the sy in the current language (or key of the first sy if we can't find good lng)
    $label = '';
    $nts0 = 0;  //
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

                $label = '<span ' . $class . '>' . $t . '</span>' . ($label == '' ? '' : ' ; ') . $label;
            } else {
                $label = $label . ($label == '' ? '' : ' ; ') . '<span ' . $class . '>' . $t . '</span>';
            }
        } elseif ($n2->nodeName == 'te') { // && $n2->getAttribute('open'))
            $nts0 ++;
        }
    }

    return($label);
}

function splitTermAndContext($word)
{
    $term = trim($word);
    $context = '';
    if (($po = strpos($term, '(')) !== false) {
        if (($pc = strpos($term, ')', $po)) !== false) {
            $context = trim(substr($term, $po + 1, $pc - $po - 1));
            $term = trim(substr($term, 0, $po));
        } else {
            $context = trim(substr($term, $po + 1));
            $term = trim(substr($term, 0, $po));
        }
    }

    return(array($term, $context));
}
