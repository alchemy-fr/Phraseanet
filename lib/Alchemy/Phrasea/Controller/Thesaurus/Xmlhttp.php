<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Thesaurus;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Xmlhttp implements ControllerProviderInterface
{
    const SEARCH_REPLACE_MAXREC = 25;

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->before(function () use ($app) {
            $app['firewall']->requireAuthentication();
        });

        $controllers->match('acceptcandidates.j.php', $this->call('AcceptCandidatesJson'))
            ->before(function () use ($app) {
                $app['firewall']->requireAccessToModule('thesaurus');
            });
        $controllers->match('checkcandidatetarget.j.php', $this->call('CheckCandidateTargetJson'))
            ->before(function () use ($app) {
                $app['firewall']->requireAccessToModule('thesaurus');
            });
        $controllers->match('editing_presets.j.php', $this->call('EditingPresetsJson'));
        $controllers->match('getsy_prod.x.php', $this->call('GetSynonymsXml'));
        $controllers->match('getterm_prod.h.php', $this->call('GetTermHtml'));
        $controllers->match('getterm_prod.x.php', $this->call('GetTermXml'));
        $controllers->match('openbranch_prod.j.php', $this->call('OpenBranchJson'));
        $controllers->match('openbranches_prod.h.php', $this->call('OpenBranchesHtml'));
        $controllers->match('openbranches_prod.x.php', $this->call('OpenBranchesXml'));
        $controllers->match('replacecandidate.j.php', $this->call('ReplaceCandidateJson'))
            ->before(function () use ($app) {
                $app['firewall']->requireAccessToModule('thesaurus');
            });
        $controllers->match('search_th_term_prod.j.php', $this->call('SearchTermJson'));

        return $controllers;
    }

    public function AcceptCandidatesJson(Application $app, Request $request)
    {
        $ret = array('refresh' => array());
        $refresh = array();

        $sbas_id = $request->get('sbid');

        try {
            $databox = $app['phraseanet.appbox']->get_databox($sbas_id);
            $connbas = $databox->get_connection();

            $domct = $databox->get_dom_cterms();
            if (!($domct instanceof DOMDocument)) {
                throw new \Exception('Unable to load cterms');
            }

            $domth = $databox->get_dom_thesaurus();
            if (!($domth instanceof DOMDocument)) {
                throw new \Exception('Unable to load thesaurus');
            }

            $xpathth = new \DOMXPath($domth);

            if ($request->get("tid") == "T") {
                $q = "/thesaurus";
            } else {
                $q = "/thesaurus//te[@id='" . $request->get("tid") . "']";
            }

            if ($request->get("debug")) {
                printf("qth: %s<br/>\n", $q);
            }

            $parentnode = $xpathth->query($q)->item(0);
            if (!$parentnode) {
                throw new \Exception('Unable to find branch');
            }

            $xpathct = new \DOMXPath($domct);
            $ctchanged = $thchanged = false;

            foreach ($request->get("cid") as $cid) {
                $q = "//te[@id='" . $cid . "']";
                if ($request->get("debug")) {
                    printf("qct: %s<br/>\n", $q);
                }
                $ct = $xpathct->query($q)->item(0);
                if (!$ct) {
                    continue;
                }
                if ($request->get("typ") == "TS") {
                    // importer tt la branche candidate comme nouveau ts
                    $nid = $parentnode->getAttribute("nextid");
                    $parentnode->setAttribute("nextid", (int) $nid + 1);

                    $oldid = $ct->getAttribute("id");
                    $te = $domth->importNode($ct, true);
                    $chgids = array();
                    if (($pid = $parentnode->getAttribute("id")) == "") {
                        $pid = "T" . $nid;
                    } else {
                        $pid .= "." . $nid;
                    }

                    $this->renumerate($request->get('piv'), $te, $pid, $chgids);
                    $te = $parentnode->appendChild($te);

                    if ($request->get("debug")) {
                        printf("newid=%s<br/>\n", $te->getAttribute("id"));
                    }

                    $soldid = str_replace(".", "d", $oldid) . "d";
                    $snewid = str_replace(".", "d", $pid) . "d";
                    $l = strlen($soldid) + 1;

                    $sql = "UPDATE thit
                            SET value=CONCAT('$snewid', SUBSTRING(value FROM $l))
                            WHERE value LIKE :like";

                    if ($request->get("debug")) {
                        printf("soldid=%s ; snewid=%s<br/>\nsql=%s<br/>\n", $soldid, $snewid, $sql);
                    } else {
                        $stmt = $connbas->prepare($sql);
                        $stmt->execute(array(':like' => $soldid . '%'));
                        $stmt->closeCursor();
                    }

                    $refreshid = $parentnode->getAttribute('id');
                    $refresh['T' . $refreshid] = array(
                        'type' => 'T',
                        'sbid' => $sbas_id,
                        'id'   => $refreshid
                    );
                    $thchanged = true;

                    $refreshid = $ct->parentNode->getAttribute("id");
                    $refresh['C' . $refreshid] = array(
                        'type' => 'C',
                        'sbid' => $sbas_id,
                        'id'   => $refreshid
                    );

                    $ct->parentNode->removeChild($ct);

                    $ctchanged = true;
                } elseif ($request->get("typ") == "SY") {
                    // importer tt le contenu de la branche sous la destination
                    for ($ct2 = $ct->firstChild; $ct2; $ct2 = $ct2->nextSibling) {
                        if ($ct2->nodeType != XML_ELEMENT_NODE || $ct2->nodeName != 'sy') {
                            continue;
                        }
                        if ($request->get('debug')) {
                            printf("ct2:%s \n", var_export($ct2, true));
                        }
                        $nid = $parentnode->getAttribute("nextid");
                        $parentnode->setAttribute("nextid", (int) $nid + 1);

                        $oldid = $ct2->getAttribute("id");
                        $te = $domth->importNode($ct2, true);
                        $chgids = array();
                        if (($pid = $parentnode->getAttribute("id")) == "") {
                            // racine
                            $pid = "T" . $nid;
                        } else {
                            $pid .= "." . $nid;
                        }

                        $this->renumerate($request->get('piv'), $te, $pid, $chgids);
                        $te = $parentnode->appendChild($te);

                        if ($request->get("debug")) {
                            printf("newid=%s<br/>\n", $te->getAttribute("id"));
                        }

                        $soldid = str_replace(".", "d", $oldid) . "d";
                        $snewid = str_replace(".", "d", $pid) . "d";
                        $l = strlen($soldid) + 1;

                        $sql = "UPDATE thit
                                SET value = CONCAT('$snewid', SUBSTRING(value FROM $l))
                                WHERE value LIKE :like";

                        if ($request->get("debug")) {
                            printf("soldid=%s ; snewid=%s<br/>\nsql=%s<br/>\n", $soldid, $snewid, $sql);
                        } else {
                            $stmt = $connbas->prepare($sql);
                            $stmt->execute(array(':like' => $soldid . '%'));
                            $stmt->closeCursor();
                        }

                        $thchanged = true;
                    }

                    $refreshid = $parentnode->parentNode->getAttribute("id");
                    $refresh['T' . $refreshid] = array(
                        'type' => 'T',
                        'sbid' => $sbas_id,
                        'id'   => $refreshid
                    );

                    $refreshid = $ct->parentNode->getAttribute("id");
                    $refresh['C' . $refreshid] = array(
                        'type' => 'C',
                        'sbid' => $sbas_id,
                        'id'   => $refreshid
                    );

                    $ct->parentNode->removeChild($ct);
                    $ctchanged = true;
                }
            }
            if ($ctchanged) {
                $databox->saveCterms($domct);
            }
            if ($thchanged) {
                $databox->saveThesaurus($domth);
            }
        } catch (\Exception $e) {

        }

        $ret['refresh'] = array_values($refresh);

        return $app->json($ret);
    }

    private function renumerate($lang, $node, $id, &$chgids, $depth = 0)
    {
        $node->setAttribute("id", $id);

        if ($node->nodeType == XML_ELEMENT_NODE && $node->nodeName == "sy") {
            $node->setAttribute("lng", $lang);
        }

        $nchild = 0;

        for ($n = $node->firstChild; $n; $n = $n->nextSibling) {
            if ($n->nodeType == XML_ELEMENT_NODE && ($n->nodeName == "te" || $n->nodeName == "sy")) {
                $this->renumerate($lang, $n, $id . "." . $nchild, $chgids, $depth + 1);
                $nchild++;
            }
        }

        $node->setAttribute("nextid", $nchild);
    }

    public function CheckCandidateTargetJson(Application $app, Request $request)
    {
        $json = array();

        if (null === $sbas_id = $request->get("sbid")) {
            return $app->json($json);
        }

        $databox = $app['phraseanet.appbox']->get_databox((int) $sbas_id);

        $dom_thesau = $databox->get_dom_thesaurus();
        $meta = $databox->get_meta_structure();

        if ($dom_thesau) {
            $xpath = new \DOMXPath($dom_thesau);

            $json['cfield'] = $request->get("acf");

            // on doit verifier si le terme demande est accessible e partir de ce champ acf
            if ($request->get("acf") == '*') {
                // le champ "*" est la corbeille, il est toujours accepte
                $json['acceptable'] = true;
            } else {
                // le champ est teste d'apres son tbranch
                if ($meta && ($databox_field = $meta->get_element_by_name($request->get('acf')))) {
                    $tbranch = $databox_field->get_tbranch();
                    $q = "(" . $tbranch . ")/descendant-or-self::te[@id='" . $request->get("id") . "']";

                    if ($request->get("debug")) {
                        printf("tbranch-q = \" $q \" <br/>\n");
                    }

                    $nodes = $xpath->query($q);

                    $json['acceptable'] = ($nodes->length > 0);
                }
            }


            if ($request->get("id") == "T") {
                $q = "/thesaurus";
            } else {
                $q = "/thesaurus//te[@id='" . $request->get("id") . "']";
            }

            if ($request->get("debug")) {
                print("q:" . $q . "<br/>\n");
            }

            $nodes = $xpath->query($q);
            $json['found'] = $nodes->length;

            if ($nodes->length > 0) {
                $fullpath_html = $fullpath = "";
                for ($depth = 0, $n = $nodes->item(0); $n; $n = $n->parentNode, $depth--) {
                    if ($n->nodeName == "te") {
                        if ($request->get("debug")) {
                            printf("parent:%s<br/>\n", $n->nodeName);
                        }
                        $firstsy = $goodsy = null;
                        for ($n2 = $n->firstChild; $n2; $n2 = $n2->nextSibling) {
                            if ($n2->nodeName == "sy") {
                                $sy = $n2->getAttribute("v");
                                if (!$firstsy) {
                                    $firstsy = $sy;
                                    if ($request->get("debug")) {
                                        printf("fullpath : firstsy='%s' in %s<br/>\n", $firstsy, $n2->getAttribute("lng"));
                                    }
                                }
                                if ($n2->getAttribute("lng") == $request->get("piv")) {
                                    if ($request->get("debug")) {
                                        printf("fullpath : found '%s' in %s<br/>\n", $sy, $n2->getAttribute("lng"));
                                    }
                                    $goodsy = $sy;
                                    break;
                                }
                            }
                        }
                        if (!$goodsy) {
                            $goodsy = $firstsy;
                        }

                        $fullpath = " / " . $goodsy . $fullpath;

                        if ($depth == 0) {
                            $fullpath_html = "<span class='path_separator'> / </span><span class='main_term'>" . $goodsy . "</span>" . $fullpath_html;
                        } else {
                            $fullpath_html = "<span class='path_separator'> / </span>" . $goodsy . $fullpath_html;
                        }
                    }
                }

                if ($fullpath == "") {
                    $fullpath = "/";
                    $fullpath_html = "<span class='path_separator'> / </span>";
                }

                $json['fullpath'] = $fullpath;
                $json['fullpath_html'] = $fullpath_html;
            }
        }

        return $app->json($json);
    }

    public function EditingPresetsJson(Application $app, Request $request)
    {
        $usr_id = $app['authentication']->getUser()->get_id();

        $ret = array('parm' => array(
                'act'      => $request->get('act'),
                'sbas'     => $request->get('sbas'),
                'presetid' => $request->get('presetid'),
                'title'    => $request->get('title'),
                'f'        => $request->get('f'),
                'debug'    => $request->get('debug'),
        ));

        switch ($request->get('act')) {
            case 'DELETE':
                $sql = 'DELETE FROM edit_presets
                        WHERE edit_preset_id = :editpresetid
                            AND usr_id = :usr_id';

                $params = array(
                    ':editpresetid' => $request->get('presetid'),
                    ':usr_id'       => $usr_id
                );

                $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
                $stmt->execute($params);
                $stmt->closeCursor();

                $ret['html'] = $this->getPresetHTMLList($app, $request->get('sbas'), $usr_id);
                break;
            case 'SAVE':
                $dom = new \DOMDocument('1.0', 'UTF-8');
                $dom->standalone = true;
                $dom->preserveWhiteSpace = false;
                $dom->formatOutput = true;

                $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes" ?><edit_preset>' . $request->get('f') . '</edit_preset>';
                $dom->loadXML($xml);

                $sql = 'INSERT INTO edit_presets
                            (creation_date, sbas_id, usr_id, title, xml)
                        VALUES
                            (NOW(), :sbas_id, :usr_id, :title, :presets)';

                $params = array(
                    ':sbas_id' => $request->get('sbas'),
                    ':usr_id'  => $usr_id,
                    ':title'   => $request->get('title'),
                    ':presets' => $dom->saveXML(),
                );

                $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
                $stmt->execute($params);
                $stmt->closeCursor();

                $ret['html'] = $this->getPresetHTMLList($app, $request->get('sbas'), $usr_id);
                break;
            case 'LIST':
                $ret['html'] = $this->getPresetHTMLList($app, $request->get('sbas'), $usr_id);
                break;
            case "LOAD":
                $sql = 'SELECT edit_preset_id, creation_date, title, xml
                    FROM edit_presets
                    WHERE edit_preset_id = :edit_preset_id';

                $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
                $stmt->execute(array(':edit_preset_id' => $request->get('presetid')));
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                $stmt->closeCursor();

                $fields = array();
                if ($row && ($sx = simplexml_load_string($row['xml']))) {
                    foreach ($sx->fields->children() as $fn => $fv) {
                        if (!array_key_exists($fn, $fields)) {
                            $fields[$fn] = array();
                        }
                        $fields[$fn][] = trim($fv);
                    }
                }

                $ret['fields'] = $fields;
                break;
        }

        return $app->json($ret);
    }

    private function getPresetHTMLList(Application $app, $sbas_id, $usr_id)
    {
        $conn = \connection::getPDOConnection($app);

        $html = '';
        $sql = 'SELECT edit_preset_id, creation_date, title, xml
                FROM edit_presets
                WHERE usr_id = :usr_id AND sbas_id = :sbas_id
                ORDER BY creation_date ASC';

        $stmt = $conn->prepare($sql);
        $stmt->execute(array(
            ':sbas_id' => $sbas_id,
            ':usr_id'  => $usr_id,
        ));
        $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row) {
            if (!($sx = simplexml_load_string($row['xml']))) {
                continue;
            }
            $t_desc = array();
            foreach ($sx->fields->children() as $fn => $fv) {
                if (!array_key_exists($fn, $t_desc)) {
                    $t_desc[$fn] = trim($fv);
                } else {
                    $t_desc[$fn] .= ' ; ' . trim($fv);
                }
            }
            $desc = '';
            foreach ($t_desc as $fn => $fv) {
                $desc .= '    <p><b>' . $fn . ':&nbsp;</b>' . str_replace(array('&', '<', '>'), array('&amp;', '&lt;', '&gt;'), $fv) . '</p>' . "\n";
            }

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

        return $html;
    }

    public function GetSynonymsXml(Application $app, Request $request)
    {
        $ret = new \DOMDocument("1.0", "UTF-8");
        $ret->standalone = true;
        $ret->preserveWhiteSpace = false;
        $root = $ret->appendChild($ret->createElement("result"));
        $root->appendChild($ret->createCDATASection(var_export(array(
            'bid' => $request->get('bid'),
            'id'  => $request->get('id'),
        ), true)));

        if (null !== $request->get('bid')) {

            $databox = $app['phraseanet.appbox']->get_databox((int) $request->get('bid'));
            $dom = $databox->get_dom_thesaurus();

            if ($dom) {
                $xpath = $databox->get_xpath_thesaurus();
                $q = "/thesaurus//sy[@id='" . $request->get('id') . "']";

                if ($request->get('debug')) {
                    print("q:" . $q . "<br/>\n");
                }

                $nodes = $xpath->query($q);
                if ($nodes->length > 0) {
                    $n2 = $nodes->item(0);
                    $root->setAttribute("t", $n2->getAttribute("v"));
                }
            }
        }

        return new Response($ret->saveXML(), 200, array('Content-Type' => 'text/xml'));
    }

    public function GetTermHtml(Application $app, Request $request)
    {
        $html = '';

        if (null === $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }

        $databox = $app['phraseanet.appbox']->get_databox((int) $request->get("bid"));
        $dom = $databox->get_dom_thesaurus();

        if (!$dom) {
            return new Response('Unable to load thesaurus', 500);
        }

        $xpath = $databox->get_xpath_thesaurus();

        if ($request->get("id") == "T") {
            $q = "/thesaurus";
        } else {
            $q = "/thesaurus//te[@id='" . $request->get("id") . "']";
        }

        if ($request->get("debug")) {
            print("q:" . $q . "<br/>\n");
        }

        $nodes = $xpath->query($q);
        if ($nodes->length > 0) {
            $nts = 0;
            $tts = array();
            // on dresse la liste des termes specifiques avec comme cle le synonyme
            // dans la langue pivot
            for ($n = $nodes->item(0)->firstChild; $n; $n = $n->nextSibling) {
                if ($n->nodeName == "te") {
                    $nts++;
                    $allsy = "";
                    $tsy = array();
                    $firstksy = null;
                    $ksy = $realksy = null;
                    // on liste les sy pour fabriquer la cle
                    for ($n2 = $n->firstChild; $n2; $n2 = $n2->nextSibling) {
                        if ($n2->nodeName == "sy") {
                            $lng = $n2->getAttribute("lng");
                            $t = $n2->getAttribute("v");
                            $ksy = $n2->getAttribute("w");
                            if ($k = $n2->getAttribute("k")) {
                                //          $t .= " ($k)";
                                $ksy .= " ($k)";
                            }
                            if (!$firstksy) {
                                $firstksy = $ksy;
                            }
                            if (!$realksy && $request->get("lng") && $lng == $request->get("lng")) {
                                $realksy = $ksy;
                                $allsy = $t . ($allsy ? " ; " : "") . $allsy;

                                array_push($tsy, array(
                                    "id" => $n2->getAttribute("id"),
                                    "sy" => $t
                                ));
                            } else {
                                $allsy .= ( $allsy ? " ; " : "") . $t;
                                array_push($tsy, array(
                                    "id" => $n2->getAttribute("id"),
                                    "sy" => $t
                                ));
                            }
                        }
                    }
                    if (!$realksy) {
                        $realksy = $firstksy;
                    }
                    if ($request->get("sortsy") && $request->get("lng")) {
                        for ($uniq = 0; $uniq < 9999; $uniq++) {
                            if (!isset($tts[$realksy . "_" . $uniq])) {
                                break;
                            }
                        }
                        $tts[$realksy . "_" . $uniq] = array(
                            "id"     => $n->getAttribute("id"),
                            "allsy"  => $allsy,
                            "nchild" => $xpath->query("te", $n)->length,
                            "tsy"    => $tsy
                        );
                    } else {
                        $tts[] = array(
                            "id"     => $n->getAttribute("id"),
                            "allsy"  => $allsy,
                            "nchild" => $xpath->query("te", $n)->length,
                            "tsy"    => $tsy
                        );
                    }
                } elseif ($n->nodeName == "sy") {

                }
            }

            if ($request->get("sortsy") && $request->get("lng")) {
                ksort($tts, SORT_STRING);
            }
            if ($request->get("debug")) {
                printf("tts : <pre>%s</pre><br/>\n", var_export($tts, true));
            }

            $bid = $request->get("bid");
            foreach ($tts as $ts) {
                $tid = $ts["id"];
                $t = $ts["allsy"];
                $lt = "";
                foreach ($ts["tsy"] as $sy) {
                    $lt .= ( $lt ? " ; " : "");
                    $lt .= "<i id='TH_W." . $bid . "." . $sy["id"] . "'>";
                    $lt .= $sy["sy"];
                    $lt .= "</i>";
                }
                $html .= "<p id='TH_T." . $bid . "." . $tid . "'>";
                if ($ts["nchild"] > 0) {
                    $html .= "<u id='TH_P." . $bid . "." . $tid . "'>+</u>";
                    $html .= $lt;
                    $html .= "</p>";
                    $html .= "<div id='TH_K." . $bid . "." . $tid . "' class='c'>";
                    $html .= "loading";
                    $html .= "</div>";
                } else {
                    $html .= "<u class='w'> </u>";
                    $html .= $lt;
                    $html .= "</p>";
                }
            }
        }

        return new Response($html);
    }

    public function GetTermXml(Application $app, Request $request)
    {
        $ret = new \DOMDocument("1.0", "UTF-8");
        $ret->standalone = true;
        $ret->preserveWhiteSpace = false;
        $root = $ret->appendChild($ret->createElement("result"));
        $root->appendChild($ret->createCDATASection(var_export(array(
            "bid"    => $request->get('bid'),
            "id"     => $request->get('id'),
            "sortsy" => $request->get('sortsy'),
            "debug"  => $request->get('debug'),
        ), true)));

        $html = $root->appendChild($ret->createElement("html"));

        if (null === $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }

        $databox = $app['phraseanet.appbox']->get_databox((int) $request->get('bid'));
        $dom = $databox->get_dom_thesaurus();
        if (!$dom) {
            return new Response('Unable to load thesaurus', 500);
        }

        $xpath = $databox->get_xpath_thesaurus();

        if ($request->get("id") == "T") {
            $q = "/thesaurus";
        } else {
            $q = "/thesaurus//te[@id='" . $request->get("id") . "']";
        }

        if ($request->get("debug")) {
            print("q:" . $q . "<br/>\n");
        }

        $nodes = $xpath->query($q);
        if ($nodes->length > 0) {
            $nts = 0;
            $tts = array();
            // on dresse la liste des termes specifiques avec comme cle le synonyme
            // dans la langue pivot
            for ($n = $nodes->item(0)->firstChild; $n; $n = $n->nextSibling) {
                if ($n->nodeName == "te") {
                    $nts++;
                    $allsy = "";
                    $tsy = array();
                    $firstksy = null;
                    $ksy = $realksy = null;
                    // on liste les sy pour fabriquer la cle
                    for ($n2 = $n->firstChild; $n2; $n2 = $n2->nextSibling) {
                        if ($n2->nodeName == "sy") {
                            $lng = $n2->getAttribute("lng");
                            $t = $n2->getAttribute("v");
                            $ksy = $n2->getAttribute("w");
                            if ($k = $n2->getAttribute("k")) {
                                $ksy .= " ($k)";
                            }

                            if (!$firstksy) {
                                $firstksy = $ksy;
                            }

                            if (!$realksy && $app['locale'] && $lng == $app['locale']) {
                                $realksy = $ksy;
                                $allsy = $t . ($allsy ? " ; " : "") . $allsy;

                                array_push($tsy, array(
                                    "id" => $n2->getAttribute("id"),
                                    "sy" => $t
                                ));
                            } else {
                                $allsy .= ( $allsy ? " ; " : "") . $t;
                                array_push($tsy, array(
                                    "id" => $n2->getAttribute("id"),
                                    "sy" => $t
                                ));
                            }
                        }
                    }
                    if (!$realksy)
                        $realksy = $firstksy;

                    if ($request->get("sortsy") && $app['locale']) {
                        for ($uniq = 0; $uniq < 9999; $uniq++) {
                            if (!isset($tts[$realksy . "_" . $uniq])) {
                                break;
                            }
                        }
                        $tts[$realksy . "_" . $uniq] = array(
                            "id"     => $n->getAttribute("id"),
                            "allsy"  => $allsy,
                            "nchild" => $xpath->query("te", $n)->length,
                            "tsy"    => $tsy
                        );
                    } else {
                        $tts[] = array(
                            "id"     => $n->getAttribute("id"),
                            "allsy"  => $allsy,
                            "nchild" => $xpath->query("te", $n)->length,
                            "tsy"    => $tsy
                        );
                    }
                } elseif ($n->nodeName == "sy") {

                }
            }

            if ($request->get("sortsy") && $app['locale']) {
                ksort($tts, SORT_STRING);
            }
            if ($request->get("debug")) {
                printf("tts : <pre>%s</pre><br/>\n", var_export($tts, true));
            }

            $zhtml = "";
            $bid = $request->get("bid");
            foreach ($tts as $ts) {
                $tid = $ts["id"];
                $t = $ts["allsy"];
                $lt = "";
                foreach ($ts["tsy"] as $sy) {
                    $lt .= ( $lt ? " ; " : "");
                    $lt .= "<i id='GL_W." . $bid . "." . $sy["id"] . "'>";
                    $lt .= $sy["sy"];
                    $lt .= "</i>";
                }
                $zhtml .= "<p id='TH_T." . $bid . "." . $tid . "'>";
                if ($ts["nchild"] > 0) {
                    $zhtml .= "<u id='TH_P." . $bid . "." . $tid . "'>+</u>";
                    $zhtml .= $lt;
                    $zhtml .= "</p>";
                    $zhtml .= "<div id='TH_K." . $bid . "." . $tid . "' class='c'>";
                    $zhtml .= "loading";
                    $zhtml .= "</div>";
                } else {
                    $zhtml .= "<u class='w'> </u>";
                    $zhtml .= $lt;
                    $zhtml .= "</p>";
                }
            }
            $html->appendChild($ret->createTextNode($zhtml));
        }

        return new Response($ret->saveXML(), 200, array('Content-Type' => 'text/xml'));
    }

    public function OpenBranchJson(Application $app, Request $request)
    {
        if (null === $lng = $request->get('lng')) {
            $data = explode('_', $app['locale']);
            if (count($data) > 0) {
                $lng = $data[0];
            }
        }

        $html = '';

        $sbid = (int) $request->get('sbid');

        $tids = explode('.', $request->get('id'));
        $thid = implode('.', $tids);

        try {
            $connbas = \connection::getPDOConnection($app, $sbid);
            $dbname = \phrasea::sbas_labels($sbid, $app);

            $t_nrec = array();
            $lthid = strlen($thid);

            // count occurences
            if ($lthid == 1) {
                $dthid = str_replace('.', 'd', $thid);

                $sql = 'SELECT COUNT(DISTINCT record_id) AS n
                        FROM thit WHERE value LIKE :like ';

                $stmt = $connbas->prepare($sql);
                $stmt->execute(array(':like' => $dthid . '%'));
                $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                $stmt->closeCursor();

                if ($request->get('debug')) {
                    printf("/*\n  thid=%s\n  %s \n */\n", $thid, $sql);
                }

                foreach ($rs as $rowbas) {
                    $t_nrec[$thid] = $rowbas;
                }

                $sql = 'SELECT
                            SUBSTRING_INDEX(SUBSTR(value, ' . ($lthid + 1) . '), "d", 1) AS k ,
                            COUNT(DISTINCT record_id) AS n
                        FROM thit
                        WHERE value LIKE :like
                        GROUP BY k';

                $stmt = $connbas->prepare($sql);
                $stmt->execute(array(':like' => $dthid . '%'));
                $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                $stmt->closeCursor();

                if ($request->get('debug')) {
                    printf("/*\n  thid=%s\n  %s \n */\n", $thid, $sql);
                }

                foreach ($rs as $rowbas) {
                    $t_nrec[$thid . $rowbas['k']] = $rowbas;
                }
            } elseif (strlen($thid) > 1) {
                $dthid = str_replace('.', 'd', $thid);
                $sql = 'SELECT
                            SUBSTRING_INDEX(SUBSTR(value, ' . ($lthid) . '), \'d\', 1) AS k ,
                            COUNT(DISTINCT record_id) AS n
                        FROM thit
                        WHERE value LIKE :like
                        GROUP BY k';

                $stmt = $connbas->prepare($sql);
                $stmt->execute(array(':like' => $dthid . '%'));
                $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                $stmt->closeCursor();

                if ($request->get('debug')) {
                    printf("/*\n  thid=%s\n  %s \n */\n", $thid, $sql);
                }

                foreach ($rs as $rowbas) {
                    $t_nrec[$thid] = $rowbas;
                }

                $sql = 'SELECT
                            SUBSTRING_INDEX(SUBSTR(value, ' . ($lthid + 2) . '), \'d\', 1) AS k ,
                            COUNT(DISTINCT record_id) AS n
                        FROM thit
                        WHERE value LIKE :like
                        GROUP BY k';

                $stmt = $connbas->prepare($sql);
                $stmt->execute(array(':like' => $dthid . '%'));
                $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                $stmt->closeCursor();

                if ($request->get('debug')) {
                    printf("/*\n  thid=%s\n  %s \n */\n", $thid, $sql);
                }

                foreach ($rs as $rowbas) {
                    $t_nrec[$thid . '.' . $rowbas['k']] = $rowbas;
                }
            }

            if ($request->get('debug')) {
                printf("/* %s */\n", var_export($t_nrec, true));
            }

            $databox = $app['phraseanet.appbox']->get_databox($sbid);
            if ($request->get('type') == 'T') {
                $xqroot = 'thesaurus';
                $dom = $databox->get_dom_thesaurus();
            } else { // C
                $xqroot = 'cterms';
                $dom = $databox->get_dom_cterms();
            }

            if ($dom) {
                $term0 = '';
                $firstTerm0 = '';

                $xpath = new \DOMXPath($dom);
                if ($thid == 'T' || $thid == 'C') {
                    $q = '/' . $xqroot;
                    $term0 = $dbname;
                } else {
                    $q = '/' . $xqroot . '//te[@id=\'' . $thid . '\']';
                }
                if ($request->get('debug')) {
                    print("q:" . $q . "<br/>\n");
                }

                $nodes = $xpath->query($q);
                if ($nodes->length > 0) {
                    $node0 = $nodes->item(0);

                    $key0 = null; // key of the sy in the current language (or key of the first sy if we can't find good lng)
                    $nts0 = 0;  // count of ts under this term

                    $label = $this->buildBranchLabel($dbname, $lng, $node0, $key0, $nts0);

                    $class = '';
                    if ($nts0 > 0) {
                        $class .= ( $class == '' ? '' : ' ') . 'expandable';
                    }
                    if ($request->get('last')) {
                        $class .= ( $class == '' ? '' : ' ') . 'last';
                    }

                    $html .= '<LI id="' . $request->get('type') . 'X_P.' . $sbid . '.' . $thid . '" class="' . $class . '" loaded="1">' . "\n";
                    $html .= '  <div class="hitarea expandable-hitarea"></div>' . "\n";
                    $html .= '  <span>' . $label . '</span>';

                    if (isset($t_nrec[$thid])) {
                        $html .= ' <I>' . $t_nrec[$thid]['n'] . '</I>';
                    }

                    $html .= "\n";

                    // on dresse la liste des termes specifiques avec comme cle le synonyme dans la langue pivot
                    $nts = 0;
                    $tts = array();
                    for ($n = $node0->firstChild; $n; $n = $n->nextSibling) {
                        if ($n->nodeName == 'te' && !$n->getAttribute('delbranch') && substr($n->getAttribute('id'), 0, 1) != 'R') {
                            $nts++;

                            $key0 = null; // key of the sy in the current language (or key of the first sy if we can't find good lng)
                            $nts0 = 0;  // count of ts under this term

                            $label = $this->buildBranchLabel($dbname, $lng, $n, $key0, $nts0);

                            for ($uniq = 0; $uniq < 9999; $uniq++) {
                                if (!isset($tts[$key0 . '_' . $uniq])) {
                                    break;
                                }
                            }
                            $tts[$key0 . '_' . $uniq] = array(
                                'label' => $label,
                                'nts'   => $nts0,
                                'n'     => $n
                            );
                        }
                    }

                    if ($request->get('debug')) {
                        printf("tts(%s) : <pre>%s</pre><br/>\n", $nts, var_export($tts, true));
                    }

                    if ($nts > 0) {
                        $field0 = $node0->getAttribute('field');
                        if ($field0) {
                            $field0 = 'field="' . $field0 . '"';
                        }

                        $html .= '<UL ' . $field0 . '>' . "\n";

                        if ($request->get('sortsy') && $request->get('lng')) {
                            ksort($tts, SORT_STRING);
                        } elseif ($request->get('type') == 'C') {
                            $tts = array_reverse($tts);
                        }
                        if ($request->get('debug')) {
                            printf("%s: type=%s : <pre>%s</pre><br/>\n", __LINE__, $request->get('type'), var_export($tts, true));
                        }

                        foreach ($tts as $ts) {
                            $class = '';
                            if ($ts['nts'] > 0) {
                                $class .= ( $class == '' ? '' : ' ') . 'expandable';
                            }
                            if (--$nts == 0) {
                                $class .= ( $class == '' ? '' : ' ') . 'last';
                            }

                            $tid = $ts['n']->getAttribute('id');

                            $html .= '    <LI id="' . $request->get('type') . 'X_P.' . $sbid . '.' . $tid . '" class="' . $class . '">' . "\n";
                            if ($ts['nts'] > 0) {
                                $html .= '<div class="hitarea expandable-hitarea" />' . "\n";
                            } else {
                                $html .= '<div></div>' . "\n";
                            }

                            $html .= '<span>' . $ts['label'] . '</span>';

                            if (isset($t_nrec[$tid])) {
                                $html .= ' <I>' . $t_nrec[$tid]['n'] . '</I>';
                            }

                            $html .= "\n";

                            if ($ts['nts'] > 0) {
                                $html .= '<UL style="display:none">loading</UL>' . "\n";
                            }

                            $html .= '</LI>' . "\n";
                        }
                        $html .= '</UL>' . "\n";
                    }

                    $html .= '</LI>' . "\n";
                }
            }
        } catch (\Exception $e) {

        }

        return $app->json(array('parm' => array(
            'sbid'   => $request->get('sbid'),
            'type'   => $request->get('type'),
            'id'     => $request->get('id'),
            'lng'    => $request->get('lng'),
            'sortsy' => $request->get('sortsy'),
            'debug'  => $request->get('debug'),
            'root'   => $request->get('root'),
            'last'   => $request->get('last'),
        ), 'html' => $html));
    }

    private function buildBranchLabel($dbname, $language, $n, &$key0, &$nts0)
    {
        $key0 = null;  // key of the sy in the current language
        // (or key of the first sy if we can't find good lng)
        $label = '';
        $nts0 = 0;  //

        if (!$n->getAttribute('id')) {
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
                    if ($k = $n2->getAttribute('k')) {
                        $key .= ' (' . $k . ')';
                    }
                    if (!$key0) {
                        $key0 = $key;
                    }
                    $class = $n2->getAttribute('bold') ? 'class="h"' : '';
                    if (!$lngfound && $lng == $language) {
                        $key0 = $key;
                        $lngfound = true;

                        $label = '<span ' . $class . '>' . $t . '</span>' . ($label == '' ? '' : ' <span class="separator">;</span> ') . $label;
                    } else {
                        $label = $label . ($label == '' ? '' : ' ; ') . '<span ' . $class . '>' . $t . '</span>';
                    }
                } elseif ($n2->nodeName == 'te') {
                    $nts0++;
                }
            }
        }

        return $label;
    }

    public function OpenBranchesHtml(Application $app, Request $request)
    {
        if (null === $mod = $request->get('mod')) {
            $mod = 'TREE';
        }

        if (null === $bid = $request->get('bid')) {
            return new Response('Missing bid parameter', 400);
        }

        $html = '';

        $databox = $app['phraseanet.appbox']->get_databox((int) $bid);
        $dom = $databox->get_dom_thesaurus();

        if (!$dom) {
            return new Response('Unable to load thesaurus', 500);
        }

        $xpath = $databox->get_xpath_thesaurus();
        $q = '/thesaurus';

        if ($request->get('debug')) {
            print('q:' . $q . '<br/>\n');
        }
        if (($znode = $xpath->query($q)->item(0))) {
            $q2 = '//sy';
            if ($request->get('t')) {

                $t = $this->splitTermAndContext($request->get('t'));
                $q2 = 'starts-with(@w, \'' . \thesaurus::xquery_escape($app['unicode']->remove_indexer_chars($t[0])) . '\')';
                if ($t[1]) {
                    $q2 .= ' and starts-with(@k, \'' . \thesaurus::xquery_escape($app['unicode']->remove_indexer_chars($t[1])) . '\')';
                }
                $q2 = '//sy[' . $q2 . ']';
            }

            $nodes = $xpath->query($q2, $znode);
            if ($mod == 'TREE') {
                for ($i = 0; $i < $nodes->length; $i++) {
                    $nodes->item($i)->setAttribute('bold', '1');
                    for ($n = $nodes->item($i)->parentNode; $n && $n->nodeType == XML_ELEMENT_NODE && $n->nodeName == 'te'; $n = $n->parentNode) {
                        $n->setAttribute('open', '1');
                    }
                }

                $html = '';
                $this->getBranchesHTML($bid, $znode, $html, 0);
            } else {
                $html = '';
                $bid = $request->get('bid');
                for ($i = 0; $i < $nodes->length; $i++) {
                    $n = $nodes->item($i);
                    $t = $n->getAttribute('v');
                    $tid = $n->getAttribute('id');

                    $html .= '<p id=\'TH_T.' . $bid . '.' . $tid . '\'>';
                    $html .= '<b id=\'TH_W.' . $bid . '.' . $tid . '\'>' . $t . '</b>';
                    $html .= '</p>';
                }
            }
            if ($request->get('debug')) {
                printf('zhtml=%s<br/>\n', $html);
            }
        }

        return new Response($html);
    }

    private function splitTermAndContext($word)
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

        return array($term, $context);
    }

    private function getBranchesHTML($bid, $srcnode, &$html, $depth)
    {
        $tid = $srcnode->getAttribute('id');
        $class = 'h';
        if ($depth > 0) {
            $nts = 0;
            $allsy = '';
            for ($n = $srcnode->firstChild; $n; $n = $n->nextSibling) {
                if ($n->nodeName == 'sy') {
                    $t = $n->getAttribute('v');
                    if ($n->getAttribute('bold')) {
                        $allsy .= ( $allsy ? ' ; ' : '') . '<b id=\'TH_W.' . $bid . '.' . $n->getAttribute('id') . '\'>' . $t . '</b>';
                    } else {
                        $allsy .= ( $allsy ? ' ; ' : '') . '<i id=\'TH_W.' . $bid . '.' . $n->getAttribute('id') . '\' >' . $t . '</i>';
                    }
                } elseif ($n->nodeName == 'te') {
                    $nts++;
                }
            }
            if ($allsy == '') {
                $allsy = '<i id=\'TH_W.' . $bid . '.' . $tid . '\'>THESAURUS</i>';
            }

            if ($nts > 0) {
                $html .= '<p id=\'TH_T.' . $bid . '.' . $tid . '\'>';
                $html .= '<u id=\'TH_P.' . $bid . '.' . $tid . '\'>...</u>';
                $html .= $allsy;
                $html .= '</p>';
                $class = 'h';
            } else {
                $html .= '<p id=\'TH_T.' . $bid . '.' . $tid . '\'>';
                $html .= '<u class=\'w\'> </u>';
                $html .= $allsy;
                $html .= '</p>';
                $class = 'c';
            }
            $html .= '<div id=\'TH_K.' . $bid . '.' . $tid . '\' class=\'' . $class . '\'>';
        }

        for ($n = $srcnode->firstChild; $n; $n = $n->nextSibling) {
            if ($n->nodeName == 'te') {
                if ($n->getAttribute('open')) {
                    $this->getBranchesHTML($bid, $n, $html, $depth + 1);
                }
            }
        }

        if ($depth > 0) {
            $html .= '</div>';
        }
    }

    public function OpenBranchesXml(Application $app, Request $request)
    {
        if (null === $mod = $request->get('mod')) {
            $mod = 'TREE';
        }

        $ret = new \DOMDocument('1.0', 'UTF-8');
        $ret->standalone = true;
        $ret->preserveWhiteSpace = false;
        $root = $ret->appendChild($ret->createElement('result'));
        $root->appendChild($ret->createCDATASection(var_export(array(
                'bid'   => $request->get('bid'),
                't'     => $request->get('t'),
                'mod'   => $request->get('mod'),
                'debug' => $request->get('debug'),
                    ), true)));

        $html = $root->appendChild($ret->createElement('html'));

        if (null === $bid = $request->get('bid')) {
            return new Response('Missing bid parameter', 400);
        }

        $databox = $app['phraseanet.appbox']->get_databox((int) $bid);
        $dom = $databox->get_dom_thesaurus();

        if (!$dom) {
            return new Response('Unable to load thesaurus', 500);
        }

        $xpath = $databox->get_xpath_thesaurus();
        $q = '/thesaurus';

        if ($request->get('debug')) {
            print('q:' . $q . '<br/>\n');
        }

        if (($znode = $xpath->query($q)->item(0))) {
            $q2 = '//sy';
            if ($request->get('t')) {
                $t = $this->splitTermAndContext($request->get('t'));
                $q2 = 'starts-with(@w, \'' . \thesaurus::xquery_escape($app['unicode']->remove_indexer_chars($t[0])) . '\')';
                if ($t[1])
                    $q2 .= ' and starts-with(@k, \'' . \thesaurus::xquery_escape($app['unicode']->remove_indexer_chars($t[1])) . '\')';
                $q2 = '//sy[' . $q2 . ']';
            }
            if ($request->get('debug')) {
                print('q2:' . $q2 . '<br/>\n');
            }
            $nodes = $xpath->query($q2, $znode);
            if ($mod == 'TREE') {
                for ($i = 0; $i < $nodes->length; $i++) {
                    $nodes->item($i)->setAttribute('bold', '1');
                    for ($n = $nodes->item($i)->parentNode; $n && $n->nodeType == XML_ELEMENT_NODE && $n->nodeName == 'te'; $n = $n->parentNode) {
                        $n->setAttribute('open', '1');
                        if ($request->get('debug')) {
                            printf('opening node te id=%s<br/>\n', $n->getAttribute('id'));
                        }
                    }
                }

                $zhtml = '';
                $this->getBrancheXML($bid, $znode, $zhtml, 0);
            } else {
                $zhtml = '';
                for ($i = 0; $i < $nodes->length; $i++) {
                    $n = $nodes->item($i);
                    $t = $n->getAttribute('v');
                    $tid = $n->getAttribute('id');

                    $zhtml .= '<p id=\'TH_T.' . $bid . '.' . $tid . '\'>';
                    $zhtml .= '<b id=\'GL_W.' . $bid . '.' . $tid . '\'>' . $t . '</b>';
                    $zhtml .= '</p>';
                }
            }
            if ($request->get('debug')) {
                printf('zhtml=%s<br/>\n', $zhtml);
            }
            $html->appendChild($ret->createTextNode($zhtml));
        }

        return new Response($zhtml, 200, array('Content-Type' => 'text/xml'));
    }

    private function getBrancheXML($bid, $srcnode, &$html, $depth)
    {
        $tid = $srcnode->getAttribute('id');
        $class = 'h';
        if ($depth > 0) {
            $nts = 0;
            $allsy = '';
            for ($n = $srcnode->firstChild; $n; $n = $n->nextSibling) {
                if ($n->nodeName == 'sy') {
                    $t = $n->getAttribute('v');
                    if ($n->getAttribute('bold')) {
                        $allsy .= ( $allsy ? ' ; ' : '') . '<b id=\'GL_W.' . $bid . '.' . $n->getAttribute('id') . '\'>' . $t . '</b>';
                    } else {
                        $allsy .= ( $allsy ? ' ; ' : '') . '<i id=\'GL_W.' . $bid . '.' . $n->getAttribute('id') . '\' >' . $t . '</i>';
                    }
                } elseif ($n->nodeName == 'te') {
                    $nts++;
                }
            }
            if ($allsy == '') {
                $allsy = '<i id=\'GL_W.' . $bid . '.' . $tid . '\'>THESAURUS</i>';
            }

            if ($nts > 0) {
                $html .= '<p id=\'TH_T.' . $bid . '.' . $tid . '\'>';
                $html .= '<u id=\'TH_P.' . $bid . '.' . $tid . '\'>...</u>';
                $html .= $allsy;
                $html .= '</p>';
                $class = 'h';
            } else {
                $html .= '<p id=\'TH_T.' . $bid . '.' . $tid . '\'>';
                $html .= '<u class=\'w\'> </u>';
                $html .= $allsy;
                $html .= '</p>';
                $class = 'c';
            }
            $html .= '<div id=\'TH_K.' . $bid . '.' . $tid . '\' class=\'' . $class . '\'>';
        }

        for ($n = $srcnode->firstChild; $n; $n = $n->nextSibling) {
            if ($n->nodeName == 'te') {
                if ($n->getAttribute('open')) {
                    $this->getBrancheXML($bid, $n, $html, $depth + 1);
                }
            }
        }

        if ($depth > 0)
            $html .= '</div>';
    }

    public function ReplaceCandidateJson(Application $app, Request $request)
    {
        $tsbas = array();

        $ret = array(
            'ctermsDeleted'    => array(),
            'maxRecsUpdatable' => self::SEARCH_REPLACE_MAXREC,
            'nRecsToUpdate'    => 0,
            'nRecsUpdated'     => 0,
            'msg'              => ''
        );

        foreach ($request->get('id') as $id) {
            $id = explode('.', $id);
            $sbas_id = array_shift($id);
            if (!array_key_exists('b' . $sbas_id, $tsbas)) {
                $tsbas['b' . $sbas_id] = array(
                    'sbas_id' => (int) $sbas_id,
                    'tids'    => array(),
                    'domct'   => null,
                    'tvals'   => array(),
                    'lid'     => '',
                    'trids'   => array()
                );
            }
            $tsbas['b' . $sbas_id]['tids'][] = implode('.', $id);
        }

        if ($request->get('debug')) {
            var_dump($tsbas);
        }

        $appbox = $app['phraseanet.appbox'];

        // first, count the number of records to update
        foreach ($tsbas as $ksbas => $sbas) {

            /* @var $databox databox */
            try {
                $databox = $appbox->get_databox($sbas['sbas_id']);
                $connbas = $databox->get_connection();
                // $domth = $databox->get_dom_thesaurus();
                $tsbas[$ksbas]['domct'] = $databox->get_dom_cterms();
            } catch (\Exception $e) {
                continue;
            }

            if (!$tsbas[$ksbas]['domct']) {
                continue;
            }

            $lid = '';
            $xpathct = new \DOMXPath($tsbas[$ksbas]['domct']);

            foreach ($sbas['tids'] as $tid) {
                $xp = '//te[@id="' . $tid . '"]/sy';
                $nodes = $xpathct->query($xp);
                if ($nodes->length == 1) {
                    $sy = $term = $nodes->item(0);
                    $syid = str_replace('.', 'd', $sy->getAttribute('id')) . 'd';
                    $lid .= ( $lid ? ',' : '') . "'" . $syid . "'";
                    $field = $sy->parentNode->parentNode->getAttribute('field');

                    if (!array_key_exists($field, $tsbas[$ksbas]['tvals'])) {
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
            $sql = 'SELECT DISTINCT record_id AS r
                    FROM thit WHERE value IN (' . $lid . ')
                    ORDER BY record_id';
            $stmt = $connbas->prepare($sql);
            $stmt->execute();

            if ($request->get('debug')) {
                printf("(%d) sql: \n", __LINE__);
                var_dump($sql);
            }

            $tsbas[$ksbas]['trids'] = $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);
            $stmt->closeCursor();

            $ret['nRecsToUpdate'] += count($tsbas[$ksbas]['trids']);
        }

        if ($request->get('debug')) {
            printf("(%d) nRecsToUpdate = %d \ntsbas: \n", __LINE__, $ret['nRecsToUpdate']);
            print_r($tsbas);
        }

        if ($ret['nRecsToUpdate'] <= self::SEARCH_REPLACE_MAXREC) {
            foreach ($tsbas as $sbas) {

                /* @var $databox databox */
                try {
                    $databox = $appbox->get_databox($sbas['sbas_id']);
                    $connbas = $databox->get_connection();
                } catch (\Exception $e) {
                    continue;
                }

                // fix caption of records
                foreach ($sbas['trids'] as $rid) {

                    if ($request->get('debug')) {
                        printf("(%d) ======== working on record_id = %d ======= \n", __LINE__, $rid);
                    }
                    try {
                        $record = $databox->get_record($rid);

                        $metadatask = array();  // datas to keep
                        $metadatasd = array();  // datas to delete

                        /* @var $field caption_field */
                        foreach ($record->get_caption()->get_fields(null, true) as $field) {
                            $meta_struct_id = $field->get_meta_struct_id();
                            if ($request->get('debug')) {
                                printf("(%d) field '%s'  meta_struct_id=%s \n", __LINE__, $field->get_name(), $meta_struct_id);
                            }

                            /* @var $v caption_Field_Value */
                            $fname = $field->get_name();
                            if (!array_key_exists($fname, $sbas['tvals'])) {
                                foreach ($field->get_values() as $v) {
                                    if ($request->get('debug')) {
                                        printf("(%d) ...v = '%s' (meta_id=%s)  keep \n", __LINE__, $v->getValue(), $v->getId());
                                    }
                                    $metadatask[] = array(
                                        'meta_struct_id' => $meta_struct_id,
                                        'meta_id'        => $v->getId(),
                                        'value'          => $v->getValue()
                                    );
                                }
                            } else {
                                foreach ($field->get_values() as $v) {
                                    $keep = true;
                                    $vtxt = $app['unicode']->remove_indexer_chars($v->getValue());
                                    foreach ($sbas['tvals'][$fname] as $sy) {
                                        if ($sy->getAttribute('w') == $vtxt) {
                                            $keep = false;
                                        }
                                    }

                                    if ($request->get('debug')) {
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
                                            'value'          => $request->get('t') ? $request->get('t') : ''
                                        );
                                    }
                                }
                            }
                        }

                        if ($request->get('debug')) {
                            printf("(%d) metadatask: \n", __LINE__);
                            var_dump($metadatask);
                            printf("(%d) metadatasd: \n", __LINE__);
                            var_dump($metadatasd);
                        }

                        if (count($metadatasd) > 0) {
                            if (!$request->get('debug')) {
                                $record->set_metadatas($metadatasd, true);
                                $ret['nRecsUpdated']++;
                            }
                        }
                    } catch (\Exception $e) {
                        continue;
                    }
                }

                // delete the branch from the cterms
                if ($request->get('debug')) {
                    printf("cterms before :\n%s \n", $sbas['domct']->saveXML());
                }
                foreach ($sbas['tvals'] as $tval) {
                    foreach ($tval as $sy) {
                        // remove candidate from cterms
                        $te = $sy->parentNode;
                        $te->parentNode->removeChild($te);
                        $ret['ctermsDeleted'][] = $sbas['sbas_id'] . '.' . $te->getAttribute('id');
                    }
                }
                if ($request->get('debug')) {
                    printf("cterms after :\n%s \n", $sbas['domct']->saveXML());
                }
                if (!$request->get('debug')) {
                    $databox->saveCterms($sbas['domct']);
                }
            }
            $ret['msg'] = sprintf(_('prod::thesaurusTab:dlg:%d record(s) updated'), $ret['nRecsUpdated']);
        } else {
            // too many records to update
            $ret['msg'] = sprintf(_('prod::thesaurusTab:dlg:too many (%1$d) records to update (limit=%2$d)'), $ret['nRecsToUpdate'], self::SEARCH_REPLACE_MAXREC);
        }

        return $app->json($ret);
    }

    public function SearchTermJson(Application $app, Request $request)
    {
        if (null === $lng = $request->get('lng')) {
            $data = explode('_', $app['locale']);
            if (count($data) > 0) {
                $lng = $data[0];
            }
        }

        $html = '';
        $sbid = (int) $request->get('sbid');
        $dbname = '';

        try {
            $databox = $app['phraseanet.appbox']->get_databox($sbid);

            $html = "" . '<LI id="TX_P.' . $sbid . '.T" class="expandable">' . "\n";
            $html .= "\t" . '<div class="hitarea expandable-hitarea"></div>' . "\n";
            $html .= "\t" . '<span>' . \phrasea::sbas_labels($sbid, $app) . '</span>' . "\n";

            if ($request->get('t')) {
                if ($request->get('field') != '') {
                    $domth = $databox->get_dom_thesaurus();
                    $dom_struct = $databox->get_dom_structure();
                } else {
                    $domth = $databox->get_dom_thesaurus();
                }

                $q = null;
                if ($request->get('field') != '') {
                    // search only in the branch(es) linked to this field
                    if ($dom_struct) {
                        $xpath = new \DOMXPath($dom_struct);
                        if (($znode = $xpath->query('/record/description/' . $request->get('field'))->item(0))) {
                            $q = '(' . $znode->getAttribute('tbranch') . ')';
                        }
                    }
                } else {
                    // search in the whole thesaurus
                    $q = '/thesaurus';
                }

                if (($q !== null) && $domth) {
                    $xpath = new \DOMXPath($domth);

                    if ($request->get('debug'))
                        print('q:' . $q . "\n");

                    $t = $this->splitTermAndContext($request->get('t'));
                    $q2 = 'starts-with(@w, \'' . \thesaurus::xquery_escape($app['unicode']->remove_indexer_chars($t[0])) . '\')';
                    if ($t[1])
                        $q2 .= ' and starts-with(@k, \'' . \thesaurus::xquery_escape($app['unicode']->remove_indexer_chars($t[1])) . '\')';
                    $q2 = '//sy[' . $q2 . ' and @lng=\'' . $lng . '\']';

                    if ($request->get('debug'))
                        print('q2:' . $q2 . "\n");

                    $q .= $q2;
                    if ($request->get('debug'))
                        print('q:' . $q . "\n");

                    $nodes = $xpath->query($q);

                    for ($i = 0; $i < $nodes->length; $i++) {
                        $nodes->item($i)->setAttribute('bold', '1');
                        for ($n = $nodes->item($i)->parentNode; $n && $n->nodeType == XML_ELEMENT_NODE && $n->nodeName == 'te'; $n = $n->parentNode) {
                            $n->setAttribute('open', '1');
                            if ($request->get('debug'))
                                printf("opening node te id=%s \n", $n->getAttribute('id'));
                        }
                    }

                    $this->getHTMLTerm($sbid, $lng, $domth->documentElement, $html);
                }
            } else {
                $html .= "\t" . '<ul style="display: none;">loading</ul>' . "\n";
            }

            $html .= "" . '</LI>' . "\n";
        } catch (\Exception $e) {

        }

        return $app->json(array('parm' => array(
            'sbid'  => $request->get('sbid'),
            't'     => $request->get('t'),
            'field' => $request->get('field'),
            'lng'   => $request->get('lng'),
            'debug' => $request->get('debug'),
        ), 'html' => $html));
    }

    private function buildTermLabel($language, $n, &$key0, &$nts0)
    {
        $lngfound = false; // true when wet met a first synonym in the current language
        $key0 = null;  // key of the sy in the current language (or key of the first sy if we can't find good lng)
        $label = '';
        $nts0 = 0;

        // compute the label of the term, regarding the current language.
        for ($n2 = $n->firstChild; $n2; $n2 = $n2->nextSibling) {
            if ($n2->nodeName == 'sy') {

                $lng = $n2->getAttribute('lng');
                $t = $n2->getAttribute('v');
                $key = $n2->getAttribute('w');  // key of the current sy

                if ($k = $n2->getAttribute('k')) {
                    $key .= ' (' . $k . ')';
                }

                // first sy gives the key
                if (!$key0) {
                    $key0 = $key;
                }
                $class = $n2->getAttribute('bold') ? 'class="h"' : '';

                // overwrite the key if we found the good lng
                if (!$lngfound && $lng == $language) {
                    $key0 = $key;
                    $lngfound = true;

                    $label = '<span ' . $class . '>' . $t . '</span>' . ($label == '' ? '' : ' ; ') . $label;
                } else {
                    $label = $label . ($label == '' ? '' : ' ; ') . '<span ' . $class . '>' . $t . '</span>';
                }
            } elseif ($n2->nodeName == 'te') {
                $nts0++;
            }
        }

        return $label;
    }

    private function getHTMLTerm($sbid, $lng, $srcnode, &$html, $depth = 0)
    {
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

                    $label = $this->buildTermLabel($lng, $n, $key0, $nts0);

                    for ($uniq = 0; $uniq < 9999; $uniq++) {
                        if (!isset($tts[$key0 . '_' . $uniq]))
                            break;
                    }
                    $tts[$key0 . '_' . $uniq] = array('label' => $label, 'nts'   => $nts0, 'n'     => $n);
                    $ntsopened++;
                }
                $nts++;
            }
        }

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
                    if ($ts['nts'] > 0) {
                        $class .= ( $class == '' ? '' : ' ') . 'expandable';
                    }
                    if (--$ntsopened == 0) {
                        $class .= ( $class == '' ? '' : ' ') . 'last';
                    }

                    $tid = $ts['n']->getAttribute('id');

                    $html .= $tab . "\t" . '<LI id="TX_P.' . $sbid . '.' . $tid . '" class="' . $class . '">' . "\n";
                    if ($ts['nts'] > 0) {
                        $html .= $tab . "\t\t" . '<div class="hitarea expandable-hitarea" />' . "\n";
                    } else {
                        $html .= $tab . "\t\t" . '<div />' . "\n";
                    }
                    $html .= $tab . "\t\t" . '<span>' . $ts['label'] . '</span>' . "\n";

                    $this->getHTMLTerm($sbid, $lng, $ts['n'], $html, $depth + 1);

                    $html .= $tab . "\t" . '</LI>' . "\n";
                }
                $html .= $tab . '</UL>' . "\n";
            }
        }
    }

    private function call($method)
    {
        return sprintf('%s::%s', __CLASS__, $method);
    }
}
