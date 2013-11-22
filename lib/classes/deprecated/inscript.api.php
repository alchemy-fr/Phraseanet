<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

function giveMeBases(Application $app, $usr = null)
{
    $conn = $app['phraseanet.appbox']->get_connection();

    $inscriptions = null;

    $usrerRegis = null;

    if ($usr != null) {

        $sqlU = '
            SELECT sbas.dbname, time_limited, UNIX_TIMESTAMP( limited_from ) AS limited_from,
                UNIX_TIMESTAMP( limited_to ) AS limited_to, bas.server_coll_id,
                u.id, basusr.actif, demand.en_cours, demand.refuser
            FROM (Users u, bas, sbas)
            LEFT JOIN basusr ON ( u.id = basusr.usr_id
                AND bas.base_id = basusr.base_id )
            LEFT JOIN demand ON ( demand.usr_id = u.id
                AND bas.base_id = demand.base_id )
            WHERE bas.active > 0
                AND bas.sbas_id = sbas.sbas_id
                AND u.id = :usr_id
                AND u.model_of IS NULL
        ';

        $stmt = $conn->prepare($sqlU);
        $stmt->execute([':usr_id' => $usr]);
        $rsU = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if (count($rsU) == 0) {
            return null;
        }

        foreach ($rsU as $rowU) {
            if ( ! isset($usrerRegis[$rowU['dbname']]))
                $usrerRegis[$rowU['dbname']] = null;

            if ( ! is_null($rowU['actif']) || ! is_null($rowU['en_cours'])) {

                $usrerRegis[$rowU['dbname']][$rowU['server_coll_id']] = true;
                if ($rowU['actif'] == '0')
                    $usrerRegis[$rowU['dbname']][$rowU['server_coll_id']] = 'NONACTIF';
                elseif ($rowU['time_limited'] == '1' && ! ($rowU['limited_from'] >= time() && $rowU['limited_to'] <= time()))
                    $usrerRegis[$rowU['dbname']][$rowU['server_coll_id']] = 'OUTTIME';
                elseif ($rowU['time_limited'] == '1' && ($rowU['limited_from'] > time() && $rowU['limited_to'] < time()))
                    $usrerRegis[$rowU['dbname']][$rowU['server_coll_id']] = 'INTIME';
                elseif ($rowU['en_cours'] == '1')
                    $usrerRegis[$rowU['dbname']][$rowU['server_coll_id']] = 'WAIT';
                elseif ($rowU['refuser'] == '1')
                    $usrerRegis[$rowU['dbname']][$rowU['server_coll_id']] = 'REFUSE';
            }
        }
    }

    foreach ($app['phraseanet.appbox']->get_databoxes() as $databox) {
        $collname = $basname = null;
        $sbas_id = $databox->get_sbas_id();
        $inscriptions[$sbas_id] = [];
        $inscriptions[$sbas_id]['CGU'] = false;
        $inscriptions[$sbas_id]['CGUrelease'] = false;
        $inscriptions[$sbas_id]['inscript'] = false;
        $inscriptions[$sbas_id]['CollsCGU'] = null;
        $inscriptions[$sbas_id]['Colls'] = null;
        $inscriptions[$sbas_id]['CollsRegistered'] = null;
        $inscriptions[$sbas_id]['CollsWait'] = null;
        $inscriptions[$sbas_id]['CollsRefuse'] = null;
        $inscriptions[$sbas_id]['CollsIntime'] = null;
        $inscriptions[$sbas_id]['CollsOuttime'] = null;
        $inscriptions[$sbas_id]['CollsNonactif'] = null;

        foreach ($databox->get_collections() as $key => $coll) {
            $collname[$key] = $coll->get_label($app['locale']);
            $basname[$key] = $coll->get_coll_id();
        }
        $sbpcgu = '';

        $xml = $databox->get_sxml_structure();
        if ($xml) {
            foreach ($xml->xpath('/record/caninscript') as $caninscript) {
                if ($inscriptions[$sbas_id]['inscript'] === false)
                    $inscriptions[$sbas_id]['inscript'] = ((string) $caninscript == "1");
            }
            foreach ($xml->xpath('/record/cgu') as $sbpcgu) {
                foreach ($sbpcgu->attributes() as $a => $b) {
                    if ($a == "release")
                        $inscriptions[$sbas_id]['CGUrelease'] = (string) $b;
                }
                $inscriptions[$sbas_id]['CGU'] = (string) $sbpcgu->saveXML();
            }
        }
        $baseInscript = $inscriptions[$sbas_id]['inscript'];
        foreach ($databox->get_collections() as $collection) {
            $cguColl = false;

            $collInscript = $baseInscript;
            $cguSpec = false;
            if (false !== $xml = simplexml_load_string($collection->get_prefs())) {
                foreach ($xml->xpath('/baseprefs/caninscript') as $caninscript) {
                    $tmp = (string) $caninscript;
                    if ($tmp === "1")
                        $collInscript = true;
                    elseif ($tmp === "0")
                        $collInscript = false;
                }
                if ($collInscript) {
                    $cguCollRelease = false;

                    if ($inscriptions[$sbas_id]['inscript'] === false)
                        $inscriptions[$sbas_id]['inscript'] = ! ! $collInscript;

                    foreach ($xml->xpath('/baseprefs/cgu') as $bpcgu) {
                        foreach ($bpcgu->attributes() as $a => $b) {
                            if ($a == "release")
                                $cguCollRelease = (string) $b;
                        }
                        $cguColl = (string) $bpcgu->saveXML();
                    }
                    if ($cguColl) {
                        $cguSpec = true;
                    } else {
                        if ( ! isset($usrerRegis[$databox->get_dbname()][$collection->get_coll_id()]))
                            $inscriptions[$sbas_id]['Colls'][$collection->get_coll_id()] = $collection->get_label($app['locale']);
                    }
                }
            }
            $lacgu = $cguColl ? $cguColl : (string) $sbpcgu;

            if (isset($usrerRegis[$databox->get_dbname()]) && isset($usrerRegis[$databox->get_dbname()][$collection->get_coll_id()])) {
                if ($usrerRegis[$databox->get_dbname()][$collection->get_coll_id()] === "WAIT")
                    $inscriptions[$sbas_id]['CollsWait'][$collection->get_coll_id()] = $lacgu;
                elseif ($usrerRegis[$databox->get_dbname()][$collection->get_coll_id()] === "REFUSE")
                    $inscriptions[$sbas_id]['CollsRefuse'][$collection->get_coll_id()] = $lacgu;
                elseif ($usrerRegis[$databox->get_dbname()][$collection->get_coll_id()] === "INTIME")
                    $inscriptions[$sbas_id]['CollsIntime'][$collection->get_coll_id()] = $lacgu;
                elseif ($usrerRegis[$databox->get_dbname()][$collection->get_coll_id()] === "OUTTIME")
                    $inscriptions[$sbas_id]['CollsOuttime'][$collection->get_coll_id()] = $lacgu;
                elseif ($usrerRegis[$databox->get_dbname()][$collection->get_coll_id()] === "NONACTIF")
                    $inscriptions[$sbas_id]['CollsNonactif'][$collection->get_coll_id()] = $lacgu;
                elseif ($usrerRegis[$databox->get_dbname()][$collection->get_coll_id()] === true)
                    $inscriptions[$sbas_id]['CollsRegistered'][$collection->get_coll_id()] = $lacgu;
            } elseif (! $cguSpec && $collInscript) {//ne va pas.. si l'inscriptio na la coll est explicitement non autorise, je refuse'
                $inscriptions[$sbas_id]['Colls'][$collection->get_coll_id()] = $collection->get_label($app['locale']);
            } elseif ($cguSpec) {
                $inscriptions[$sbas_id]['CollsCGU'][$collection->get_coll_id()]['name'] = $collection->get_label($app['locale']);
                $inscriptions[$sbas_id]['CollsCGU'][$collection->get_coll_id()]['CGU'] = $cguColl;
                $inscriptions[$sbas_id]['CollsCGU'][$collection->get_coll_id()]['CGUrelease'] = $cguCollRelease;
            }
        }
    }

    return $inscriptions;
}

function giveMeBaseUsr(Application $app, $usr)
{
    $noDemand = true;

    $out = '<table border="0" style="table-layout:fixed;font-size:11px;" cellspacing=0 width="100%">' .
        '<tr>' .
        '<td  style="width:180px; text-align:right">&nbsp;</td>' .
        '<td  width="15px" style="width:15px">&nbsp;</td>' .
        '<td  style="width:180px;">&nbsp;</td>' .
        '</tr>';

    $inscriptions = giveMeBases($app, $usr);
    foreach ($inscriptions as $sbasId => $baseInsc) {
        //je presente la base
        if (($baseInsc['CollsRegistered'] || $baseInsc['CollsRefuse'] || $baseInsc['CollsWait'] || $baseInsc['CollsIntime'] || $baseInsc['CollsOuttime'] || $baseInsc['CollsNonactif'] || $baseInsc['CollsCGU'] || $baseInsc['Colls']))//&& $baseInsc['inscript'])
            $out .= '<tr><td colspan="3" style="text-align:center;"><h3>' . phrasea::sbas_labels($sbasId, $app) . '</h3></td></tr>';

        if ($baseInsc['CollsRegistered']) {
            foreach ($baseInsc['CollsRegistered'] as $collId => $isTrue) {
                $base_id = phrasea::baseFromColl($sbasId, $collId, $app);
                $out .= '<tr><td colspan="3" style="text-align:center;">' . $app->trans('login::register: acces authorise sur la collection') . phrasea::bas_labels($base_id, $app);
                if (trim($isTrue) != '')
                    $out .= ' <a class="inscriptlink" href="/include/cguUtils.php?action=PRINT&bas=' . $sbasId . '&col=' . $collId . '">' . $app->trans('login::register::CGU: lire les CGU') . '</a>';
                $out .= '</td></tr>';
            }
            $out .= '<tr style="height:5px;"><td></td></tr>';
        }
        if ($baseInsc['CollsRefuse']) {
            foreach ($baseInsc['CollsRefuse'] as $collId => $isTrue) {
                $base_id = phrasea::baseFromColl($sbasId, $collId, $app);
                $out .= '<tr><td colspan="3" style="text-align:center;"><span style="color:red;">' . $app->trans('login::register: acces refuse sur la collection') . phrasea::bas_labels($base_id, $app) . '</span>';
                if (trim($isTrue) != '')
                    $out .= ' <a class="inscriptlink" href="/include/cguUtils.php?action=PRINT&bas=' . $sbasId . '&col=' . $collId . '">' . $app->trans('login::register::CGU: lire les CGU') . '</a>';
                $out .= '</td></tr>';
            }
            $out .= '<tr style="height:5px;"><td></td></tr>';
        }
        if ($baseInsc['CollsWait']) {
            foreach ($baseInsc['CollsWait'] as $collId => $isTrue) {
                $base_id = phrasea::baseFromColl($sbasId, $collId, $app);
                $out .= '<tr><td colspan="3" style="text-align:center;"><span style="color:orange;">' . $app->trans('login::register: en attente d\'acces sur') . ' ' . phrasea::bas_labels($base_id, $app) . '</span>';
                if (trim($isTrue) != '')
                    $out .= ' <a class="inscriptlink" href="/include/cguUtils.php?action=PRINT&bas=' . $sbasId . '&col=' . $collId . '">' . $app->trans('login::register::CGU: lire les CGU') . '</a>';
                $out .= '</td></tr>';
            }
            $out .= '<tr style="height:5px;"><td></td></tr>';
        }
        if ($baseInsc['CollsIntime']) {
            foreach ($baseInsc['CollsIntime'] as $collId => $isTrue) {
                $base_id = phrasea::baseFromColl($sbasId, $collId, $app);
                $out .= '<tr><td colspan="3" style="text-align:center;">' . $app->trans('login::register: acces temporaire sur') . phrasea::bas_labels($base_id, $app) . '</span>';
                if (trim($isTrue) != '')
                    $out .= ' <a class="inscriptlink" href="/include/cguUtils.php?action=PRINT&bas=' . $sbasId . '&col=' . $collId . '">' . $app->trans('login::register::CGU: lire les CGU') . '</a>';
                $out .= '</td></tr>';
            }
            $out .= '<tr style="height:5px;"><td></td></tr>';
        }
        if ($baseInsc['CollsOuttime']) {
            foreach ($baseInsc['CollsOuttime'] as $collId => $isTrue) {
                $base_id = phrasea::baseFromColl($sbasId, $collId, $app);
                $out .= '<tr><td colspan="3" style="text-align:center;"><span style="color:red;">' . $app->trans('login::register: acces temporaire termine sur') . phrasea::bas_labels($base_id, $app) . '</span>';
                if (trim($isTrue) != '')
                    $out .= ' <a class="inscriptlink" href="/include/cguUtils.php?action=PRINT&bas=' . $sbasId . '&col=' . $collId . '">' . $app->trans('login::register::CGU: lire les CGU') . '</a>';
                $out .= '</td></tr>';
            }
            $out .= '<tr style="height:5px;"><td></td></tr>';
        }
        if ($baseInsc['CollsNonactif']) {
            foreach ($baseInsc['CollsNonactif'] as $collId => $isTrue) {
                $base_id = phrasea::baseFromColl($sbasId, $collId, $app);
                $out .= '<tr><td colspan="3" style="text-align:center;"><span style="color:red;">' . $app->trans('login::register: acces supendu sur') . phrasea::bas_labels($base_id, $app) . '</span>';
                if (trim($isTrue) != '')
                    $out .= ' <a class="inscriptlink" href="/include/cguUtils.php?action=PRINT&bas=' . $sbasId . '&col=' . $collId . '">' . $app->trans('login::register::CGU: lire les CGU') . '</a>';
                $out .= '</td></tr>';
            }
            $out .= '<tr style="height:5px;"><td></td></tr>';
        }

        $out .= '<tr style="height:5px;"><td></td></tr>';
        if (($baseInsc['CollsCGU'] || $baseInsc['Colls']) && $baseInsc['inscript']) {// il y a des coll ou s'inscrire !
            $noDemand = false;

            if ($baseInsc['Colls']) {//des coll ou on peut s'inscrire sans cgu specifiques
                //je check si ya des cgu pour la base
                if ($baseInsc['CGU']) {
                    $out .= '<tr><td colspan="3" style="text-align:center;">' . $app->trans('login::register: L\'acces aux bases ci-dessous implique l\'acceptation des Conditions Generales d\'Utilisation (CGU) suivantes') . '</td></tr>';
                    $out .= '<tr><td colspan="3" style="text-align:center;"><div style="width:90%;height:120px;text-align:left;overflow:auto;">' . $baseInsc['CGU'] . '</div></td></tr>';
                }
                foreach ($baseInsc['Colls'] as $collId => $collName) {
                    $base_id = phrasea::baseFromColl($sbasId, $collId, $app);
                    $out .= '<tr>' .
                        '<td style="text-align:right;">' . $collName . '</td>' .
                        '<td></td>' .
                        '<td class="TD_R" style="width:200px;">' .
                        '<input style="width:15px;" class="checkbox" type="checkbox" name="demand[]" value="' . $base_id . '" >' .
                        '<span>' . $app->trans('login::register: Faire une demande d\'acces') . '</span>' .
                        '</td>' .
                        '</tr>';
                }
            }
            if ($baseInsc['CollsCGU']) {
                foreach ($baseInsc['CollsCGU'] as $collId => $collDesc) {
                    $base_id = phrasea::baseFromColl($sbasId, $collId, $app);
                    $out .= '<tr><td colspan="3" style="text-align:center;"><hr style="width:80%"/></td></tr>' .
                        '<tr><td colspan="3" style="text-align:center;">' . $app->trans('login::register: L\'acces aux bases ci-dessous implique l\'acceptation des Conditions Generales d\'Utilisation (CGU) suivantes') . '</td></tr>' .
                        '<tr>' .
                        '<td colspan="3" style="text-align:center;">' .
                        '<div style="width:90%;height:120px;text-align:left;overflow:auto;">' . $collDesc['CGU'] . '</div>' .
                        '</td>' .
                        '</tr>' .
                        '<tr >' .
                        '<td style="text-align:right;">' . $collDesc['name'] . '</td>' .
                        '<td></td>' .
                        '<td class="TD_R" style="width:200px;">' .
                        '<input style="width:15px;" class="checkbox" type="checkbox" name="demand[]" value="' . $base_id . '" >' .
                        '<span>' . $app->trans('login::register: Faire une demande d\'acces') . '</span>' .
                        '</td>' .
                        '</tr>';
                }
            }
        }
    }

    $out .= '</table>';

    return ['tab'      => $out, 'demandes' => $noDemand];
}
