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
    'id',
    't',
    'debug'
);

phrasea::headers(200, true, 'application/json', 'UTF-8', false);

define('SEARCH_REPLACE_MAXREC', 25);

$tsbas = array();

$ret = array(
    'ctermsDeleted'=>array(),
    'maxRecsUpdatable'=>SEARCH_REPLACE_MAXREC,
    'nRecsToUpdate'=>0,
    'nRecsUpdated'=>0,
    'msg'=>''
);


foreach ($parm['id'] as $id) {
    $id = explode('.', $id);
    $sbas_id = array_shift($id);
    if (!array_key_exists('b' . $sbas_id, $tsbas)) {
        $tsbas['b' . $sbas_id] = array(
            'sbas_id' => (int) $sbas_id,
            'tids'    => array(),
            'domct' => null,
            'tvals' => array(),
            'lid'   => '',
            'trids' => array()
        );
    }
    $tsbas['b' . $sbas_id]['tids'][] = implode('.', $id);
}

if ($parm['debug']) {
    var_dump($tsbas);
}

$appbox = \appbox::get_instance(\bootstrap::getCore());


// first, count the number of records to update
foreach ($tsbas as $ksbas=>$sbas) {

    /* @var $databox databox */
    try {
        $databox = $appbox->get_databox($sbas['sbas_id']);
        $connbas = $databox->get_connection();
        // $domth = $databox->get_dom_thesaurus();
        $tsbas[$ksbas]['domct'] = $databox->get_dom_cterms();
    } catch (Exception $e) {
        continue;
    }

    if ( ! $tsbas[$ksbas]['domct']) {
        continue;
    }

    $lid = '';
    $xpathct = new DOMXPath($tsbas[$ksbas]['domct']);

    foreach ($sbas['tids'] as $tid) {
        $xp = '//te[@id="' . $tid . '"]/sy';
        $nodes = $xpathct->query($xp);
        if ($nodes->length == 1) {
            $sy = $term = $nodes->item(0);
            $syid = str_replace('.', 'd', $sy->getAttribute('id')) . 'd';
            $lid .= ( $lid ? ',' : '') . "'" . $syid . "'";
            $field = $sy->parentNode->parentNode->getAttribute('field');

            if ( ! array_key_exists($field, $tsbas[$ksbas]['tvals'])) {
                $tsbas[$ksbas]['tvals'][$field] = array();
            }
            $tsbas[$ksbas]['tvals'][$field][] = $sy;
        }
    }

    if ($lid == '') {
        // no cterm was found
        continue;
    }
    $tsbas[$ksbas]['lid'] = $lid;

    // count records
    $sql = 'SELECT DISTINCT record_id AS r'
          .' FROM thit WHERE value IN (' . $lid . ') ORDER BY record_id';
    $stmt = $connbas->prepare($sql);
    $stmt->execute();

    if ($parm['debug']) {
        printf("(%d) sql: \n", __LINE__);
        var_dump($sql);
    }

    $tsbas[$ksbas]['trids'] = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    $stmt->closeCursor();

    $ret['nRecsToUpdate'] += count($tsbas[$ksbas]['trids']);
}


if ($parm['debug']) {
    printf("(%d) nRecsToUpdate = %d \ntsbas: \n", __LINE__, $ret['nRecsToUpdate']);
    print_r($tsbas);
}


if($ret['nRecsToUpdate'] <= SEARCH_REPLACE_MAXREC)
{
    $unicode = new unicode;
    foreach ($tsbas as $sbas) {

        /* @var $databox databox */
        try {
            $databox = $appbox->get_databox($sbas['sbas_id']);
            $connbas = $databox->get_connection();
        } catch (Exception $e) {
            continue;
        }

        // fix caption of records
        foreach ($sbas['trids'] as $rid) {

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
                    $fname = $field->get_name();
                    if(!array_key_exists($fname, $sbas['tvals'])) {
                        foreach ($field->get_values() as $v) {
                            if ($parm['debug']) {
                                printf("(%d) ...v = '%s' (meta_id=%s)  keep \n", __LINE__, $v->getValue(), $v->getId());
                            }
                            $metadatask[] = array(
                                'meta_struct_id' => $meta_struct_id,
                                'meta_id'        => $v->getId(),
                                'value'          => $v->getValue()
                            );
                        }
                    }
                    else {
                        foreach ($field->get_values() as $v) {
                            $keep = true;
                            $vtxt = $unicode->remove_indexer_chars($v->getValue());
                            foreach($sbas['tvals'][$fname] as $sy) {
                                if ($sy->getAttribute('w') == $vtxt) {
                                    $keep = false;
                                }
                            }

                            if ($parm['debug']) {
                                printf("(%d) ...v = '%s' (meta_id=%s)  %s \n", __LINE__, $v->getValue(), $v->getId(), ($keep ? '' : '!!! drop !!!'));
                            }
                            if ($keep) {
                                $metadatask[] = array(
                                    'meta_struct_id' => $meta_struct_id,
                                    'meta_id'        => $v->getId(),
                                    'value'          => $v->getValue()
                                );
                            } else {
                                $metadatasd[] = array(
                                    'meta_struct_id' => $meta_struct_id,
                                    'meta_id'        => $v->getId(),
                                    'value'          => $parm['t'] ? $parm['t'] : ''
                                );
                            }
                        }
                    }
                }

                if ($parm['debug']) {
                    printf("(%d) metadatask: \n", __LINE__);
                    var_dump($metadatask);
                    printf("(%d) metadatasd: \n", __LINE__);
                    var_dump($metadatasd);
                }

                if(count($metadatasd) > 0) {
                    if ( ! $parm['debug']) {
                        $record->set_metadatas($metadatasd, true);
                        $ret['nRecsUpdated']++;
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // delete the branch from the cterms
        if ($parm['debug']) {
            printf("cterms before :\n%s \n", $sbas['domct']->saveXML());
        }
        foreach($sbas['tvals'] as $tval) {
            foreach($tval as $sy) {
                // remove candidate from cterms
                $te = $sy->parentNode;
                $te->parentNode->removeChild($te);
                $ret['ctermsDeleted'][] = $sbas['sbas_id'] . '.' . $te->getAttribute('id');
            }
        }
        if ($parm['debug']) {
            printf("cterms after :\n%s \n", $sbas['domct']->saveXML());
        }
        if ( ! $parm['debug']) {
            $databox->saveCterms($sbas['domct']);
        }

    }
    $ret['msg'] = sprintf(_('prod::thesaurusTab:dlg:%d record(s) updated'), $ret['nRecsUpdated']);
}
else {
    // too many records to update
    $ret['msg'] = sprintf(_('prod::thesaurusTab:dlg:too many (%1$d) records to update (limit=%2$d)'), $ret['nRecsToUpdate'], SEARCH_REPLACE_MAXREC);
}


/**
 * @todo respecter les droits d'editing par collections
 */
print(p4string::jsonencode($ret));
