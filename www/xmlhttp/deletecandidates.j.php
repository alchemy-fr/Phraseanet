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
require_once __DIR__ . "/../../lib/bootstrap.php";
$registry = registry::get_instance();

$request = http_request::getInstance();
$parm = $request->get_parms(
    'id'
);

$tsbas = array();

$ret = array();

$conn = connection::getPDOConnection();
$unicode = new unicode();

$sql = "SELECT * FROM sbas";
$stmt = $conn->prepare($sql);
$stmt->execute();
$rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor();

foreach ($rs as $row) {
    $tsbas['b' . $row['sbas_id']] = array('sbas' => $row, 'tids' => array());
}

foreach ($parm['id'] as $id) {
    $id = explode('.', $id);
    $sbas = array_shift($id);
    if (array_key_exists('b' . $sbas, $tsbas))
        $tsbas['b' . $sbas]['tids'][] = implode('.', $id);
}

foreach ($tsbas as $sbas) {
    if (count($sbas['tids']) <= 0)
        continue;

    $databox = databox::get_instance((int) $sbas['sbas']['sbas_id']);
    try {
        $connbas = connection::getPDOConnection($sbas['sbas']['sbas_id']);
    } catch (Exception $e) {
        continue;
    }

    $domth = $databox->get_dom_thesaurus();
    $domct = $databox->get_dom_cterms();

    if ( ! $domth || ! $domct)
        continue;

    $lid = '';
    $tsyid = array();
    $xpathct = new DOMXPath($domct);
    foreach ($sbas['tids'] as $tid) {
        $xp = '//te[@id="' . $tid . '"]/sy';
        $nodes = $xpathct->query($xp);
        if ($nodes->length == 1) {
            $sy = $term = $nodes->item(0);
            $w = $sy->getAttribute('w');
            $syid = str_replace('.', 'd', $sy->getAttribute('id')) . 'd';
            $lid .= ( $lid ? ',' : '') . "'" . $syid . "'";
            $tsyid[$syid] = array('w'     => $w, 'field' => $sy->parentNode->parentNode->getAttribute('field'));

            // remove candidate from cterms
            $te = $sy->parentNode;
            $te->parentNode->removeChild($te);
        }
    }

    $databox->saveCterms($domct);

    $sql = 'SELECT t.record_id, r.xml, t.value
          FROM thit AS t
          INNER JOIN record AS r USING(record_id)
          WHERE value IN (' . $lid . ') ORDER BY record_id';
    $stmt = $connbas->prepare($sql);
    $stmt->execute();
    $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    $t_rid = array();
    foreach ($rs as $rowbas) {
        $rid = $rowbas['record_id'];
        if ( ! array_key_exists('' . $rid, $t_rid))
            $t_rid['' . $rid] = array('xml'  => $rowbas['xml'], 'hits' => array());
        $t_rid['' . $rid]['hits'][] = $rowbas['value'];
    }

    foreach ($t_rid as $rid => $record) {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        if ( ! ($dom->loadXML($record['xml'])))
            continue;
        $nodetodel = array();
        $xp = new DOMXPath($dom);
        foreach ($record['hits'] as $value) {
            $field = $tsyid[$value];
            $x = '/record/description/' . $field['field'];
            $nodes = $xp->query($x);
            foreach ($nodes as $n) {
                $current_value = $unicode->remove_indexer_chars($n->textContent);

                if ($current_value == $field['w']) {
                    $nodetodel[] = $n;
                }
            }
        }
        foreach ($nodetodel as $n) {
            $n->parentNode->removeChild($n);
        }

        $sql = 'DELETE FROM idx  WHERE record_id = :record_id';
        $stmt = $connbas->prepare($sql);
        $stmt->execute(array(':record_id' => $rid));
        $stmt->closeCursor();

        $sql = 'DELETE FROM prop WHERE record_id = :record_id';
        $stmt = $connbas->prepare($sql);
        $stmt->execute(array(':record_id' => $rid));
        $stmt->closeCursor();


        $sql = 'DELETE FROM thit WHERE record_id = :record_id';
        $stmt = $connbas->prepare($sql);
        $stmt->execute(array(':record_id' => $rid));
        $stmt->closeCursor();


        $sql = 'UPDATE record
            SET status=(status & ~3)|4, jeton=' . (JETON_WRITE_META_DOC | JETON_WRITE_META_SUBDEF) . ',
                xml = :xml WHERE record_id = :record_id';
        $stmt = $connbas->prepare($sql);
        $stmt->execute(array(':record_id' => $rid, ':xml'       => $dom->saveXML()));
        $stmt->closeCursor();
    }
}

$ret = $parm['id'];

/**
 * @todo respecter les droits d'editing par collections
 */
phrasea::headers(200, true, 'application/json', 'UTF-8', false);
print(p4string::jsonencode($ret));
