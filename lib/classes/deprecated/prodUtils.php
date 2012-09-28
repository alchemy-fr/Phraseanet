<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
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
function deleteRecord(Application $app, $lst, $del_children)
{
    $BE_repository = $app['EM']->getRepository('\Entities\BasketElement');

    $ACL = $app['phraseanet.user']->ACL();

    $lst = explode(";", $lst);

    $tcoll = array();
    $tbase = array();

    foreach ($lst as $basrec) {
        $basrec = explode("_", $basrec);
        if ( ! $basrec || count($basrec) !== 2)
            continue;

        $record = new record_adapter($app, $basrec[0], $basrec[1]);
        $base_id = $record->get_base_id();
        if ( ! isset($tcoll["c" . $base_id])) {

            $tcoll["c" . $base_id] = null;

            foreach ($app['phraseanet.appbox']->get_databoxes() as $databox) {
                foreach ($databox->get_collections() as $collection) {
                    if ($collection->get_base_id() == $base_id) {
                        $tcoll["c" . $base_id] = array("base_id" => $databox->get_sbas_id(), "id"      => $base_id);
                        if ( ! isset($tbase["b" . $base_id])) {
                            $x = $databox->get_structure();
                            $tbase["b" . $collection->get_base_id()] = array("id"   => $collection->get_base_id(), "rids" => array());
                        }
                        break;
                    }
                }
            }
        }

        $temp = null;
        $temp[0] = $basrec[0];
        $temp[1] = $basrec[1];

        $tbase["b" . $tcoll["c" . $base_id]["base_id"]]["rids"][] = $temp;
    }
    $ret = array();

    foreach ($tbase as $base) {
        try {
            foreach ($base["rids"] as $rid) {
                $record = new record_adapter($app, $rid[0], $rid[1]);
                if ( ! $ACL->has_right_on_base($record->get_base_id(), 'candeleterecord'))
                    continue;
                if ($del_children == "1") {
                    foreach ($record->get_children() as $oneson) {
                        if ( ! $ACL->has_right_on_base($oneson->get_base_id(), 'candeleterecord'))
                            continue;

                        $oneson->delete();
                        $ret[] = $oneson->get_serialize_key();
                    }
                }
                $ret[] = $record->get_serialize_key();

                $basket_elements = $BE_repository->findElementsByRecord($record);

                foreach ($basket_elements as $basket_element) {
                    $app['EM']->remove($basket_element);
                }

                $record->delete();
                unset($record);
            }
        } catch (Exception $e) {

        }
    }

    $app['EM']->flush();

    return p4string::jsonencode($ret);
}

function whatCanIDelete(Application $app, $lst)
{
    $usr_id = $app['phraseanet.user']->get_id();

    $conn = $app['phraseanet.appbox']->get_connection();

    $nbdocsel = 0;
    $nbgrp = 0;
    $oksel = array();
    $arrSel = explode(";", $lst);

    if ( ! is_array($lst))
        $lst = explode(';', $lst);

    foreach ($lst as $sel) {
        if ($sel == "")
            continue;
        $exp = explode("_", $sel);

        if (count($exp) !== 2)
            continue;

        $record = new record_adapter($app, $exp[0], $exp[1]);
        $sqlV = 'SELECT mask_and, mask_xor, sb.*
               FROM (sbas sb, bas b, usr u)
                LEFT JOIN basusr bu ON
                  (bu.base_id = b.base_id AND bu.candeleterecord = "1"
                    AND bu.usr_id = u.usr_id AND actif="1")
               WHERE u.usr_id = :usr_id AND b.base_id = :base_id
                AND b.sbas_id = sb.sbas_id';

        $params = array(
            ':base_id' => $record->get_base_id()
            , ':usr_id'  => $usr_id
        );

        $stmt = $conn->prepare($sqlV);
        $stmt->execute($params);
        $rowV = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ($rowV && $rowV['mask_and'] != '' && $rowV['mask_xor'] != '') {
            try {
                $connbas = connection::getPDOConnection($app, $rowV['sbas_id']);
                $sqlS2 = 'SELECT record_id FROM record
              WHERE ((status ^ ' . $rowV['mask_xor'] . ') & ' . $rowV['mask_and'] . ')=0
                AND record_id = :record_id';

                $stmt = $connbas->prepare($sqlS2);
                $stmt->execute(array(':record_id' => $exp[1]));
                $num_rows = $stmt->rowCount();
                $stmt->closeCursor();

                if ($num_rows > 0) {
                    $oksel[] = implode('_', $exp);
                    $nbdocsel ++;
                    if ($record->is_grouping())
                        $nbgrp ++;
                }
            } catch (Exception $e) {

            }
        }
    }

    $ret = array('lst'       => $oksel, 'groupings' => $nbgrp);

    return p4string::jsonencode($ret);
}
