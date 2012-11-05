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
require_once __DIR__ . "/../../lib/bootstrap.php";

$request = http_request::getInstance();
$parm = $request->get_parms(
    'sbid'
    , 'cid'  // candidate (id) to replace
    , 't'  // replacing term
    , 'debug'
);


phrasea::headers(200, true, 'application/json', 'UTF-8', false);

$dbname = null;

$result = array('n_recsChanged' => 0); // , 'n_termsDeleted'=>0, 'n_termsReplaced'=>0);

$appbox = \appbox::get_instance(\bootstrap::getCore());

try {
    $databox = $appbox->get_databox((int) $parm['sbid']);
    $connbas = $databox->get_connection();
    $domth = $databox->get_dom_thesaurus();
    $domct = $databox->get_dom_cterms();

    // delete the branch from the cterms

    if ($domth && $domct) {

        $lid = '';
        $tsyid = array();
        $tvals = array();
        $xpathct = new DOMXPath($domct);

        if ($parm['debug']) {
            printf("cterms before :\n%s \n", $domct->saveXML());
        }

        $xpathct = new DOMXPath($domct);
        $field = null;
        $x = null;

        $xp = '//te[@id="' . $parm['cid'] . '"]/sy';

        $nodes = $xpathct->query($xp);
        if ($nodes->length == 1) {
            $sy = $term = $nodes->item(0);
            $w = $sy->getAttribute('w');

            $candidate = array('a' => $sy->getAttribute('v'), 'u' => $sy->getAttribute('w'));
            if (($k = $sy->getAttribute('k'))) {
                $candidate['u'] .= ' (' . $k . ')';
            }
            if ($parm['debug']) {
                printf("%s : candidate = %s \n", __LINE__, var_export($candidate, true));
            }

            $syid = str_replace('.', 'd', $sy->getAttribute('id')) . 'd';
            $lid .= ( $lid ? ',' : '') . "'" . $syid . "'";
            $field = $sy->parentNode->parentNode->getAttribute('field');

            $tsyid[$syid] = array('w'     => $w, 'field' => $field);

            if (!array_key_exists($field, $tvals)) {
                $tvals[$field] = array();
            }
            $tvals[$field][] = $w;

            // remove candidate from cterms
            $te = $sy->parentNode;
            $te->parentNode->removeChild($te);

            if ($lid == '') {
                // no cterm was found
                continue;
            }


            if ($parm['debug']) {
                printf("cterms after :\n%s \n", $domct->saveXML());
            }
            if (!$parm['debug']) {
                $databox->saveCterms($domct);
            }

            $sql = 'SELECT t.record_id, r.xml
              FROM thit AS t INNER JOIN record AS r USING(record_id)
              WHERE t.value = :syn_id
              ORDER BY record_id';

            $stmt = $connbas->prepare($sql);
            $stmt->execute(array(':syn_id' => $syid));
            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            if ($parm['debug']) {
                printf("%s : SQL=%s \n", __LINE__, $sql);
            }

            $t_rid = array();
            foreach ($rs as $rowbas) {
                $rid = $rowbas['record_id'];
                if (!array_key_exists('' . $rid, $t_rid)) {
                    $t_rid['' . $rid] = $rowbas['xml'];
                }
            }
            if ($parm['debug']) {
                printf("%s : %s \n", __LINE__, var_export($t_rid, true));
            }

            $replacing = array();
            $parm['t'] = explode(';', $parm['t']);
            foreach ($parm['t'] as $t) {
                $replacing[] = simplified($t);
            }
            if ($parm['debug']) {
                printf("%s : replacing=%s \n", __LINE__, var_export($replacing, true));
            }

            $unicode = new unicode;
            foreach ($t_rid as $rid => $xml) {
                if ($parm['debug']) {
                    printf("(%d) ======== working on record_id = %d ======= \n", __LINE__, $rid);
                }

                try {
                    $record = $databox->get_record($rid);

                    $metadatask = array();  // datas to keep
                    $metadatasd = array();  // datas to delete

                    /* @var $field caption_field */
                    foreach ($record->get_caption()->get_fields(null, true) as $field) {
                        $meta_struct_id = $field->get_meta_struct_id();
                        if ($parm['debug']) {
                            printf("(%d) field '%s'  meta_struct_id=%s \n", __LINE__, $field->get_name(), $meta_struct_id);
                        }

                        /* @var $v caption_Field_Value */
                        foreach ($field->get_values() as $v) {
                            $vtxt = $unicode->remove_indexer_chars($v->getValue());
                            $keep = true;
                            foreach ($tvals as $fname => $vals) {
                                if ($field->get_name() == $fname) {
                                    if (in_array($vtxt, $vals)) {
                                        $keep = false;
                                    }
                                }
                            }
                            if ($parm['debug']) {
                                printf("(%d) ...v = '%s'  %s \n", __LINE__, $vtxt, ($keep ? '' : '!!! drop !!!'));
                            }
                            if ($keep) {
                                $metadatask[] = array(
                                    'meta_struct_id' => $meta_struct_id,
                                    'meta_id'        => $v->getId(),
                                    'value'          => $v->getValue()
                                );
                            } else {
                                $r = array_shift($replacing);
                                $metadatasd[] = array(
                                    'meta_struct_id' => $meta_struct_id,
                                    'meta_id'        => $v->getId(),
                                    'value'          => $r['a']
                                );
                                foreach ($replacing as $r) {
                                    $metadatasd[] = array(
                                        'meta_struct_id' => $meta_struct_id,
                                        'meta_id'        => null,
                                        'value'          => $r['a']
                                    );
                                }
                            }
                        }
                    }

                    if ($parm['debug']) {
                        //                       printf("metadatas-keep :\n");
                        //                       var_dump($metadatask);
                        printf("metadatas-delete :\n");
                        var_dump($metadatasd);
                    }

                    if (!$parm['debug']) {
                        foreach (array('idx', 'prop', 'thit') as $t) {
                            $sql = 'DELETE FROM ' . $t . ' WHERE record_id = :record_id';
                            $stmt = $connbas->prepare($sql);
                            $stmt->execute(array(':record_id' => $rid));
                            $stmt->closeCursor();
                        }
                        $record->set_metadatas($metadatasd, true);
                    }
                } catch (Exception $e) {

                }
            }
        }
    }
} catch (Exception $e) {

}

function simplified($t)
{
    $t = splitTermAndContext($t);
    $unicode = new unicode();
    $su = $unicode->remove_indexer_chars($sa = $t[0]);
    if ($t[1]) {
        $sa .= ' (' . ($t[1]) . ')';
        $su .= ' (' . $unicode->remove_indexer_chars($t[1]) . ')';
    }

    return(array('a' => $sa, 'u' => $su));
}
print(p4string::jsonencode(array('parm'   => $parm, 'result' => $result)));

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
