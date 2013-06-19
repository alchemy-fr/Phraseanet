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

use Alchemy\Phrasea\Application as PhraseaApplication;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Thesaurus implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->before(function() use ($app) {
            $app['firewall']->requireAuthentication();
            $app['firewall']->requireAccessToModule('thesaurus');
        });

        $controllers->match('/', $this->call('indexThesaurus'))->bind('thesaurus');
        $controllers->match('accept.php', $this->call('accept'));
        $controllers->match('export_text.php', $this->call('exportText'));
        $controllers->match('export_text_dlg.php', $this->call('exportTextDialog'));
        $controllers->match('export_topics.php', $this->call('exportTopics'));
        $controllers->match('export_topics_dlg.php', $this->call('exportTopicsDialog'));
        $controllers->match('import.php', $this->call('import'));
        $controllers->match('import_dlg.php', $this->call('importDialog'));
        $controllers->match('linkfield.php', $this->call('linkFieldStep1'));
        $controllers->match('linkfield2.php', $this->call('linkFieldStep2'));
        $controllers->match('linkfield3.php', $this->call('linkFieldStep3'));
        $controllers->match('loadth.php', $this->call('loadThesaurus'))->bind('thesaurus_loadth');
        $controllers->match('newsy_dlg.php', $this->call('newSynonymDialog'));
        $controllers->match('newterm.php', $this->call('newTerm'));
        $controllers->match('properties.php', $this->call('properties'));
        $controllers->match('search.php', $this->call('search'));
        $controllers->match('thesaurus.php', $this->call('thesaurus'))->bind('thesaurus_thesaurus');

        $controllers->match('xmlhttp/accept.x.php', $this->call('acceptXml'));
        $controllers->match('xmlhttp/acceptcandidates.x.php', $this->call('acceptCandidatesXml'));
        $controllers->match('xmlhttp/changesylng.x.php', $this->call('changeSynonymLanguageXml'));
        $controllers->match('xmlhttp/changesypos.x.php', $this->call('changeSynonymPositionXml'));
        $controllers->match('xmlhttp/deletenohits.x.php', $this->call('removeNoHitXml'));
        $controllers->match('xmlhttp/delsy.x.php', $this->call('removeSynonymXml'));
        $controllers->match('xmlhttp/delts.x.php', $this->call('removeSpecificTermXml'));
        $controllers->match('xmlhttp/gethtmlbranch.x.php', $this->call('getHtmlBranchXml'));
        $controllers->match('xmlhttp/getsy.x.php', $this->call('getSynonymXml'));
        $controllers->match('xmlhttp/getterm.x.php', $this->call('getTermXml'));
        $controllers->match('xmlhttp/killterm.x.php', $this->call('killTermXml'));
        $controllers->match('xmlhttp/newsy.x.php', $this->call('newSynonymXml'));
        $controllers->match('xmlhttp/newts.x.php', $this->call('newSpecificTermXml'));
        $controllers->match('xmlhttp/openbranches.x.php', $this->call('openBranchesXml'));
        $controllers->match('xmlhttp/reject.x.php', $this->call('RejectXml'));
        $controllers->match('xmlhttp/searchcandidate.x.php', $this->call('searchCandidateXml'));
        $controllers->match('xmlhttp/searchnohits.x.php', $this->call('searchNoHitsXml'));

        return $controllers;
    }

    public function accept(Application $app, Request $request)
    {
        if (null === $bid = $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }

        $dom = $this->getXMLTerm($app, $bid, $request->get('src'), 'CT', $request->get('piv'), '0', null, '1', null);

        $cterm_found = (int) $dom->documentElement->getAttribute('found');

        $fullpath_src = $fullpath_tgt = $nts = $cfield = $term_found = $acceptable = null;

        if ($cterm_found) {
            $fullpath_src = $dom->getElementsByTagName("fullpath_html")->item(0)->firstChild->nodeValue;
            $nts = $dom->getElementsByTagName("ts_list")->item(0)->getAttribute("nts");

            if (($cfield = $dom->getElementsByTagName("cfield")->item(0))) {
                if ($cfield->getAttribute("delbranch")) {
                    $cfield = '*';
                } else {
                    $cfield = $cfield->getAttribute("field");
                }
            } else {
                $cfield = null;
            }

            $dom = $this->getXMLTerm($app, $bid, $request->get('tgt'), 'TH', $request->get('piv'), '0', null, '1', $cfield);

            $term_found = (int) $dom->documentElement->getAttribute('found');

            if ($term_found) {
                $fullpath_tgt = $dom->getElementsByTagName("fullpath_html")->item(0)->firstChild->nodeValue;
                $acceptable = (int) $dom->getElementsByTagName("cfield")->item(0)->getAttribute("acceptable");
            }
        }

        return $app['twig']->render('thesaurus/accept.html.twig', array(
            'dlg'          => $request->get('dlg'),
            'bid'          => $request->get('bid'),
            'piv'          => $request->get('piv'),
            'src'          => $request->get('src'),
            'tgt'          => $request->get('tgt'),
            'cterm_found'  => $cterm_found,
            'term_found'   => $term_found,
            'cfield'       => $cfield,
            'nts'          => $nts,
            'fullpath_tgt' => $fullpath_tgt,
            'fullpath_src' => $fullpath_src,
            'acceptable'   => $acceptable,
        ));
    }

    public function exportText(Application $app, Request $request)
    {
        $thits = $tnodes = array();
        $output = '';

        if (null === $bid = $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }

        if ($request->get("typ") == "TH" || $request->get("typ") == "CT") {
            try {
                $databox = $app['phraseanet.appbox']->get_databox((int) $bid);
                $connbas = \connection::getPDOConnection($app, $bid);

                if ($request->get("typ") == "TH") {
                    $domth = $databox->get_dom_thesaurus();
                } else {
                    $domth = $databox->get_dom_cterms();
                }

                if ($domth) {
                    $sql = "SELECT value, SUM(1) as hits FROM thit GROUP BY value";

                    $stmt = $connbas->prepare($sql);
                    $stmt->execute();
                    $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                    $stmt->closeCursor();

                    foreach ($rs as $rowbas2) {
                        $thits[str_replace('d', '.', $rowbas2["value"])] = $rowbas2["hits"];
                    }

                    $xpathth = new \DOMXPath($domth);
                    if ($request->get("id") == "T") {
                        $q = "/thesaurus";
                    } elseif ($request->get("id") == "C") {
                        $q = "/cterms";
                    } else {
                        $q = "//te[@id='" . $request->get("id") . "']";
                    }
                    $this->export0($xpathth->query($q)->item(0), $tnodes, $thits, $output, $request->get('iln'), $request->get('hit'), $request->get('ilg'), $request->get('osl'));
                }
            } catch (\Exception $e) {

            }
        }

        return $app['twig']->render('thesaurus/export-text.html.twig', array(
            'output'  => $output,
            'smp' => $request->get('smp'),
        ));
    }

    private function printTNodes(&$output, &$tnodes, $iln, $hit, $ilg, $osl)
    {
        $numlig = $iln == "1";
        $hits = $hit == "1";
        $ilg = $ilg == "1";
        $oneline = $osl == "1";

        $ilig = 1;
        foreach ($tnodes as $node) {
            $tabs = str_repeat("\t", $node["depth"]);
            switch ($node["type"]) {
                case "ROOT":
                    if ($numlig) {
                        $output .= $ilig ++ . "\t";
                    }
                    if ($hits && ! $oneline) {
                        $output .= "\t";
                    }
                    $output .= $tabs . $node["name"] . "\n";
                    break;
                case "TRASH":
                    if ($numlig) {
                        $output .= $ilig ++ . "\t";
                    }
                    if ($hits && ! $oneline) {
                        $output .= "\t";
                    }
                    $output .= $tabs . "{TRASH}\n";
                    break;
                case "FIELD":
                    if ($numlig) {
                        $output .= $ilig ++ . "\t";
                    }
                    if ($hits && ! $oneline) {
                        $output .= "\t";
                    }
                    $output .= $tabs . $node["name"] . "\n";
                    break;
                case "TERM":
                    $isyn = 0;
                    if ($oneline) {
                        if ($numlig) {
                            $output .= $ilig ++ . "\t";
                        }
                        $output .= $tabs;
                        $isyn = 0;
                        foreach ($node["syns"] as $syn) {
                            if ($isyn > 0) {
                                $output .= " ; ";
                            }
                            $output .= $syn["v"];
                            if ($ilg) {
                                $output .= " [" . $syn["lng"] . "]";
                            }
                            if ($hits) {
                                $output .= " [" . $syn["hits"] . "]";
                            }
                            $isyn ++;
                        }
                        $output .= "\n";
                    } else {
                        $isyn = 0;
                        foreach ($node["syns"] as $syn) {
                            if ($numlig) {
                                $output .= $ilig ++ . "\t";
                            }
                            if ($hits) {
                                $output .= $syn["hits"] . "\t";
                            }
                            $output .= $tabs;

                            if ($isyn > 0) {
                                $output .= "; ";
                            }

                            $output .= $syn["v"];

                            if ($ilg) {
                                $output .= " [" . $syn["lng"] . "]";
                            }
                            $output .= "\n";
                            $isyn ++;
                        }
                    }
                    break;
            }
            if (! $oneline) {
                if ($numlig) {
                    $output .= $ilig ++ . "\t";
                }
                $output .= "\n";
            }
        }
    }

    private function exportNode(&$node, &$tnodes, &$thits, $depth)
    {
        if ($node->nodeType == XML_ELEMENT_NODE) {
            if (($nname = $node->nodeName) == "thesaurus" || $nname == "cterms") {
                $tnodes[] = array(
                    "type"  => "ROOT",
                    "depth" => $depth,
                    "name"  => $nname,
                    "cdate" => $node->getAttribute("creation_date"),
                    "mdate" => $node->getAttribute("modification_date")
                );
            } elseif (($fld = $node->getAttribute("field"))) {
                if ($node->getAttribute("delbranch")) {
                    $tnodes[] = array(
                        "type"    => "TRASH",
                        "depth"   => $depth,
                        "name"    => $fld
                    );
                } else {
                    $tnodes[] = array(
                        "type"  => "FIELD",
                        "depth" => $depth,
                        "name"  => $fld
                    );
                }
            } else {
                $tsy = array();
                for ($n = $node->firstChild; $n; $n = $n->nextSibling) {
                    if ($n->nodeName == "sy") {
                        $id = $n->getAttribute("id");
                        if (array_key_exists($id . '.', $thits)) {
                            $hits = 0 + $thits[$id . '.'];
                        } else {
                            $hits = 0;
                        }

                        $tsy[] = array(
                            "v"       => $n->getAttribute("v"),
                            "lng"     => $n->getAttribute("lng"),
                            "hits"    => $hits
                        );
                    }
                }
                $tnodes[] = array("type"  => "TERM", "depth" => $depth, "syns"  => $tsy);
            }
        }
    }

    private function export0($znode, &$tnodes, &$thits, &$output, $iln, $hit, $ilg, $osl)
    {
        $nodes = array();
        $depth = 0;

        for ($node = $znode->parentNode; $node; $node = $node->parentNode) {
            if ($node->nodeType == XML_ELEMENT_NODE)
                $nodes[] = $node;
        }
        $nodes = array_reverse($nodes);

        foreach ($nodes as $depth => $node) {
            $this->exportNode($node, $tnodes, $thits, $depth);
        }

        $this->export($znode, $tnodes, $thits, count($nodes));
        $this->printTNodes($output, $tnodes, $iln, $hit, $ilg, $osl);
    }

    private function export($node, &$tnodes, &$thits, $depth = 0)
    {
        if ($node->nodeType == XML_ELEMENT_NODE) {
            $this->exportNode($node, $tnodes, $thits, $depth);
        }
        for ($n = $node->firstChild; $n; $n = $n->nextSibling) {
            if ($n->nodeName == "te") {
                $this->export($n, $tnodes, $thits, $depth + 1);
            }
        }
    }

    public function exportTextDialog(Application $app, Request $request)
    {
        return $app['twig']->render('thesaurus/export-text-dialog.html.twig', array(
            'dlg' => $request->get('dlg'),
            'bid' => $request->get('bid'),
            'typ' => $request->get('typ'),
            'piv' => $request->get('piv'),
            'id'  => $request->get('id'),
        ));
    }

    public function exportTopics(Application $app, Request $request)
    {
        $lng = $app['locale'];
        $obr = explode(';', $request->get('obr'));

        $t_lng = array();

        if ($request->get('ofm') == 'tofiles') {
            $t_lng = array_map(function ($code) {
                $lng_code = explode('_', $code);

                return $lng_code[0];
            }, array_keys(PhraseaApplication::getAvailableLanguages()));
        } else {
            $t_lng[] = $request->get('piv');
        }

        switch ($request->get('obrf')) {
            case 'from_itf_closable':
                $default_display = 'closed';
                $opened_display = 'opened';
                break;
            case 'from_itf_static':
                $default_display = 'closed';
                $opened_display = 'static';
                break;
            case 'all_opened_closable':
                $default_display = 'opened';
                $opened_display = '';
                break;
            case 'all_opened_static':
                $default_display = 'static';
                $opened_display = '';
                break;
            case 'all_closed':
                $default_display = 'closed';
                $opened_display = '';
                break;
        }

        $now = date('YmdHis');
        $lngs = array();
        try {
            $databox = $app['phraseanet.appbox']->get_databox((int) $request->get("bid"));
            if ($request->get("typ") == "TH") {
                $domth = $databox->get_dom_thesaurus();
            } else {
                $domth = $databox->get_dom_cterms();
            }

            if ($domth) {
                $xpathth = new \DOMXPath($domth);
                if ($request->get("id") == "T") {
                    $q = "/thesaurus";
                } elseif ($request->get("id") == "C") {
                    $q = "/cterms";
                } else {
                    $q = "//te[@id='" . $request->get("id") . "']";
                }

                if ($request->get('ofm') == 'toscreen') {
                    printf("<pre style='font-size: 12px;'>\n");
                }

                foreach ($t_lng as $lng) {
                    $dom = new \DOMDocument("1.0", "UTF-8");
                    $dom->standalone = true;
                    $dom->preserveWhiteSpace = false;
                    $dom->formatOutput = true;
                    $root = $dom->appendChild($dom->createElementNS('www.phraseanet.com', 'phraseanet:topics'));

                    $root->appendChild($dom->createComment(sprintf(_('thesaurus:: fichier genere le %s'), $now)));

                    $root->appendChild($dom->createElement('display'))
                        ->appendChild($dom->createElement('defaultview'))
                        ->appendChild($dom->createTextNode($default_display));

                    $this->export0Topics($xpathth->query($q)->item(0), $dom, $root, $lng, $request->get("srt"), $request->get("sth"), $request->get("sand"), $opened_display, $obr);

                    if ($request->get("ofm") == 'toscreen') {
                        $lngs[$lng] = str_replace(array('&', '<', '>'), array('&amp;', '&lt;', '&gt;'), $dom->saveXML());
                    } elseif ($request->get("ofm") == 'tofiles') {
                        $fname = 'topics_' . $lng . '.xml';

                        @rename($app['root.path'] . '/config/topics/' . $fname, $app['root.path'] . '/config/topics/topics_' . $lng . '_BKP_' . $now . '.xml');

                        if ($dom->save($app['root.path'] . '/config/topics/' . $fname)) {
                            $lngs[$lng] = \p4string::MakeString(sprintf(_('thesaurus:: fichier genere : %s'), $fname));
                        } else {
                            $lngs[$lng] = \p4string::MakeString(_('thesaurus:: erreur lors de l\'enregsitrement du fichier'));
                        }
                    }
                }
            }
        } catch (\Exception $e) {

        }

        return $app['twig']->render('thesaurus/export-topics.html.twig', array(
            'lngs' => $lngs,
            'ofm'  => $request->get('ofm'),
        ));
    }

    private function export0Topics($znode, &$dom, &$root, $lng, $srt, $sth, $sand, $opened_display, $obr)
    {
        $topics = $root->appendChild($dom->createElement('topics'));
        $this->doExportTopics($znode, $dom, $topics, '', $lng, $srt, $sth, $sand, $opened_display, $obr, 0);
    }

    private function doExportTopics($node, &$dom, &$topics, $prevQuery, $lng, $srt, $sth, $sand, $opened_display, $obr, $depth = 0)
    {
        $ntopics = 0;
        if ($node->nodeType == XML_ELEMENT_NODE) {
            $t_node = array();
            $t_sort = array();
            $i = 0;
            for ($n = $node->firstChild; $n; $n = $n->nextSibling) {
                if ($n->nodeName == "te") {
                    $ntopics ++;
                    $label0 = $label = "";
                    $query0 = $query = "";
                    for ($n2 = $n->firstChild; $n2; $n2 = $n2->nextSibling) {
                        if ($n2->nodeName == "sy") {
                            if (! $query0) {
                                $query0 = $n2->getAttribute("w");
                                if ($n2->getAttribute("k")) {
                                    $query0 .= ( ' (' . $n2->getAttribute("k") . ')');
                                }
                                $label0 = $n2->getAttribute("v");
                            }
                            if ($n2->getAttribute("lng") == $lng) {
                                $query = $n2->getAttribute("w");
                                if ($n2->getAttribute("k"))
                                    $query .= ( ' (' . $n2->getAttribute("k") . ')');
                                $label = $n2->getAttribute("v");
                                break;
                            }
                        }
                    }
                    if (!$query) {
                        $query = $query0;
                    }
                    if (!$label) {
                        $label = $label0;
                    }

                    $t_sort[$i] = $query; // tri sur w
                    $t_node[$i] = array('label' => $label, 'node'  => $n);

                    $i ++;
                }
            }

            if ($srt)
                natcasesort($t_sort);

            foreach ($t_sort as $i => $query) {
                $topic = $topics->appendChild($dom->createElement('topic'));
                // $topic->setAttribute('id', $n->getAttribute('id'));
                if ($opened_display != '' && in_array($t_node[$i]['node']->getAttribute('id'), $obr)) {
                    $topic->setAttribute('view', $opened_display);
                }
                $topic->appendChild($dom->createElement('label'))->appendChild($dom->createTextNode($t_node[$i]['label']));

                $query = '"' . $query . '"';
                if ($sth) {
                    $query = '*:' . $query;
                    if ($sand) {
                        $query = '(' . $query . ')';
                    }
                }

                if ($sand && $prevQuery != '') {
                    $query = $prevQuery . ' ' . _('phraseanet::technique:: et') . ' ' . $query . '';
                }

                $topic->appendChild($dom->createElement('query'))->appendChild($dom->createTextNode('' . $query . ''));

                $topics2 = $dom->createElement('topics');

                if ($this->doExportTopics($t_node[$i]['node'], $dom, $topics2, $query, $lng, $srt, $sth, $sand, $opened_display, $obr, $depth + 1) > 0) {
                    $topic->appendChild($topics2);
                }
            }
        }

        return $ntopics;
    }

    public function exportTopicsDialog(Application $app, Request $request)
    {
        return $app['twig']->render('thesaurus/export-topics-dialog.html.twig', array(
            'bid' => $request->get('bid'),
            'piv' => $request->get('piv'),
            'typ' => $request->get('typ'),
            'dlg' => $request->get('dlg'),
            'id'  => $request->get('id'),
            'obr'  => $request->get('obr'),
        ));
    }

    public function import(Application $app, Request $request)
    {
        set_time_limit(300);

        $imported = false;
        $err = '';

        if (null === $bid = $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }

        try {
            $databox = $app['phraseanet.appbox']->get_databox((int) $bid);
            $connbas = \connection::getPDOConnection($app, $bid);

            $dom = $databox->get_dom_thesaurus();

            if ($dom) {
                if ($request->get('id') == '') {
                    // on importe un theaurus entier
                    $node = $dom->documentElement;
                    while ($node->firstChild) {
                        $node->removeChild($node->firstChild);
                    }

                    $cbad = array();
                    $cok = array();
                    for ($i = 0; $i < 32; $i ++) {
                        $cbad[] = chr($i);
                        $cok[] = '_';
                    }

                    $file = $request->files->get('fil')->getPathname();

                    if (($fp = fopen($file, 'rb'))) {
                        $iline = 0;
                        $curdepth = -1;
                        $tid = array(-1    => -1, 0     => -1);
                        while ( ! $err && ! feof($fp) && ($line = fgets($fp)) !== FALSE) {
                            $iline ++;
                            if (trim($line) == '') {
                                continue;
                            }
                            for ($depth = 0; $line != '' && $line[0] == "\t"; $depth ++) {
                                $line = substr($line, 1);
                            }
                            if ($depth > $curdepth + 1) {
                                $err = sprintf(_("over-indent at line %s"), $iline);
                                continue;
                            }

                            $line = trim($line);

                            if ( ! $this->checkEncoding($line, 'UTF-8')) {
                                $err = sprintf(_("bad encoding at line %s"), $iline);
                                continue;
                            }

                            $line = str_replace($cbad, $cok, ($oldline = $line));
                            if ($line != $oldline) {
                                $err = sprintf(_("bad character at line %s"), $iline);
                                continue;
                            }

                            while ($curdepth >= $depth) {
                                $curdepth --;
                                $node = $node->parentNode;
                            }
                            $curdepth = $depth;

                            $nid = (int) ($node->getAttribute('nextid'));
                            $id = $node->getAttribute('id') . '.' . $nid;
                            $pid = $node->getAttribute('id');

                            $te_id = ($pid ? ($pid . '.') : 'T') . $nid;

                            $node->setAttribute('nextid', (string) ($nid + 1));

                            $te = $node->appendChild($dom->createElement('te'));
                            $te->setAttribute('id', $te_id);

                            $node = $te;

                            $tsy = explode(';', $line);
                            $nsy = 0;
                            foreach ($tsy as $syn) {
                                $lng = $request->get('piv');
                                $hit = '';
                                $kon = '';

                                if (($ob = strpos($syn, '[')) !== false) {
                                    if (($cb = strpos($syn, ']', $ob)) !== false) {
                                        $lng = trim(substr($syn, $ob + 1, $cb - $ob - 1));
                                        $syn = substr($syn, 0, $ob) . substr($syn, $cb + 1);
                                    } else {
                                        $lng = trim(substr($syn, $ob + 1));
                                        $syn = substr($syn, 0, $ob);
                                    }

                                    if (($ob = strpos($syn, '[')) !== false) {
                                        if (($cb = strpos($syn, ']', $ob)) !== false) {
                                            $hit = trim(substr($syn, $ob + 1, $cb - $ob - 1));
                                            $syn = substr($syn, 0, $ob) . substr($syn, $cb + 1);
                                        } else {
                                            $hit = trim(substr($syn, $ob + 1));
                                            $syn = substr($syn, 0, $ob);
                                        }
                                    }
                                }
                                if (($ob = strpos($syn, '(')) !== false) {
                                    if (($cb = strpos($syn, ')', $ob)) !== false) {
                                        $kon = trim(substr($syn, $ob + 1, $cb - $ob - 1));
                                        $syn = substr($syn, 0, $ob) . substr($syn, $cb + 1);
                                    } else {
                                        $kon = trim(substr($syn, $ob + 1));
                                        $syn = substr($syn, 0, $ob);
                                    }
                                }

                                $syn = trim($syn);

                                $sy = $node->appendChild($dom->createElement('sy'));
                                $sy->setAttribute('id', $te_id . '.' . $nsy);
                                $v = $syn;
                                if ($kon) {
                                    $v .= ' (' . $kon . ')';
                                }
                                $sy->setAttribute('v', $v);
                                $sy->setAttribute('w', $app['unicode']->remove_indexer_chars($syn));
                                if ($kon) {
                                    $sy->setAttribute('k', $app['unicode']->remove_indexer_chars($kon));
                                }
                                $sy->setAttribute('lng', $lng);

                                $nsy ++;
                            }

                            $te->setAttribute('nextid', (string) $nsy);
                        }

                        fclose($fp);
                    }

                } else {
                    // on importe dans une branche
                    $err = 'not implemented';
                }

                if (! $err) {
                    $imported = true;
                    $databox->saveThesaurus($dom);
                }
            }

            if (! $err) {
                $meta_struct = $databox->get_meta_structure();

                foreach ($meta_struct->get_elements() as $meta_field) {
                    $meta_field->set_tbranch('')->save();
                }

                $dom = $databox->get_dom_cterms();
                if ($dom) {
                    $node = $dom->documentElement;
                    while ($node->firstChild) {
                        $node->removeChild($node->firstChild);
                    }
                    $databox->saveCterms($dom);
                }

                $sql = 'UPDATE RECORD SET status=status & ~3';
                $stmt = $connbas->prepare($sql);
                $stmt->execute();
                $stmt->closeCursor();
            }
        } catch (\Exception $e) {

        }

        return $app['twig']->render('thesaurus/import.html.twig', array('err' => $err));
    }

    private function checkEncoding($string, $string_encoding)
    {
        $fs = $string_encoding == 'UTF-8' ? 'UTF-32' : $string_encoding;
        $ts = $string_encoding == 'UTF-32' ? 'UTF-8' : $string_encoding;

        return $string === mb_convert_encoding(mb_convert_encoding($string, $fs, $ts), $ts, $fs);
    }

    public function importDialog(Application $app, Request $request)
    {
        return $app['twig']->render('thesaurus/import-dialog.html.twig', array(
            'dlg' => $request->get('dlg'),
            'bid' => $request->get('bid'),
            'id'  => $request->get('id'),
            'piv' => $request->get('piv'),
        ));
    }

    public function indexThesaurus(Application $app, Request $request)
    {
        $sql = "SELECT
                    sbas.sbas_id,
                    sbasusr.bas_manage AS bas_manage,
                    sbasusr.bas_modify_struct AS bas_modify_struct,
                    sbasusr.bas_modif_th AS bas_edit_thesaurus
                FROM
                    (usr INNER JOIN sbasusr
                        ON usr.usr_id = :usr_id
                        AND usr.usr_id = sbasusr.usr_id
                        AND model_of = 0)
                INNER JOIN
                    sbas ON sbas.sbas_id = sbasusr.sbas_id
                HAVING bas_edit_thesaurus > 0
                ORDER BY sbas.ord";

        $bases = $languages = array();

        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':usr_id' => $app['authentication']->getUser()->get_id()));
        $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row) {
            try {
                $connbas = \connection::getPDOConnection($app, $row['sbas_id']);
            } catch (\Exception $e) {
                continue;
            }
            $bases[$row['sbas_id']] = \phrasea::sbas_labels($row['sbas_id'], $app);
        }

        foreach (PhraseaApplication::getAvailableLanguages() as $lng_code => $lng) {
            $lng_code = explode('_', $lng_code);
            $languages[$lng_code[0]] = $lng;
        }

        return $app['twig']->render('thesaurus/index.html.twig', array(
            'languages' => $languages,
            'bases'     => $bases,
        ));
    }

    public function linkFieldStep1(Application $app, Request $request)
    {
        if (null === $bid = $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }

        try {
            $databox = $app['phraseanet.appbox']->get_databox((int) $bid);
            $domstruct = $databox->get_dom_structure();
            $domth = $databox->get_dom_thesaurus();

            if ($domstruct && $domth) {
                $xpathth = new \DOMXPath($domth);
                $xpathstruct = new \DOMXPath($domstruct);

                    if ($request->get('tid') !== "") {
                        $q = "//te[@id='" . $request->get('tid') . "']";
                    } else {
                        $q = "//te[not(@id)]";
                    }

                    $nodes = $xpathth->query($q);
                    $fullBranch = "";
                    if ($nodes->length == 1) {
                        for ($n = $nodes->item(0); $n && $n->nodeType == XML_ELEMENT_NODE && $n->getAttribute("id") !== ""; $n = $n->parentNode) {
                            $sy = $xpathth->query("sy", $n)->item(0);
                            $sy = $sy ? $sy->getAttribute("v") : "";
                            if (! $sy) {
                                $sy = $sy = "...";
                            }
                            $fullBranch = " / " . $sy . $fullBranch;
                        }
                    }
                    $fieldnames = array();
                    $nodes = $xpathstruct->query("/record/description/*");
                    for ($i = 0; $i < $nodes->length; $i ++) {
                        $fieldname = $nodes->item($i)->nodeName;
                        $tbranch = $nodes->item($i)->getAttribute("tbranch");
                        $ck = false;
                        if ($tbranch) {
                            // ce champ a deje un tbranch, est-ce qu'il pointe sur la branche selectionnee ?
                            $thnodes = $xpathth->query($tbranch);
                            for ($j = 0; $j < $thnodes->length; $j ++) {
                                if ($thnodes->item($j)->getAttribute("id") == $request->get('tid')) {
                                    $ck = true;
                                }
                            }
                        }
                        $fieldnames[$fieldname] = $ck;
                    }
            }
        } catch (\Exception $e) {

        }

        return $app['twig']->render('thesaurus/link-field-step1.html.twig', array(
            'piv'        => $request->get('piv'),
            'bid'        => $request->get('bid'),
            'tid'        => $request->get('tid'),
            'fullBranch' => $fullBranch,
            'fieldnames' => $fieldnames
        ));
    }

    public function linkFieldStep2(Application $app, Request $request)
    {
        if (null === $bid = $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }

        $oldlinks = array();
        $needreindex = false;

        try {
            $databox = $app['phraseanet.appbox']->get_databox((int) $bid);
            $domstruct = $databox->get_dom_structure();
            $domth = $databox->get_dom_thesaurus();

            if ($domstruct && $domth) {
                $xpathth = new \DOMXPath($domth);
                $xpathstruct = new \DOMXPath($domstruct);
                $nodes = $xpathstruct->query("/record/description/*");

                for ($i = 0; $i < $nodes->length; $i ++) {
                    $fieldname = $nodes->item($i)->nodeName;
                    $oldbranch = $nodes->item($i)->getAttribute("tbranch");
                    $ck = false;
                    $tids = array(); // les ids de branches liees e ce champ
                    if ($oldbranch) {
                        // ce champ a deje un tbranch, on balaye les branches auxquelles il est lie
                        $thnodes = $xpathth->query($oldbranch);
                        for ($j = 0; $j < $thnodes->length; $j ++) {
                            if ($thnodes->item($j)->getAttribute("id") == $request->get('tid')) {
                                // il etait deje lie e la branche selectionnee
                                $tids[$thnodes->item($j)->getAttribute("id")] = $thnodes->item($j);
                                $ck = true;
                            } else {
                                // il etait lie e une autre branche
                                $tids[$thnodes->item($j)->getAttribute("id")] = $thnodes->item($j);
                            }
                        }
                    }

                    if (in_array($fieldname, $request->get('field', array())) != $ck) {
                        if ($ck) {
                            // print("il etait lie a la branche, il ne l'est plus<br/>\n");
                            unset($tids[$request->get('tid')]);
                        } else {
                            // print("il n'etait pas lie a la branche, il l'est maintenant<br/>\n");
                            $tids[$request->get('tid')] = $xpathth->query("/thesaurus//te[@id='" . \thesaurus::xquery_escape($request->get('tid')) . "']")->item(0);
                        }
                        $newtbranch = "";
                        foreach ($tids as $kitd => $node) {
                            if ($kitd === "") {
                                $newtbranch .= ( $newtbranch ? " | " : "") . "/thesaurus";
                            } else {
                                $neb = "";
                                while ($node && $node->nodeName == "te") {
                                    $neb = "/te[@id='" . $node->getAttribute("id") . "']" . $neb;
                                    $node = $node->parentNode;
                                }
                                $newtbranch .= ( $newtbranch ? " | " : "") . "/thesaurus" . $neb;
                            }
                        }

                        $oldlinks[$fieldname] = array(
                            'old_branch' => $oldbranch,
                            'new_branch' => $newtbranch
                        );

                        if ($newtbranch != "") {
                            $needreindex = true;
                        }
                    }
                }
            }
        } catch (\Exception $e) {

        }

        return $app['twig']->render('thesaurus/link-field-step2.html.twig', array(
            'piv'          => $request->get('piv'),
            'bid'          => $request->get('bid'),
            'tid'          => $request->get('tid'),
            'oldlinks'     => $oldlinks,
            'need_reindex' => $needreindex,
        ));
    }

    public function linkFieldStep3(Application $app, Request $request)
    {
        if (null === $bid = $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }

        try {
            $databox = $app['phraseanet.appbox']->get_databox((int) $bid);
            $connbas = \connection::getPDOConnection($app, $bid);
            $meta_struct = $databox->get_meta_structure();
            $domct = $databox->get_dom_cterms();
            $domst = $databox->get_dom_structure();

            if ($domct && $domst) {
                $xpathct = new \DOMXPath($domct);
                $xpathst = new \DOMXPath($domst);
                $ctchanged = false;

                $candidates2del = array();
                foreach ($request->get("f2unlk", array()) as $f2unlk) {
                    $q = "/cterms/te[@field='" . \thesaurus::xquery_escape($f2unlk) . "']";
                    $nodes = $xpathct->query($q);
                    for ($i = 0; $i < $nodes->length; $i ++) {
                        $candidates2del[] = array(
                            "field" => $f2unlk,
                            "node"  => $nodes->item($i)
                        );
                    }

                    $field = $meta_struct->get_element_by_name($f2unlk);
                    if ($field) {
                        $field->set_tbranch('')->save();
                    }
                }
                foreach ($candidates2del as $candidate2del) {
                    $candidate2del["node"]->parentNode->removeChild($candidate2del["node"]);
                    $ctchanged = true;
                }

                foreach ($request->get("fbranch", array()) as $fbranch) {
                    $p = strpos($fbranch, "<");
                    if ($p > 1) {
                        $fieldname = substr($fbranch, 0, $p);
                        $tbranch = substr($fbranch, $p + 1);
                        $field = $meta_struct->get_element_by_name($fieldname);
                        if ($field) {
                            $field->set_tbranch($tbranch)->save();
                        }
                    }
                }

                if ($ctchanged) {
                    $databox->saveCterms($domct);
                }
            }

            $sql = "DELETE FROM thit WHERE name = :name";
            $stmt = $connbas->prepare($sql);
            foreach ($request->get("f2unlk", array()) as $f2unlk) {
                $stmt->execute(array(':name' => $f2unlk));
            }
            $stmt->closeCursor();

            if ($request->get("reindex")) {
                $sql = "UPDATE record SET status=status & ~2";
                $stmt = $connbas->prepare($sql);
                $stmt->execute();
                $stmt->closeCursor();
            }
        } catch (\Exception $e) {

        }

        return $app['twig']->render('thesaurus/link-field-step3.html.twig', array(
            'field2del'      => $request->get('f2unlk', array()),
            'candidates2del' => $candidates2del,
            'branch2del'     => $request->get('fbranch', array()),
            'ctchanged'      => $ctchanged,
            'reindexed'      => $request->get('reindex'),
        ));
    }

    private function fixThesaurus($app, &$domct, &$domth, &$connbas)
    {
        $oldversion = $version = $domth->documentElement->getAttribute("version");

        if ('' === trim($version)) {
            $version = '1.0.0';
        }

        while (class_exists($cls = "patchthesaurus_" . str_replace(".", "", $version))) {

            $last_version = $version;
            $zcls = new $cls;
            $version = $zcls->patch($version, $domct, $domth, $connbas);

            if ($version == $last_version) {
                break;
            }
        }

        return $version;
    }

    public function loadThesaurus(Application $app, Request $request)
    {
        if (null === $bid = $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }

        $updated = false;
        $validThesaurus = true;
        $ctlist = array();
        $name = \phrasea::sbas_labels($request->get('bid'), $app);

        try {
            $databox = $app['phraseanet.appbox']->get_databox((int) $request->get('bid'));
            $connbas = \connection::getPDOConnection($app, $request->get('bid'));

            $domct = $databox->get_dom_cterms();
            $domth = $databox->get_dom_thesaurus();
            $now = date("YmdHis");

            if ( ! $domct && $request->get('repair') == 'on') {
                $domct = new \DOMDocument();
                $domct->load(__DIR__ . "/../../../../conf.d/blank_cterms.xml");
                $domct->documentElement->setAttribute("creation_date", $now);
                $databox->saveCterms($domct);
            }
            if ( ! $domth && $request->get('repair') == 'on') {
                $domth = new \DOMDocument();
                $domth->load(__DIR__ . "/../../../../conf.d/blank_thesaurus.xml");
                $domth->documentElement->setAttribute("creation_date", $now);
                $databox->saveThesaurus($domth);
            }

            if ($domct && $domth) {

                $oldversion = $domth->documentElement->getAttribute("version");
                if (($version = $this->fixThesaurus($app, $domct, $domth, $connbas)) != $oldversion) {
                    $updated = true;
                    $databox->saveCterms($domct);
                    $databox->saveThesaurus($domth);
                }

                for ($ct = $domct->documentElement->firstChild; $ct; $ct = $ct->nextSibling) {
                    if ($ct->nodeName == "te") {
                        $ctlist[] = array(
                            'id' => $ct->getAttribute("id"),
                            'field' => $ct->getAttribute("field")
                        );
                    }
                }
            } else {
                $validThesaurus = false;
            }
        } catch (\Exception $e) {

        }

        return $app['twig']->render('thesaurus/load-thesaurus.html.twig', array(
            'bid' => $request->get('bid'),
            'name' => $name,
            'cterms' => $ctlist,
            'valid_thesaurus' => $validThesaurus,
            'updated' => $updated
        ));
    }

    public function newSynonymDialog(Application $app, Request $request)
    {
        $languages = array();

        foreach (PhraseaApplication::getAvailableLanguages() as $lng_code => $lng) {
            $lng_code = explode('_', $lng_code);
            $languages[$lng_code[0]] = $lng;
        }

        return $app['twig']->render('thesaurus/new-synonym-dialog.html.twig', array(
            'piv'       => $request->get('piv'),
            'typ'       => $request->get('typ'),
            'languages' => $languages,
        ));
    }


    public function newTerm(Application $app, Request $request)
    {
        list($term, $context) = $this->splitTermAndContext($request->get("t"));

        $dom = $this->doSearchCandidate($app, $request->get('bid'), $request->get('pid'), $request->get('term'), $request->get('context'), $request->get('piv'));

        $xpath = new \DOMXPath($dom);

        $candidates = $xpath->query("/result/candidates_list/ct");

        $nb_candidates_ok = $nb_candidates_bad = 0;
        $flist_ok = $flist_bad = "";
        for ($i = 0; $i < $candidates->length; $i ++) {
            if ($candidates->item($i)->getAttribute("sourceok") == "1") { // && $candidates->item($i)->getAttribute("cid"))
                $flist_ok .= ( $flist_ok ? ", " : "") . $candidates->item($i)->getAttribute("field");
                $nb_candidates_ok ++;
            } else {
                $flist_bad .= ( $flist_bad ? ", " : "") . $candidates->item($i)->getAttribute("field");
                $nb_candidates_bad ++;
            }
        }
        $candidates_list = array();
        for ($i = 0; $i < $candidates->length; $i ++) {
            if ($candidates->item($i)->getAttribute("sourceok") == "1") {
                $candidates_list = array(
                    'id'    => $candidates->item($i)->getAttribute("id"),
                    'field' => $candidates->item($i)->getAttribute("field"),
                );
            }
        }

        return $app['twig']->render('thesaurus/new-term.html.twig', array(
            'typ' => $request->get('typ'),
            'bid' => $request->get('bid'),
            'piv' => $request->get('piv'),
            'pid' => $request->get('pid'),
            'sylng' => $request->get('sylng'),
            'dlg' => $request->get('dlg'),
            'candidates' => $candidates_list,
            'term' => $term,
            'context' => $context,
            'nb_candidates_ok' => $nb_candidates_ok,
            'nb_candidates_bad' => $nb_candidates_bad,
        ));
    }

    public function properties(Application $app, Request $request)
    {
        $dom = $this->getXMLTerm($app, $request->get('bid'), $request->get('id'), $request->get('typ'), $request->get('piv'), '0', null, '1', null);
        $fullpath = $dom->getElementsByTagName("fullpath_html")->item(0)->firstChild->nodeValue;
        $hits = $dom->getElementsByTagName("allhits")->item(0)->firstChild->nodeValue;

        $languages = $synonyms = array();

        $sy_list = $dom->getElementsByTagName("sy_list")->item(0);
        for ($n = $sy_list->firstChild; $n; $n = $n->nextSibling) {
            $synonyms[] = array(
                'id' => $n->getAttribute("id"),
                'lng' => $n->getAttribute("lng"),
                't' => $n->getAttribute("t"),
                'hits' => $n->getAttribute("hits"),
            );
        }

        foreach (PhraseaApplication::getAvailableLanguages() as $code => $language) {
            $lng_code = explode('_', $code);
            $languages[$lng_code[0]] = $language;
        }

        return $app['twig']->render('thesaurus/properties.html.twig', array(
            'typ' => $request->get('typ'),
            'bid' => $request->get('bid'),
            'piv' => $request->get('piv'),
            'id' => $request->get('id'),
            'dlg' => $request->get('dlg'),
            'languages' => $languages,
            'fullpath' => $fullpath,
            'hits' => $hits,
            'synonyms' => $synonyms,
        ));
    }

    public function search(Application $app, Request $request)
    {
        return $app['twig']->render('thesaurus/search.html.twig');
    }

    public function thesaurus(Application $app, Request $request)
    {
        $flags = $jsFlags = array();

        foreach (PhraseaApplication::getAvailableLanguages() as $code => $language) {
            $lng_code = explode('_', $code);
            $flags[$lng_code[0]] = $language;
            $jsFlags[$lng_code[0]] = array('w' => 18, 'h' => 13);
        }
        $jsFlags = json_encode($jsFlags);

        return $app['twig']->render('thesaurus/thesaurus.html.twig', array(
            'piv'     => $request->get('piv'),
            'bid'     => $request->get('bid'),
            'flags'   => $flags,
            'jsFlags' => $jsFlags,
        ));
    }


    public function acceptXml(Application $app, Request $request)
    {
        $ret = new \DOMDocument("1.0", "UTF-8");
        $ret->standalone = true;
        $ret->preserveWhiteSpace = false;

        $root = $ret->appendChild($ret->createElement("result"));
        $root->appendChild($ret->createCDATASection(var_export(array(
            "bid"   => $request->get('bid'),
            "id"    => $request->get('id'),
            "piv"   => $request->get('piv'),
            "debug" => $request->get('debug'),
        ), true)));

        $refresh_list = $root->appendChild($ret->createElement("refresh_list"));

        if (null === $bid = $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }

        try {
            $databox = $app['phraseanet.appbox']->get_databox((int) $bid);
            $connbas = \connection::getPDOConnection($app, $bid);

            $dom = $databox->get_dom_cterms();
            $xpath = new \DOMXPath($dom);
            $q = "/cterms//te[@id='" . $request->get('id') . "']";

            if ($request->get('debug')) {
                print("q:" . $q . "<br/>\n");
            }

            $te = $xpath->query($q)->item(0);
            if ($te) {
                if ($request->get('debug')) {
                    printf("found te : id=%s<br/>\n", $te->getAttribute("id"));
                }

                $this->acceptBranch($app, $bid, $te);

                $databox->saveCterms($dom);

                $r = $refresh_list->appendChild($ret->createElement("refresh"));
                $r->setAttribute("id", $te->parentNode->getAttribute("id"));
                $r->setAttribute("type", "CT");
            }
        } catch (\Exception $e) {

        }

        return new Response($ret->saveXML(), 200, array('Content-Type' => 'text/xml'));
    }

    private function acceptBranch(Application $app, $sbas_id, &$node)
    {
        if (strlen($oldid = $node->getAttribute("id")) > 1) {
            $node->setAttribute("id", $newid = ("C" . substr($oldid, 1)));

            $thit_oldid = str_replace(".", "d", $oldid) . "d";
            $thit_newid = str_replace(".", "d", $newid) . "d";

            $sql = "UPDATE thit SET value = thit_new WHERE value = :thit_old";

            try {
                $connbas = \connection::getPDOConnection($app, $sbas_id);
                $stmt = $connbas->prepare($sql);
                $stmt->execute(array(
                    ':thit_new' => $thit_newid,
                    'thit_old'  => $thit_oldid
                ));
                $stmt->closeCursor();
            } catch (\Exception $e) {

            }
        }
        for ($n = $node->firstChild; $n; $n = $n->nextSibling) {
            if ($n->nodeType == XML_ELEMENT_NODE) {
                $this->acceptBranch($app, $sbas_id, $n);
            }
        }
    }

    public function acceptCandidatesXml(Application $app, Request $request)
    {
        $ret = new \DOMDocument("1.0", "UTF-8");
        $ret->standalone = true;
        $ret->preserveWhiteSpace = false;

        $root = $ret->appendChild($ret->createElement("result"));
        $root->appendChild($ret->createCDATASection(var_export(array(
            'bid'   => $request->get('bid'),
            'piv'   => $request->get('piv'),
            'cid'   => $request->get('cid'),
            'pid'   => $request->get('pid'),
            'typ'   => $request->get('typ'),
            'debug' => $request->get('debug'),
        ), true)));

        $refresh_list = $root->appendChild($ret->createElement("refresh_list"));

        if (null === $bid = $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }

        try {
            $databox = $app['phraseanet.appbox']->get_databox((int) $bid);
            $connbas = \connection::getPDOConnection($app, $bid);

            $domct = $databox->get_dom_cterms();
            $domth = $databox->get_dom_thesaurus();

            if ($domct !== false && $domth !== false) {
                $xpathth = new \DOMXPath($domth);

                if ($request->get('pid') == "T") {
                    $q = "/thesaurus";
                } else {
                    $q = "/thesaurus//te[@id='" . $request->get('pid') . "']";
                }

                if ($request->get('debug')) {
                    printf("qth: %s<br/>\n", $q);
                }

                $parentnode = $xpathth->query($q)->item(0);

                if ($parentnode) {
                    $xpathct = new \DOMXPath($domct);
                    $ctchanged = $thchanged = false;

                    $icid = 0;
                    foreach ($request->get("cid") as $cid) {
                        $q = "//te[@id='" . $cid . "']";

                        if ($request->get('debug')) {
                            printf("qct: %s<br/>\n", $q);
                        }

                        $ct = $xpathct->query($q)->item(0);

                        if ($ct) {
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

                                $this->renumerate($te, $pid, $chgids);
                                $te = $parentnode->appendChild($te);

                                if ($request->get('debug')) {
                                    printf("newid=%s<br/>\n", $te->getAttribute("id"));
                                }

                                $soldid = str_replace(".", "d", $oldid) . "d";
                                $snewid = str_replace(".", "d", $pid) . "d";
                                $l = strlen($soldid) + 1;

                                $sql = "UPDATE thit
                                        SET value = CONCAT('$snewid', SUBSTRING(value FROM $l))
                                        WHERE value LIKE :like";

                                if ($request->get('debug')) {
                                    printf("soldid=%s ; snewid=%s<br/>\nsql=%s<br/>\n", $soldid, $snewid, $sql);
                                } else {
                                    $stmt = $connbas->prepare($sql);
                                    $stmt->execute(array(':like' => $soldid . '%'));
                                    $stmt->closeCursor();
                                }

                                if ($icid == 0) { // on update la destination une seule fois
                                    $r = $refresh_list->appendChild($ret->createElement("refresh"));
                                    $r->setAttribute("id", $parentnode->getAttribute("id"));
                                    $r->setAttribute("type", "TH");
                                }
                                $thchanged = true;

                                $r = $refresh_list->appendChild($ret->createElement("refresh"));
                                $r->setAttribute("id", $ct->parentNode->getAttribute("id"));
                                $r->setAttribute("type", "CT");

                                $ct->parentNode->removeChild($ct);

                                $ctchanged = true;
                            } elseif ($request->get("typ") == "SY") {
                                // importer tt le contenu de la branche sous la destination
                                for ($ct2 = $ct->firstChild; $ct2; $ct2 = $ct2->nextSibling) {
                                    if ($ct2->nodeType != XML_ELEMENT_NODE) {
                                        continue;
                                    }

                                    $nid = $parentnode->getAttribute("nextid");
                                    $parentnode->setAttribute("nextid", (int) $nid + 1);

                                    $oldid = $ct2->getAttribute("id");
                                    $te = $domth->importNode($ct2, true);
                                    $chgids = array();
                                    if (($pid = $parentnode->getAttribute("id")) == "") {
                                        $pid = "T" . $nid;
                                    } else {
                                        $pid .= "." . $nid;
                                    }

                                    $this->renumerate($te, $pid, $chgids);
                                    $te = $parentnode->appendChild($te);

                                    if ($request->get('debug')) {
                                        printf("newid=%s<br/>\n", $te->getAttribute("id"));
                                    }

                                    $soldid = str_replace(".", "d", $oldid) . "d";
                                    $snewid = str_replace(".", "d", $pid) . "d";
                                    $l = strlen($soldid) + 1;

                                    $sql = "UPDATE thit
                                            SET value = CONCAT('$snewid', SUBSTRING(value FROM $l))
                                            WHERE value LIKE :like";

                                    if ($request->get('debug')) {
                                        printf("soldid=%s ; snewid=%s<br/>\nsql=%s<br/>\n", $soldid, $snewid, $sql);
                                    } else {
                                        $stmt = $connbas->prepare($sql);
                                        $stmt->execute(array(':like' => $soldid . '%'));
                                        $stmt->closeCursor();
                                    }

                                    $thchanged = true;
                                }
                                if ($icid == 0) { // on update la destination une seule fois
                                    $r = $refresh_list->appendChild($ret->createElement("refresh"));
                                    $r->setAttribute("id", $parentnode->parentNode->getAttribute("id"));
                                    $r->setAttribute("type", "TH");
                                }
                                $r = $refresh_list->appendChild($ret->createElement("refresh"));
                                $r->setAttribute("id", $ct->parentNode->getAttribute("id"));
                                $r->setAttribute("type", "CT");

                                $ct->parentNode->removeChild($ct);
                                $ctchanged = true;
                            }
                            $icid ++;
                        }
                    }
                    if ($ctchanged) {
                        $databox->saveCterms($domct);
                    }
                    if ($thchanged) {
                        $databox->saveThesaurus($domth);
                    }
                }
            }
        } catch (\Exception $e) {

        }

        return new Response($ret->saveXML(), 200, array('Content-Type' => 'text/xml'));
    }

    private function renumerate($node, $id, &$chgids, $depth = 0)
    {
        $node->setAttribute("id", $id);
        $nchild = 0;
        for ($n = $node->firstChild; $n; $n = $n->nextSibling) {
            if ($n->nodeType == XML_ELEMENT_NODE && ($n->nodeName == "te" || $n->nodeName == "sy")) {
                $this->renumerate($n, $id . "." . $nchild, $chgids, $depth + 1);
                $nchild ++;
            }
        }
        $node->setAttribute("nextid", $nchild);
    }

    public function changeSynonymLanguageXml(Application $app, Request $request)
    {
        $ret = new \DOMDocument("1.0", "UTF-8");
        $ret->standalone = true;
        $ret->preserveWhiteSpace = false;

        $root = $ret->appendChild($ret->createElement("result"));
        $root->appendChild($ret->createCDATASection(var_export(array(
            'bid'    => $request->get('bid'),
            'piv'    => $request->get('piv'),
            'newlng' => $request->get('cid'),
            'id'     => $request->get('id'),
            'typ'    => $request->get('typ'),
            'debug'  => $request->get('debug'),
        ), true)));

        if (null === $bid = $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }

        try {
            $databox = $app['phraseanet.appbox']->get_databox((int) $bid);

            if ($request->get('typ') == "CT") {
                $xqroot = "cterms";
                $dom = $databox->get_dom_cterms();
            } else {
                $xqroot = "thesaurus";
                $dom = $databox->get_dom_thesaurus();
            }

            if ($dom) {
                $xpath = new \DOMXPath($dom);
                $q = "/$xqroot//sy[@id='" . $request->get('id') . "']";

                if ($request->get('debug')) {
                    print("q:" . $q . "<br/>\n");
                }

                $sy0 = $xpath->query($q)->item(0);
                if ($sy0) {
                    $sy0->setAttribute("lng", $request->get('newlng'));

                    if ($xqroot == "cterms") {
                        $databox->saveCterms($dom);
                    } elseif ($xqroot == "thesaurus") {
                        $databox->saveThesaurus($dom);
                    }

                    $ret = $this->getXMLTerm($app, $bid, $sy0->parentNode->getAttribute("id"), $request->get('typ'), $request->get('piv'), null, $request->get('id'), '1', null);

                    $root = $ret->getElementsByTagName("result")->item(0);
                    $refresh_list = $root->appendChild($ret->createElement("refresh_list"));
                    $r = $refresh_list->appendChild($ret->createElement("refresh"));

                    $r->setAttribute("id", $sy0->parentNode->parentNode->getAttribute("id"));
                    $r->setAttribute("type", $request->get('typ'));
                }
            }
        } catch (\Exception $e) {

        }

        return new Response($ret->saveXML(), 200, array('Content-Type' => 'text/xml'));
    }

    public function changeSynonymPositionXml(Application $app, Request $request)
    {
        $ret = new \DOMDocument("1.0", "UTF-8");
        $ret->standalone = true;
        $ret->preserveWhiteSpace = false;

        $root = $ret->appendChild($ret->createElement("result"));
        $root->appendChild($ret->createCDATASection(var_export(array(
            'bid'   => $request->get('bid'),
            'piv'   => $request->get('piv'),
            'dir'   => $request->get('dir'),
            'id'    => $request->get('id'),
            'typ'   => $request->get('typ'),
            'debug' => $request->get('debug'),
        ), true)));

        if (null === $bid = $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }

        $refresh_list = $root->appendChild($ret->createElement("refresh_list"));

        try {
            $databox = $app['phraseanet.appbox']->get_databox((int) $bid);

            if ($request->get('typ') == "CT") {
                $xqroot = "cterms";
                $dom = $databox->get_dom_cterms();
            } else {
                $xqroot = "thesaurus";
                $dom = $databox->get_dom_thesaurus();
            }

            if ($dom) {
                $xpath = new \DOMXPath($dom);
                $q = "/$xqroot//sy[@id='" . $request->get('id') . "']";
                if ($request->get('debug'))
                    print("q:" . $q . "<br/>\n");

                $sy0 = $xpath->query($q)->item(0);
                if ($sy0) {
                    if ($request->get('dir') == 1 && $sy0 && $sy0->previousSibling) {
                        $sy0->parentNode->insertBefore($sy0, $sy0->previousSibling);
                    } elseif ($request->get('dir') == -1 && $sy0 && $sy0->nextSibling) {
                        $sy0->parentNode->insertBefore($sy0->nextSibling, $sy0);
                    }

                    if ($xqroot == "cterms") {
                        $databox->saveCterms($dom);
                    } elseif ($xqroot == "thesaurus") {
                        $databox->saveThesaurus($dom);
                    }

                    $ret = $this->getXMLTerm($app, $bid, $sy0->parentNode->getAttribute("id"), $request->get('typ'), $request->get('piv'), null, $request->get('id'), '1', null);

                    $root = $ret->getElementsByTagName("result")->item(0);
                    $refresh_list = $root->appendChild($ret->createElement("refresh_list"));

                    $r = $refresh_list->appendChild($ret->createElement("refresh"));
                    $r->setAttribute("id", $sy0->parentNode->parentNode->getAttribute("id"));
                    $r->setAttribute("type", $request->get('typ'));
                }
            }
        } catch (\Exception $e) {

        }

        return new Response($ret->saveXML(), 200, array('Content-Type' => 'text/xml'));
    }

    public function removeNoHitXml(Application $app, Request $request)
    {
        $ret = new \DOMDocument("1.0", "UTF-8");
        $ret->standalone = true;
        $ret->preserveWhiteSpace = false;

        $root = $ret->appendChild($ret->createElement("result"));
        $root->appendChild($ret->createCDATASection(var_export(array(
            'bid'   => $request->get('bid'),
            'piv'   => $request->get('piv'),
            'id'    => $request->get('id'),
            'pid'   => $request->get('pid'),
            'typ'   => $request->get('typ'),
            'debug' => $request->get('debug'),
        ), true)));

        if (null === $bid = $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }

        try {
            $databox = $app['phraseanet.appbox']->get_databox((int) $bid);
            $connbas = \connection::getPDOConnection($app, $bid);

            $s_thits = '';
            $sql = "SELECT DISTINCT value FROM thit";

            $stmt = $connbas->prepare($sql);
            $stmt->execute();
            $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            foreach ($rs as $rowbas) {
                $s_thits .= str_replace('d', '.', $rowbas['value']) . ';';
            }

            if ($request->get('typ') == 'CT') {
                $dom = $databox->get_dom_cterms();
            } else {
                $dom = $databox->get_dom_thesaurus();
            }

            if ($dom) {
                $xpath = new \DOMXPath($dom);

                if ($request->get('id') == "T") {
                    $q = "/thesaurus";
                } elseif ($request->get('id') == "C") {
                    $q = "/cterms";
                } else {
                    $q = "//te[@id='" . $request->get('id') . "']";
                }

                if (($znode = $xpath->query($q)->item(0))) {
                    $nodestodel = array();
                    $root->setAttribute('n_nohits', (string) $this->doDeleteNohits($znode, $s_thits, $nodestodel));
                    foreach ($nodestodel as $n) {
                        $n->parentNode->removeChild($n);
                    }

                    if ($request->get('debug')) {
                        printf("<pre>%s</pre>", $dom->saveXML());
                    }

                    if ($request->get('typ') == 'CT') {
                        $databox->saveCterms($dom);
                    }
                }
            }
        } catch (\Exception $e) {

        }

        return new Response($ret->saveXML(), 200, array('Content-Type' => 'text/xml'));
    }

    private function doDeleteNohits($node, &$s_thits, &$nodestodel)
    {
        $ret = 0;
        if ($node->nodeType == XML_ELEMENT_NODE) { // && $node->nodeName=='te')
            $id = $node->getAttribute('id') . '.';

            if ((strpos($s_thits, $id)) === false && ! $node->getAttribute('field')) {
                // this id has no hits, neither any of his children
                $nodestodel[] = $node;
                $ret = 1;
            } else {
                // this id (or a child) has hit, must check children
                for ($n = $node->firstChild; $n; $n = $n->nextSibling) {
                    if ($n->nodeType == XML_ELEMENT_NODE) {
                        $ret += $this->doDeleteNohits($n, $s_thits, $nodestodel);
                    }
                }
            }
        }

        return $ret;
    }

    public function removeSynonymXml(Application $app, Request $request)
    {
        $ret = new \DOMDocument("1.0", "UTF-8");
        $ret->standalone = true;
        $ret->preserveWhiteSpace = false;

        $root = $ret->appendChild($ret->createElement("result"));
        $root->appendChild($ret->createCDATASection(var_export(array(
            'bid'   => $request->get('bid'),
            'id'    => $request->get('id'),
            'piv'   => $request->get('piv'),
            'typ'   => $request->get('typ'),
            'debug' => $request->get('debug'),
        ), true)));

        $refresh_list = $root->appendChild($ret->createElement("refresh_list"));

        if (null === $bid = $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }

        try {
            $connbas = \connection::getPDOConnection($app, $bid);
            $databox = $app['phraseanet.appbox']->get_databox((int) $bid);
            $domct = $databox->get_dom_cterms();
            $dom = $databox->get_dom_thesaurus();

            if ($request->get('typ') == "CT") {
                $xqroot = "cterms";
            } else {
                $xqroot = "thesaurus";
            }

            if ($dom && $domct) {
                $xpath = new \DOMXPath($dom);
                $q = "/$xqroot//sy[@id='" . $request->get('id') . "']";

                if ($request->get('debug')) {
                    print("q:" . $q . "<br/>\n");
                }

                $sy0 = $xpath->query($q)->item(0);
                if ($sy0) {
                    $xpathct = new \DOMXPath($domct);

                    // on cherche la branche 'deleted' dans les cterms
                    $nodes = $xpathct->query("/cterms/te[@delbranch='1']");
                    if ( ! $nodes || ($nodes->length == 0)) {
                        // 'deleted' n'existe pas, on la cree
                        $id = $domct->documentElement->getAttribute("nextid");
                        if ($request->get('debug')) {
                            printf("creating 'deleted' branch : id=%s<br/>\n", $id);
                        }
                        $domct->documentElement->setAttribute("nextid", (int) ($id) + 1);
                        $del = $domct->documentElement->appendChild($domct->createElement("te"));
                        $del->setAttribute("id", "C" . $id);
                        $del->setAttribute("field", _('thesaurus:: corbeille'));
                        $del->setAttribute("nextid", "0");
                        $del->setAttribute("delbranch", "1");

                        $r = $refresh_list->appendChild($ret->createElement("refresh"));
                        $r->setAttribute("id", "C");
                        $r->setAttribute("type", "CT");
                    } else {
                        // 'deleted' existe
                        $del = $nodes->item(0);
                        $r = $refresh_list->appendChild($ret->createElement("refresh"));
                        $r->setAttribute("id", $del->getAttribute("id"));
                        $r->setAttribute("type", "CT");
                    }

                    // on cree une branche 'te'
                    $oldid = $sy0->getAttribute("id");
                    $refrid = $sy0->parentNode->parentNode->getAttribute("id");
                    $delid = $del->getAttribute("id");
                    $delteid = (int) ($del->getAttribute("nextid"));

                    if ($request->get('debug')) {
                        printf("delid=$delid ; delteid=$delteid <br/>\n");
                    }

                    $del->setAttribute("nextid", $delteid + 1);
                    $delte = $del->appendChild($domct->createElement("te"));
                    $delte->setAttribute("id", $delid . "." . $delteid);
                    $delte->setAttribute("nextid", "1");

                    $delsy = $delte->appendChild($domct->createElement("sy"));
                    $delsy->setAttribute("id", $newid = ($delid . "." . $delteid . ".0"));
                    // $delsy->setAttribute("id", $newid = ($delid . "." . $delteid));
                    $delsy->setAttribute("lng", $sy0->getAttribute("lng"));
                    $delsy->setAttribute("v", $sy0->getAttribute("v"));
                    $delsy->setAttribute("w", $sy0->getAttribute("w"));

                    if ($sy0->hasAttribute("k")) {
                        $delsy->setAttribute("k", $sy0->getAttribute("k"));
                    }

                    $te = $sy0->parentNode;
                    $te->removeChild($sy0);

                    $sql_oldid = str_replace(".", "d", $oldid) . "d";
                    $sql_newid = str_replace(".", "d", $newid) . "d";

                    $sql = "UPDATE thit SET value = :new_id WHERE value = :old_id";

                    if ($request->get('debug')) {
                        printf("sql: %s<br/>\n", $sql);
                    } else {
                        $stmt = $connbas->prepare($sql);
                        $stmt->execute(array(':new_id' => $sql_newid, ':old_id' => $sql_oldid));
                        $stmt->closeCursor();
                    }

                    $sql = array();

                    $databox->saveCterms($domct);
                    if ($request->get('typ') == "CT") {
                        $r = $refresh_list->appendChild($ret->createElement("refresh"));
                        $r->setAttribute("type", "CT");
                        if ($refrid) {
                            $r->setAttribute("id", $refrid);
                        } else {
                            $r->setAttribute("id", "C");
                        }
                    } else {
                        $xmlct = str_replace(array("\r", "\n", "\t"), array("", "", ""), $domct->saveXML());
                        $xmlte = str_replace(array("\r", "\n", "\t"), array("", "", ""), $dom->saveXML());

                        $databox->saveThesaurus($dom);

                        $r = $refresh_list->appendChild($ret->createElement("refresh"));
                        $r->setAttribute("type", "TH");
                        if ($refrid) {
                            $r->setAttribute("id", $refrid);
                        } else {
                            $r->setAttribute("id", "T");
                        }
                    }

                    $ret2 = $this->getXMLTerm($app, $request->get('bid'), $te->getAttribute("id"), $request->get('typ'), $request->get('piv'), null, null, '1', null);

                    if ($sl = $ret2->getElementsByTagName("sy_list")->item(0)) {
                        $sl = $ret->importNode($sl, true);
                        $sy_list = $root->appendChild($sl);
                    }

                    if ($request->get('debug')) {
                        printf("url: %s<br/>\n", $url);
                        printf("<pre>" . $ret2->saveXML() . "</pre>");
                    }
                }
            }
        } catch (\Exception $e) {

        }

        return new Response($ret->saveXML(), 200, array('Content-Type' => 'text/xml'));
    }

    public function removeSpecificTermXml(Application $app, Request $request)
    {
        $ret = new \DOMDocument("1.0", "UTF-8");
        $ret->standalone = true;
        $ret->preserveWhiteSpace = false;

        $root = $ret->appendChild($ret->createElement("result"));
        $root->appendChild($ret->createCDATASection(var_export(array(
            'bid'   => $request->get('bid'),
            'id'    => $request->get('id'),
            'piv'   => $request->get('piv'),
            'debug' => $request->get('debug'),
        ), true)));

        $refresh_list = $root->appendChild($ret->createElement("refresh_list"));

        if (null === $bid = $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }

        try {
            $databox = $app['phraseanet.appbox']->get_databox((int) $bid);
            $domth = $databox->get_dom_thesaurus();
            $domct = $databox->get_dom_cterms();

            if ($domth && $domct) {
                $xpathth = new \DOMXPath($domth);
                $xpathct = new \DOMXPath($domct);
                if ($request->get('id') !== "") {    // secu pour pas exploser le thesaurus
                    $q = "/thesaurus//te[@id='" . $request->get('id') . "']";

                    if ($request->get('debug')) {
                        printf("q:%s<br/>\n", $q);
                    }

                    $thnode = $xpathth->query($q)->item(0);
                    if ($thnode) {
                        $chgids = array();
                        $pid = $thnode->parentNode->getAttribute("id");
                        if ($pid === "") {
                            $pid = "T";
                        }

                        $nodes = $xpathct->query("/cterms/te[@delbranch='1']");
                        if ( ! $nodes || ($nodes->length == 0)) {
                            $id = $domct->documentElement->getAttribute("nextid");

                            if ($request->get('debug')) {
                                printf("creating 'deleted' branch : id=%s<br/>\n", $id);
                            }

                            $domct->documentElement->setAttribute("nextid", (int) ($id) + 1);
                            $ct = $domct->documentElement->appendChild($domct->createElement("te"));
                            $ct->setAttribute("id", "C" . $id);
                            $ct->setAttribute("field", _('thesaurus:: corbeille'));
                            $ct->setAttribute("nextid", "0");
                            $ct->setAttribute("delbranch", "1");

                            $r = $refresh_list->appendChild($ret->createElement("refresh"));
                            $r->setAttribute("id", "C");
                            $r->setAttribute("type", "CT");
                        } else {
                            $ct = $nodes->item(0);
                            $r = $refresh_list->appendChild($ret->createElement("refresh"));
                            $r->setAttribute("id", $ct->getAttribute("id"));
                            $r->setAttribute("type", "CT");
                        }
                        $teid = (int) ($ct->getAttribute("nextid"));
                        $ct->setAttribute("nextid", $teid + 1);

                        $newte = $ct->appendChild($domct->importNode($thnode, true));
                        $oldid = $newte->getAttribute("id");

                        $this->renumerate($newte, $ct->getAttribute("id") . "." . $teid, $chgids);

                        $newid = $ct->getAttribute("id") . "." . $teid;
                        $soldid = str_replace(".", "d", $oldid) . "d";
                        $snewid = str_replace(".", "d", $newid) . "d";
                        $l = strlen($soldid) + 1;

                        $connbas = \connection::getPDOConnection($app, $bid);

                        $sql = "UPDATE thit SET value=CONCAT('$snewid', SUBSTRING(value FROM $l))
                                WHERE value LIKE :like";

                        $stmt = $connbas->prepare($sql);
                        $stmt->execute(array(':like' => $soldid . '%'));
                        $stmt->closeCursor();

                        $thnode->parentNode->removeChild($thnode);

                        if ($request->get('debug')) {
                            printf("<pre>%s</pre>", $domct->saveXML());
                        }

                        if ($request->get('debug')) {
                            printf("chgids: %s<br/>\n", var_export($chgids, true));
                        }

                        $databox->saveCterms($domct)->saveThesaurus($domth);

                        $r = $refresh_list->appendChild($ret->createElement("refresh"));
                        $r->setAttribute("id", $pid);
                        $r->setAttribute("type", "TH");
                    }
                }
            }
        } catch (\Exception $e) {

        }

        return new Response($ret->saveXML(), 200, array('Content-Type' => 'text/xml'));
    }

    public function getHtmlBranchXml(Application $app, Request $request)
    {
        $ret = new \DOMDocument("1.0", "UTF-8");
        $ret->standalone = true;
        $ret->preserveWhiteSpace = false;

        $root = $ret->appendChild($ret->createElement("result"));
        $root->appendChild($ret->createCDATASection(var_export(array(
            'bid'   => $request->get('bid'),
            'id'    => $request->get('id'),
            'typ'   => $request->get('typ'),
            'debug' => $request->get('debug'),
        ), true)));

        $html = $root->appendChild($ret->createElement("html"));

        if (null === $bid = $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }

        try {
            $databox = $app['phraseanet.appbox']->get_databox((int) $request->get('bid'));

            if ($request->get('typ') == "CT") {
                $xqroot = "cterms";
                $dom = $databox->get_dom_cterms();
            } else {
                $xqroot = "thesaurus";
                $dom = $databox->get_dom_thesaurus();
            }

            if ($dom) {
                $xpath = new \DOMXPath($dom);

                if ($request->get('id') == "T") {
                    $q = "/thesaurus";
                } elseif ($request->get('id') == "C") {
                    $q = "/cterms";
                } else {
                    $q = "/$xqroot//te[@id='" . $request->get('id') . "']";
                }

                if ($request->get('debug')) {
                    print("q:" . $q . "<br/>\n");
                }

                $node = $xpath->query($q)->item(0);

                $this->formatHTMLBranch($node, $ret, $html, 0);
            }
        } catch (\Exception $e) {

        }

        return new Response($ret->saveXML(), 200, array('Content-Type' => 'text/xml'));
    }

    private function formatHTMLBranch($srcnode, $dstdom, $dstnode, $depth)
    {
        $allsy = "";
        $nts = 0;

        for ($n = $srcnode->firstChild; $n; $n = $n->nextSibling) {
            if ($n->nodeName == "te" && $depth < 100) {
                $nts ++;

                $id = $n->getAttribute("id");
                $div_the = $dstnode->appendChild($dstdom->createElement("div"));
                $div_the->setAttribute("id", "THE_" . $id);
                $div_the->setAttribute("class", "s_");

                $u = $div_the->appendChild($dstdom->createElement("u"));
                $u->setAttribute("id", "THP_" . $id);

                $div_thb = $dstnode->appendChild($dstdom->createElement("div"));
                $div_thb->setAttribute("id", "THB_" . $id);

                $t = $this->formatHTMLBranch($n, $dstdom, $div_thb, $depth + 1);
                if ($t["nts"] == 0) {
                    $u->setAttribute("class", "nots");
                    $div_thb->setAttribute("class", "ob");
                } else {
                    $u->appendChild($dstdom->createTextNode("-"));
                    $div_thb->setAttribute("class", "OB");
                }

                $div_the->appendChild($dstdom->createTextNode($t["allsy"]));
            } elseif ($n->nodeName == "sy") {
                $allsy .= ( $allsy ? " ; " : "") . $n->getAttribute("v");
            }
        }

        if ($allsy == "") {
            $allsy = "THESAURUS";
        }

        return array("allsy" => $allsy, "nts"   => $nts);
    }

    public function getSynonymXml(Application $app, Request $request)
    {
        $ret = new \DOMDocument("1.0", "UTF-8");
        $ret->standalone = true;
        $ret->preserveWhiteSpace = false;

        $root = $ret->appendChild($ret->createElement("result"));
        $root->appendChild($ret->createCDATASection(var_export(array(
            'bid'   => $request->get('bid'),
            'id'    => $request->get('id'),
            'typ'   => $request->get('typ'),
            'debug' => $request->get('debug'),
        ), true)));

        if (null === $bid = $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }

        try {
            $databox = $app['phraseanet.appbox']->get_databox((int) $bid);
            $connbas = \connection::getPDOConnection($app, $bid);

            if ($request->get('typ') == "CT") {
                $xqroot = "cterms";
                $dom = $databox->get_dom_cterms();
            } else {
                $xqroot = "thesaurus";
                $dom = $databox->get_dom_thesaurus();
            }

            if ($dom) {
                $xpath = new \DOMXPath($dom);

                if ($request->get('id') == "T") {
                    $q = "/thesaurus";
                } elseif ($request->get('id') == "C") {
                    $q = "/cterms";
                } else {
                    $q = "/$xqroot//sy[@id='" . $request->get('id') . "']";
                }

                if ($request->get('debug')) {
                    print("q:" . $q . "<br/>\n");
                }

                $nodes = $xpath->query($q);
                if ($nodes->length > 0) {
                    $t = $nodes->item(0)->getAttribute("v");

                    if (($k = $nodes->item(0)->getAttribute("k"))) {
                        $t .= " (" . $k . ")";
                    }

                    $fullpath_html = "<span class='path_separator'> / </span><span class='main_term'>" . $t . "</span>";
                    $fullpath = " / " . $t;

                    $sy = $root->appendchild($ret->createElement("sy"));
                    $sy->setAttribute("t", $t);
                    foreach (array("v", "w", "k", "lng", "id") as $a) {
                        if ($nodes->item(0)->hasAttribute($a)) {
                            $sy->setAttribute($a, $nodes->item(0)->getAttribute($a));
                        }
                    }

                    for ($depth = -1, $n = $nodes->item(0)->parentNode->parentNode; $n; $n = $n->parentNode, $depth -- ) {
                        if ($n->nodeName == "te") {
                            if ($request->get('debug')) {
                                printf("parent:%s<br/>\n", $n->nodeName);
                            }
                            if ($request->get('typ') == "CT" && ($fld = $n->getAttribute("field")) != "") {
                                $fullpath = " / " . $fld . $fullpath;
                                if ($depth == 0) {
                                    $fullpath_html = "<span class='path_separator'> / </span><span class='main_term'>" . $fld . "</span>" . $fullpath_html;
                                } else {
                                    $fullpath_html = "<span class='path_separator'> / </span>" . $fld . $fullpath_html;
                                }
                                break;
                            }
                            $firstsy = $goodsy = null;
                            for ($n2 = $n->firstChild; $n2; $n2 = $n2->nextSibling) {
                                if ($n2->nodeName == "sy") {
                                    $t = $n2->getAttribute("v");

                                    if (! $firstsy) {
                                        $firstsy = $t;
                                    }
                                    if ($n2->getAttribute("lng") == $request->get('piv')) {
                                        if ($request->get('debug')) {
                                            printf("fullpath : found '%s' in %s<br/>\n", $sy, $n2->getAttribute("lng"));
                                        }
                                        $goodsy = $t;
                                        break;
                                    }
                                }
                            }
                            if (! $goodsy) {
                                $goodsy = $firstsy;
                            }
                            $fullpath = " / " . $goodsy . $fullpath;
                            $fullpath_html = "<span class='path_separator'> / </span>" . $goodsy . $fullpath_html;
                        }
                    }
                    $fp = $root->appendchild($ret->createElement("fullpath"));
                    $fp->appendChild($ret->createTextNode($fullpath));

                    $fp = $root->appendchild($ret->createElement("fullpath_html"));
                    $fp->appendChild($ret->createTextNode($fullpath_html));

                    // $id = "S" . str_replace(".", "d", substr($nodes->item(0)->getAttribute("id"), 1)) . "d";
                    $id = str_replace(".", "d", $nodes->item(0)->getAttribute("id")) . "d";
                    $hits = "0";

                    $sql = "SELECT COUNT(DISTINCT(record_id)) AS hits FROM thit WHERE value = :id";

                    $stmt = $connbas->prepare($sql);
                    $stmt->execute(array(':id'    => $id));
                    $rowbas2 = $stmt->fetch(\PDO::FETCH_ASSOC);
                    $stmt->closeCursor();

                    if ($request->get('debug')) {
                        printf("sql: %s<br/>\n", $sql);
                    }
                    if ($rowbas2) {
                        $hits = $rowbas2["hits"];
                    }
                    $n = $root->appendchild($ret->createElement("hits"));
                    $n->appendChild($ret->createTextNode($hits));
                }
            }
        } catch (\Exception $e) {

        }

        return new Response($ret->saveXML(), 200, array('Content-Type' => 'text/xml'));
    }

    public function getTermXml(Application $app, Request $request)
    {
        return new Response(
            $this->getXMLTerm($app, $request->get('bid'), $request->get('id'), $request->get('typ'),
                $request->get('piv'), $request->get('sortsy'), $request->get('sel'), $request->get('nots'),
                $request->get('acf'), $request->get('debug'))->saveXML(),
            200,
            array('Content-Type' => 'text/xml')
        );
    }

    private function getXMLTerm(Application $app, $bid, $id, $typ, $piv, $sortsy, $sel, $nots, $acf, $debug = false)
    {
        $ret = new \DOMDocument("1.0", "UTF-8");
        $ret->standalone = true;
        $ret->preserveWhiteSpace = false;

        $root = $ret->appendChild($ret->createElement("result"));
        $root->appendChild($ret->createCDATASection(var_export(array(
            "bid"    => $bid,
            "id"     => $id,
            "typ"    => $typ,
            "piv"    => $piv,
            "sortsy" => $sortsy,
            "sel"    => $sel,
            "nots"   => $nots,
            "acf"    => $acf,
            "debug"  => $debug,
        ), true)));

        $cfield = $root->appendChild($ret->createElement("cfield"));
        $ts_list = $root->appendChild($ret->createElement("ts_list"));
        $sy_list = $root->appendChild($ret->createElement("sy_list"));

        if ($bid !== null) {
            try {
                $databox = $app['phraseanet.appbox']->get_databox((int) $bid);
                $connbas = $databox->get_connection();

                if ($typ == "CT") {
                    $xqroot = "cterms";
                    $dom = $databox->get_dom_cterms();
                } else {
                    $xqroot = "thesaurus";
                    $dom = $databox->get_dom_thesaurus();
                }

                $meta = $databox->get_meta_structure();

                if ($dom) {
                    $xpath = new \DOMXPath($dom);
                    if ($typ == "TH" && $acf) {
                        $cfield->setAttribute("field", $acf);

                        // on doit verifier si le terme demande est accessible e partir de ce champ acf
                        if ($acf == '*') {
                            // le champ "*" est la corbeille, il est toujours accepte
                            $cfield->setAttribute("acceptable", "1");
                        } else {
                            if (($databox_field = $meta->get_element_by_name($acf)) instanceof \databox_field) {
                                $tbranch = $databox_field->get_tbranch();
                                $q = "(" . $tbranch . ")/descendant-or-self::te[@id='" . $id . "']";

                                if ($debug) {
                                    printf("tbranch-q = \" $q \" <br/>\n");
                                }

                                $nodes = $xpath->query($q);
                                $cfield->setAttribute("acceptable", ($nodes->length > 0) ? "1" : "0");
                            }
                        }
                    }

                    if ($id == "T") {
                        $q = "/thesaurus";
                    } elseif ($id == "C") {
                        $q = "/cterms";
                    } else {
                        $q = "/$xqroot//te[@id='" . $id . "']";
                    }
                    if ($debug) {
                        print("q:" . $q . "<br/>\n");
                    }

                    $nodes = $xpath->query($q);
                    $root->setAttribute('found', '' . $nodes->length);
                    if ($nodes->length > 0) {
                        $nts = 0;
                        $tts = array();
                        // on dresse la liste des termes specifiques avec comme cle le synonyme
                        // dans la langue pivot
                        for ($n = $nodes->item(0)->firstChild; $n; $n = $n->nextSibling) {
                            if ($n->nodeName == "te") {
                                $nts ++;
                                if (! $nots) {
                                    if ($typ == "CT" && $id == "C") {
                                        $realksy = $allsy = $n->getAttribute("field");
                                    } else {
                                        $allsy = "";
                                        $firstksy = null;
                                        $ksy = $realksy = null;
                                        // on liste les sy pour fabriquer la cle
                                        for ($n2 = $n->firstChild; $n2; $n2 = $n2->nextSibling) {
                                            if ($n2->nodeName == "sy") {
                                                $lng = $n2->getAttribute("lng");
                                                $t = $n2->getAttribute("v");
                                                $ksy = $n2->getAttribute("w");
                                                if ($k = $n2->getAttribute("k")) {
                                                    //        $t .= " ($k)";
                                                    //        $ksy .= " ($k)";
                                                }
                                                if (! $firstksy) {
                                                    $firstksy = $ksy;
                                                }
                                                if (! $realksy && $piv && $lng == $piv) {
                                                    $realksy = $ksy;
                                                    $allsy = $t . ($allsy ? " ; " : "") . $allsy;
                                                } else {
                                                    $allsy .= ( $allsy ? " ; " : "") . $t;
                                                }
                                            }
                                        }
                                        if (! $realksy) {
                                            $realksy = $firstksy;
                                        }
                                    }
                                    if ($sortsy && $piv) {
                                        for ($uniq = 0; $uniq < 9999; $uniq ++) {
                                            if ( ! isset($tts[$realksy . "_" . $uniq])) {
                                                break;
                                            }
                                        }
                                        $tts[$realksy . "_" . $uniq] = array(
                                            "id"     => $n->getAttribute("id"),
                                            "allsy"  => $allsy,
                                            "nchild" => $xpath->query("te", $n)->length
                                        );
                                    } else {
                                        $tts[] = array(
                                            "id"     => $n->getAttribute("id"),
                                            "allsy"  => $allsy,
                                            "nchild" => $xpath->query("te", $n)->length
                                        );
                                    }
                                }
                            } elseif ($n->nodeName == "sy") {
                                $value_id = str_replace(".", "d", $n->getAttribute("id")) . "d";
                                $hits = "";

                                $sql = "SELECT COUNT(DISTINCT(record_id)) AS hits
                                        FROM thit WHERE value = :id";

                                $stmt = $connbas->prepare($sql);
                                $stmt->execute(array(':id'    => $value_id));
                                $rowbas2 = $stmt->fetch(\PDO::FETCH_ASSOC);
                                $stmt->closeCursor();

                                if ($rowbas2) {
                                    $hits = $rowbas2["hits"];
                                }

                                $sy = $sy_list->appendChild($ret->createElement("sy"));

                                $sy->setAttribute("id", $n->getAttribute("id"));
                                $sy->setAttribute("v", $t = $n->getAttribute("v"));
                                $sy->setAttribute("w", $n->getAttribute("w"));
                                $sy->setAttribute("hits", $hits);
                                $sy->setAttribute("lng", $lng = $n->getAttribute("lng"));
                                if (($k = $n->getAttribute("k"))) {
                                    $sy->setAttribute("k", $k);
                                    //        $t .= " (" . $k . ")";
                                }
                                $sy->setAttribute("t", $t);
                                if ($n->getAttribute("id") == $sel) {
                                    $sy->setAttribute("sel", "1");
                                }
                            }
                        }
                        $ts_list->setAttribute("nts", $nts);

                        if ($sortsy && $piv) {
                            ksort($tts, SORT_STRING);
                        }
                        if ($debug) {
                            printf("tts : <pre>%s</pre><br/>\n", var_export($tts, true));
                        }
                        foreach ($tts as $ts) {
                            $newts = $ts_list->appendChild($ret->createElement("ts"));
                            $newts->setAttribute("id", $ts["id"]);
                            $newts->setAttribute("nts", $ts["nchild"]);
                            $newts->appendChild($ret->createTextNode($ts["allsy"]));
                        }

                        $fullpath_html = $fullpath = "";
                        for ($depth = 0, $n = $nodes->item(0); $n; $n = $n->parentNode, $depth -- ) {
                            if ($n->nodeName == "te") {
                                if ($debug) {
                                    printf("parent:%s<br/>\n", $n->nodeName);
                                }
                                if ($typ == "CT" && ($fld = $n->getAttribute("field")) != "") {
                                    // la source provient des candidats pour ce champ
                                    if ($debug) {
                                        printf("field:%s<br/>\n", $fld);
                                    }

                                    $cfield->setAttribute("field", $fld);
                                    $cfield->setAttribute("delbranch", $n->getAttribute("delbranch"));

                                    $fullpath = " / " . $fld . $fullpath;

                                    if ($depth == 0) {
                                        $fullpath_html = "<span class='path_separator'> / </span><span class='main_term'>" . $fld . "</span>" . $fullpath_html;
                                    } else {
                                        $fullpath_html = "<span class='path_separator'> / </span>" . $fld . $fullpath_html;
                                    }
                                    break;
                                }
                                $firstsy = $goodsy = null;
                                for ($n2 = $n->firstChild; $n2; $n2 = $n2->nextSibling) {
                                    if ($n2->nodeName == "sy") {
                                        $sy = $n2->getAttribute("v");
                                        if (! $firstsy) {
                                            $firstsy = $sy;
                                            if ($debug) {
                                                printf("fullpath : firstsy='%s' in %s<br/>\n", $firstsy, $n2->getAttribute("lng"));
                                            }
                                        }
                                        if ($n2->getAttribute("lng") == $piv) {
                                            if ($debug) {
                                                printf("fullpath : found '%s' in %s<br/>\n", $sy, $n2->getAttribute("lng"));
                                            }
                                            $goodsy = $sy;
                                            break;
                                        }
                                    }
                                }
                                if (! $goodsy) {
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
                        $fp = $root->appendchild($ret->createElement("fullpath"));
                        $fp->appendChild($ret->createTextNode($fullpath));

                        $fp = $root->appendchild($ret->createElement("fullpath_html"));
                        $fp->appendChild($ret->createTextNode($fullpath_html));

                        $value_id = str_replace(".", "d", $nodes->item(0)->getAttribute("id")) . "d";
                        $hits = "0";

                        $sql = "SELECT COUNT(DISTINCT(record_id)) AS hits
                        FROM thit WHERE value = :id";

                        $stmt = $connbas->prepare($sql);
                        $stmt->execute(array(':id'    => $value_id));
                        $rowbas2 = $stmt->fetch(\PDO::FETCH_ASSOC);
                        $stmt->closeCursor();

                        if ($rowbas2) {
                            $hits = $rowbas2["hits"];
                        }

                        $n = $root->appendchild($ret->createElement("hits"));
                        $n->appendChild($ret->createTextNode($hits));

                        $hits = "0";
                        $sql = "SELECT COUNT(DISTINCT(record_id)) AS hits
                                FROM thit WHERE value LIKE :like";

                        $stmt = $connbas->prepare($sql);
                        $stmt->execute(array(':like'  => $value_id . '%'));
                        $rowbas2 = $stmt->fetch(\PDO::FETCH_ASSOC);
                        $stmt->closeCursor();

                        if ($rowbas2) {
                            $hits = $rowbas2["hits"];
                        }

                        $n = $root->appendchild($ret->createElement("allhits"));
                        $n->appendChild($ret->createTextNode($hits));
                    }
                }
            } catch (\Exception $e) {

            }
        }

        return $ret;
    }

    public function killTermXml(Application $app, Request $request)
    {
        $ret = new \DOMDocument("1.0", "UTF-8");
        $ret->standalone = true;
        $ret->preserveWhiteSpace = false;

        $root = $ret->appendChild($ret->createElement("result"));
        $root->appendChild($ret->createCDATASection(var_export(array(
            'bid'   => $request->get('bid'),
            'id'    => $request->get('id'),
            'piv'   => $request->get('piv'),
            'typ'   => $request->get('typ'),
            'debug' => $request->get('debug'),
        ), true)));

        $refresh_list = $root->appendChild($ret->createElement("refresh_list"));

        if (null === $bid = $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }

        try {
            $databox = $app['phraseanet.appbox']->get_databox((int) $bid);
            $connbas = \connection::getPDOConnection($app, $bid);

            if ($request->get('typ') == "CT") {
                $xqroot = "cterms";
                $dom = $databox->get_dom_cterms();
            } else {
                $xqroot = "thesaurus";
                $dom = $databox->get_dom_thesaurus();
            }

            if ($dom) {
                $xpath = new \DOMXPath($dom);
                $q = "/$xqroot//te[@id='" . $request->get('id') . "']";

                if ($request->get('debug')) {
                    print("q:" . $q . "<br/>\n");
                }

                $sy0 = $xpath->query($q)->item(0);
                if ($sy0) {
                    $oldid = $sy0->getAttribute("id");
                    $refrid = $sy0->parentNode->getAttribute("id");

                    if ($request->get('debug')) {
                        print("oldid=$oldid ; refrid=$refrid<br/>\n");
                    }

                    $te = $sy0->parentNode;
                    $te->removeChild($sy0);

                    $xml_oldid = str_replace(".", "d", $oldid) . "d";
                    $sql = "DELETE FROM thit WHERE value LIKE :like";

                    $stmt = $connbas->prepare($sql);
                    $stmt->execute(array(':like' => $xml_oldid . '%'));
                    $stmt->closeCursor();

                    if ($request->get('typ') == "CT") {
                        $databox->saveCterms($dom);

                        $r = $refresh_list->appendChild($ret->createElement("refresh"));
                        $r->setAttribute("type", "CT");
                        $r->setAttribute("id", $refrid);
                    } else {
                        $databox->saveThesaurus($dom);

                        $r = $refresh_list->appendChild($ret->createElement("refresh"));
                        $r->setAttribute("type", "TH");
                        if ($refrid) {
                            $r->setAttribute("id", $refrid);
                        } else {
                            $r->setAttribute("id", "T");
                        }
                    }

                    $ret2 = $this->getXMLTerm($app, $bid, $te->getAttribute("id"), $request->get('typ'), $request->get('piv'), null, null, '1', null);

                    if ($sl = $ret2->getElementsByTagName("sy_list")->item(0)) {
                        $sl = $ret->importNode($sl, true);
                        $sy_list = $root->appendChild($sl);
                    }

                    if ($request->get('debug')) {
                        printf("<pre>" . $ret2->saveXML() . "</pre>");
                    }
                }
            }
        } catch (\Exception $e) {

        }

        return new Response($ret->saveXML(), 200, array('Content-Type' => 'text/xml'));
    }

    public function newSynonymXml(Application $app, Request $request)
    {
        $ret = new \DOMDocument("1.0", "UTF-8");
        $ret->standalone = true;
        $ret->preserveWhiteSpace = false;

        $root = $ret->appendChild($ret->createElement("result"));
        $root->appendChild($ret->createCDATASection(var_export(array(
            "bid"   => $request->get('bid'),
            "pid"   => $request->get('pid'),
            "piv"   => $request->get('piv'),
            "sylng" => $request->get('sylng'),
            "t"     => $request->get('t'),
            "k"     => $request->get('k'),
            "debug" => $request->get('debug'),
        ), true)));

        $refresh_list = $root->appendChild($ret->createElement("refresh_list"));

        if (null === $bid = $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }

        try {
            $databox = $app['phraseanet.appbox']->get_databox((int) $bid);
            $domth = $databox->get_dom_thesaurus();

            if ($domth) {
                $xpathth = new \DOMXPath($domth);
                if ($bid === "T") {
                    $q = "/thesaurus";
                } else {
                    $q = "/thesaurus//te[@id='" . $request->get('pid') . "']";
                }

                $te = $xpathth->query($q)->item(0);
                if ($te) {
                    $tenextid = (int) $te->getAttribute("nextid");
                    $te->setAttribute("nextid", $tenextid + 1);

                    $sy = $te->appendChild($domth->createElement("sy"));
                    $syid = $te->getAttribute("id") . "." . $tenextid;
                    $sy->setAttribute("id", $syid);

                    if ($request->get('debug')) {
                        printf("syid='%s'<br/>\n", $syid);
                    }

                    if ($request->get('sylng')) {
                        $sy->setAttribute("lng", $request->get('sylng'));
                    } else {
                        $sy->setAttribute("lng", "");
                    }

                    list($v, $k) = $this->splitTermAndContext($request->get('t'));

                    $k = trim($k) . trim($request->get('k'));
                    $w = $app['unicode']->remove_indexer_chars($v);

                    if ($k) {
                        $v .= " (" . $k . ")";
                    }

                    $k = $app['unicode']->remove_indexer_chars($k);

                    $sy->setAttribute("v", $v);
                    $sy->setAttribute("w", $w);

                    if ($request->get('debug')) {
                        printf("v='%s' w='%s'<br/>\n", $v, $w);
                    }

                    if ($k) {
                        $sy->setAttribute("k", $k);
                        if ($request->get('debug')) {
                            printf("k='%s'<br/>\n", $k);
                        }
                    }

                    $databox->saveThesaurus($domth);

                    $r = $refresh_list->appendChild($ret->createElement("refresh"));
                    $r->setAttribute("type", "TH");
                    $pid = $te->parentNode->getAttribute("id");

                    if ($pid == "") {
                        $pid = "T";
                    }

                    $r->setAttribute("id", $pid);
                }
            }
        } catch (\Exception $e) {

        }

        return new Response($ret->saveXML(), 200, array('Content-Type' => 'text/xml'));
    }

    private function splitTermAndContext($word)
    {
        $term = trim($word);
        $context = "";
        if (($po = strpos($term, "(")) !== false) {
            if (($pc = strpos($term, ")", $po)) !== false) {
                $context = trim(substr($term, $po + 1, $pc - $po - 1));
                $term = trim(substr($term, 0, $po));
            }
        }

        return array($term, $context);
    }

    public function newSpecificTermXml(Application $app, Request $request)
    {
        $ret = new \DOMDocument("1.0", "UTF-8");
        $ret->standalone = true;
        $ret->preserveWhiteSpace = false;

        $root = $ret->appendChild($ret->createElement("result"));
        $root->appendChild($ret->createCDATASection(var_export(array(
            "bid"     => $request->get('bid'),
            "pid"     => $request->get('pid'),
            "t"       => $request->get('t'),
            "k"       => $request->get('k'),
            "sylng"   => $request->get('sylng'),
            "reindex" => $request->get('reindex'),
            "debug"   => $request->get('debug'),
        ), true)));

        $refresh_list = $root->appendChild($ret->createElement("refresh_list"));

        if (null === $bid = $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }

        try {
            $connbas = \connection::getPDOConnection($app, $bid);
            $databox = $app['phraseanet.appbox']->get_databox((int) $bid);
            $domth = $databox->get_dom_thesaurus();

            if ($domth) {
                $xpathth = new \DOMXPath($domth);

                if ($request->get('pid') === "T") {
                    $q = "/thesaurus";
                } else {
                    $q = "/thesaurus//te[@id='" . $request->get('pid') . "']";
                }

                $parentnode = $xpathth->query($q)->item(0);
                if ($parentnode) {
                    $nid = $parentnode->getAttribute("nextid");
                    $parentnode->setAttribute("nextid", (int) $nid + 1);
                    $te = $parentnode->appendChild($domth->createElement("te"));

                    if ($request->get('pid') === "T") {
                        $te->setAttribute("id", $teid = "T" . ($nid));
                    } else {
                        $te->setAttribute("id", $teid = ($request->get('pid') . "." . $nid));
                    }

                    $te->setAttribute("nextid", "1");
                    $sy = $te->appendChild($domth->createElement("sy"));
                    $sy->setAttribute("id", $teid . ".0");

                    if ($request->get('sylng')) {
                        $sy->setAttribute("lng", $request->get('sylng'));
                    } else {
                        $sy->setAttribute("lng", "");
                    }

                    list($v, $k) = $this->splitTermAndContext($request->get('t'));
                    $k = trim($k) . trim($request->get('k'));

                    if ($request->get('debug')) {
                        printf("k='%s'<br/>\n", $k);
                    }

                    $w = $app['unicode']->remove_indexer_chars($v);

                    if ($k) {
                        $v .= " (" . $k . ")";
                    }

                    $k = $app['unicode']->remove_indexer_chars($k);

                    $sy->setAttribute("v", $v);
                    $sy->setAttribute("w", $w);

                    if ($request->get('debug')) {
                        printf("v='%s' w='%s'<br/>\n", $v, $w);
                    }

                    if ($k) {
                        $sy->setAttribute("k", $k);
                        if ($request->get('debug')) {
                            printf("k='%s'<br/>\n", $k);
                        }
                    }

                    $databox->saveThesaurus($domth);

                    if ($request->get('reindex') == "1") {
                        $sql = "UPDATE record SET status=status & ~2";
                        $stmt = $connbas->prepare($sql);
                        $stmt->execute();
                        $stmt->closeCursor();
                    }

                    $r = $refresh_list->appendChild($ret->createElement("refresh"));
                    $r->setAttribute("type", "TH");
                    $r->setAttribute("id", $request->get('pid'));
                }
            }
        } catch (\Exception $e) {

        }

        return new Response($ret->saveXML(), 200, array('Content-Type' => 'text/xml'));
    }

    public function openBranchesXml(Application $app, Request $request)
    {
        $ret = new \DOMDocument("1.0", "UTF-8");
        $ret->standalone = true;
        $ret->preserveWhiteSpace = false;

        $root = $ret->appendChild($ret->createElement("result"));
        $root->appendChild($ret->createCDATASection(var_export(array(
            "bid"    => $request->get('bid'),
            "id"     => $request->get('id'),
            "typ"    => $request->get('typ'),
            "t"      => $request->get('t'),
            "method" => $request->get('method'),
            "debug"  => $request->get('debug'),
        ), true)));

        $html = $root->appendChild($ret->createElement("html"));

        if (null === $bid = $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }

        try {
            $databox = $app['phraseanet.appbox']->get_databox((int) $bid);
            if ($request->get('typ') == "CT") {
                $xqroot = "cterms";
                $dom = $databox->get_dom_cterms();
            } else {
                $xqroot = "thesaurus";
                $dom = $databox->get_dom_thesaurus();
            }

            if ($dom) {
                $xpath = new \DOMXPath($dom);

                if ($request->get('id') == "T") {
                    $q = "/thesaurus";
                } elseif ($request->get('id') == "C") {
                    $q = "/cterms";
                } else {
                    $q = "/$xqroot//te[@id='" . $request->get('id') . "']";
                }

                if ($request->get('debug')) {
                    print("q:" . $q . "<br/>\n");
                }

                if (($znode = $xpath->query($q)->item(0))) {
                    if ($request->get('t')) {
                        $t = $this->splitTermAndContext($request->get('t'));
                        switch ($request->get('method')) {
                            case "begins":
                                $q2 = "starts-with(@w, '" . \thesaurus::xquery_escape($app['unicode']->remove_indexer_chars($t[0])) . "')";
                                if ($t[1]) {
                                    $q2 .= " and starts-with(@k, '" . \thesaurus::xquery_escape($app['unicode']->remove_indexer_chars($t[1])) . "')";
                                }
                                break;
                            case "contains":
                                $q2 = "contains(@w, '" . \thesaurus::xquery_escape($app['unicode']->remove_indexer_chars($t[0])) . "')";
                                if ($t[1]) {
                                    $q2 .= " and contains(@k, '" . \thesaurus::xquery_escape($app['unicode']->remove_indexer_chars($t[1])) . "')";
                                }
                                break;
                            case "equal":
                            default:
                                $q2 = "(@w='" . \thesaurus::xquery_escape($app['unicode']->remove_indexer_chars($t[0])) . "')";
                                if ($t[1]) {
                                    $q2 .= " and (@k='" . \thesaurus::xquery_escape($app['unicode']->remove_indexer_chars($t[1])) . "')";
                                }
                                break;
                        }
                        $q2 = "//sy[" . $q2 . "]";
                    }
                    if ($request->get('debug')) {
                        print("q2:" . $q2 . "<br/>\n");
                    }

                    $nodes = $xpath->query($q2, $znode);
                    for ($i = 0; $i < $nodes->length; $i ++) {
                        for ($n = $nodes->item($i)->parentNode; $n && $n->nodeType == XML_ELEMENT_NODE && $n->nodeName == "te"; $n = $n->parentNode) {
                            $n->setAttribute("open", "1");
                            if ($request->get('debug')) {
                                printf("opening node te id=%s<br/>\n", $n->getAttribute("id"));
                            }
                        }
                    }

                    $this->getBranchHTML($request->get('typ'), $znode, $ret, $html, 0);
                }
            }
        } catch (\Exception $e) {

        }

        return new Response($ret->saveXML(), 200, array('Content-Type' => 'text/xml'));
    }

    private function getBranchHTML($type, $srcnode, $dstdom, $dstnode, $depth)
    {
        $allsy = "";
        $nts = 0;
        for ($n = $srcnode->firstChild; $n; $n = $n->nextSibling) {
            if ($n->nodeType == XML_ELEMENT_NODE) {
                if ($n->nodeName == "te") {
                    $nts ++;
                    if ($n->getAttribute("open")) {
                        $id = $n->getAttribute("id");
                        $div_the = $dstnode->appendChild($dstdom->createElement("div"));
                        $div_the->setAttribute("id", "THE_" . $id);
                        $div_the->setAttribute("class", "s_");

                        $u = $div_the->appendChild($dstdom->createElement("u"));
                        $u->setAttribute("id", "THP_" . $id);

                        $div_thb = $dstnode->appendChild($dstdom->createElement("div"));
                        $div_thb->setAttribute("id", "THB_" . $id);

                        $t = $this->getBranchHTML($type, $n, $dstdom, $div_thb, $depth + 1);
                        if ($t["nts"] == 0) {
                            $u->setAttribute("class", "nots");
                            $div_thb->setAttribute("class", "ob");
                        } else {
                            $u->appendChild($dstdom->createTextNode("..."));
                            $div_thb->setAttribute("class", "hb");
                        }

                        $div_the->appendChild($dstdom->createTextNode($t["allsy"]));
                    }
                } elseif ($n->nodeName == "sy") {
                    $t = $n->getAttribute("v");
                    if ($k = $n->getAttribute("k")) {
                        //        $t .= " ($k)";
                    }
                    $allsy .= ( $allsy ? " ; " : "") . $t;
                }
            }
        }
        if ($allsy == "") {
            if ($type == "TH") {
                $allsy = "THESAURUS";
            } elseif ($type == "CT") {
                $allsy = $srcnode->getAttribute("field");
            }
        }

        return array("allsy" => $allsy, "nts"   => $nts);
    }

    public function RejectXml(Application $app, Request $request)
    {
        $ret = new \DOMDocument("1.0", "UTF-8");
        $ret->standalone = true;
        $ret->preserveWhiteSpace = false;

        $root = $ret->appendChild($ret->createElement("result"));
        $root->appendChild($ret->createCDATASection(var_export(array(
            'bid'   => $request->get('bid'),
            'id'    => $request->get('id'),
            'piv'   => $request->get('piv'),
            'debug' => $request->get('debug'),
        ), true)));

        $refresh_list = $root->appendChild($ret->createElement("refresh_list"));

        if (null === $bid = $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }

        try {
            $databox = $app['phraseanet.appbox']->get_databox((int) $bid);
            $connbas = \connection::getPDOConnection($app, $bid);

            $dom = $databox->get_cterms();

            if ($dom) {
                $xpath = new \DOMXPath($dom);
                $q = "/cterms//te[@id='" . $request->get('id') . "']";

                if ($request->get('debug')) {
                    print("q:" . $q . "<br/>\n");
                }

                $te = $xpath->query($q)->item(0);
                if ($te) {
                    if ($request->get('debug')) {
                        printf("found te : id=%s<br/>\n", $te->getAttribute("id"));
                    }

                    $this->doRejectBranch($connbas, $te);

                    $databox->saveCterms($dom);

                    $r = $refresh_list->appendChild($ret->createElement("refresh"));
                    $r->setAttribute("id", $te->parentNode->getAttribute("id"));
                    $r->setAttribute("type", "CT");
                }
            }
        } catch (\Exception $e) {

        }

        return new Response($ret->saveXML(), 200, array('Content-Type' => 'text/xml'));
    }

    private function doRejectBranch(\connection_pdo $connbas, &$node)
    {
        if (strlen($oldid = $node->getAttribute("id")) > 1) {
            $node->setAttribute("id", $newid = ("R" . substr($oldid, 1)));

            $thit_oldid = str_replace(".", "d", $oldid) . "d";
            $thit_newid = str_replace(".", "d", $newid) . "d";
            $sql = "UPDATE thit SET value = :new_value WHERE value = :old_value";
            $stmt = $connbas->prepare($sql);
            $stmt->execute(array(':old_value' => $thit_oldid, ':new_value' => $thit_newid));
            $stmt->closeCursor();
        }
        for ($n = $node->firstChild; $n; $n = $n->nextSibling) {
            if ($n->nodeType == XML_ELEMENT_NODE) {
                $this->doRejectBranch($connbas, $n);
            }
        }
    }

    public function searchCandidateXml(Application $app, Request $request)
    {
        if (null === $bid = $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }

        $ret = $this->doSearchCandidate($app, $request->get('bid'), $request->get('pid'), $request->get('t'), $request->get('k'), $request->get('piv'), $request->get('debug'));

        return new Response($ret->saveXML(), 200, array('Content-Type' => 'text/xml'));
    }

    private function doSearchCandidate(Application $app, $bid, $pid, $t, $k, $piv, $debug = false)
    {
        $ret = new \DOMDocument("1.0", "UTF-8");
        $ret->standalone = true;
        $ret->preserveWhiteSpace = false;

        $root = $ret->appendChild($ret->createElement("result"));
        $root->appendChild($ret->createCDATASection(var_export(array(
            "bid"   => $bid,
            "pid"   => $pid,
            "t"     => $t,
            "k"     => $k,
            "piv"   => $piv,
            "debug" => $debug,
        ), true)));

        $ctlist = $root->appendChild($ret->createElement("candidates_list"));

        try {
            $databox = $app['phraseanet.appbox']->get_databox((int) $bid);

            $domstruct = $databox->get_dom_structure();
            $domth = $databox->get_dom_thesaurus();
            $domct = $databox->get_dom_cterms();

            if ($domstruct && $domth && $domct) {
                $xpathth = new \DOMXPath($domth);
                $xpathct = new \DOMXPath($domct);

                // on cherche les champs d'oe peut provenir un candidat, en fct de l'endroit oe on veut inserer le nouveau terme
                $fields = array();
                $xpathstruct = new \DOMXPath($domstruct);
                $nodes = $xpathstruct->query("/record/description/*[@tbranch]");
                for ($i = 0; $i < $nodes->length; $i ++) {
                    $fieldname = $nodes->item($i)->nodeName;
                    $tbranch = $nodes->item($i)->getAttribute("tbranch");
                    if ($pid != "") {
                        $q = "(" . $tbranch . ")/descendant-or-self::te[@id='" . $pid . "']";
                    } else {
                        $q = "(" . $tbranch . ")/descendant-or-self::te[not(@id)]";
                    }

                    $fields[$fieldname] = array(
                        "name"     => $fieldname,
                        "tbranch"  => $tbranch,
                        "cid"      => null,
                        "sourceok" => false
                    );

                    if (! $tbranch) {
                        continue;
                    }

                    $l = $xpathth->query($q)->length;
                    if ($debug) {
                        printf("field '%s' : %s --: %d nodes<br/>\n", $fieldname, $q, $l);
                    }

                    if ($l > 0) {
                        // le pt d'insertion du nvo terme se trouve dans la tbranch du champ,
                        // donc ce champ peut etre source de candidats
                        $fields[$fieldname]["sourceok"] = true;
                    } else {
                        // le pt d'insertion du nvo terme ne se trouve PAS dans la tbranch du champ,
                        // donc ce champ ne peut pas etre source de candidats
                    }
                }
                // on considere que la source 'deleted' est toujours valide
                $fields["[deleted]"] = array(
                    "name"     => _('thesaurus:: corbeille'),
                    "tbranch"  => null,
                    "cid"      => null,
                    "sourceok" => true
                );

                if (count($fields) > 0) {
                    $q = "@w='" . \thesaurus::xquery_escape($app['unicode']->remove_indexer_chars($k)) . "'";
                    if ($k) {
                        if ($k != "*") {
                            $q .= " and @k='" . \thesaurus::xquery_escape($app['unicode']->remove_indexer_chars($k)) . "'";
                        }
                    } else {
                        $q .= " and not(@k)";
                    }
                    $q = "/cterms//te[./sy[$q]]";

                    if ($debug) {
                        printf("xquery : %s<br/>\n", $q);
                    }

                    // $root->appendChild($ret->createCDATASection( $q ));
                    $nodes = $xpathct->query($q);
                    // le terme peut etre present dans plusieurs candidats
                    for ($i = 0; $i < $nodes->length; $i ++) {
                        // on a trouve le terme dans les candidats, mais en provenance de quel champ ?.. on remonte au champ candidat
                        for ($n = $nodes->item($i)->parentNode; $n && $n->parentNode && $n->parentNode->nodeName != "cterms"; $n = $n->parentNode) {
                            ;
                        }
                        if ($debug) {
                            printf("proposed in field %s<br/>\n", $n->getAttribute("field"));
                        }
                        if ($n && array_key_exists($f = $n->getAttribute("field"), $fields)) {
                            $fields[$f]["cid"] = $nodes->item($i)->getAttribute("id");
                        }
                    }
                    if ($debug) {
                        printf("fields:<pre>%s</pre><br/>\n", var_export($fields, true));
                    }
                }

                foreach ($fields as $kfield => $field) {
                    if ($field["cid"] === null) {
                        continue;
                    }
                    $ct = $ctlist->appendChild($ret->createElement("ct"));
                    $ct->setAttribute("field", $field["name"]);
                    $ct->setAttribute("sourceok", $field["sourceok"] ? "1" : "0");
                    if ($field["cid"] !== null) {
                        $ct->setAttribute("id", $field["cid"]);
                    }
                }
            }
        } catch (\Exception $e) {

        }

        return $ret;
    }

    public function searchNoHitsXml(Application $app, Request $request)
    {
        $ret = new \DOMDocument("1.0", "UTF-8");
        $ret->standalone = true;
        $ret->preserveWhiteSpace = false;

        $root = $ret->appendChild($ret->createElement("result"));
        $root->appendChild($ret->createCDATASection(var_export(array(
            "bid"   => $request->get('bid'),
            "pid"   => $request->get('pid'),
            'typ'   => $request->get('typ'),
            'id'    => $request->get('id'),
            "piv"   => $request->get('piv'),
            "debug" => $request->get('debug'),
        ), true)));

        if (null === $bid = $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }

        try {
            $databox = $app['phraseanet.appbox']->get_databox((int) $bid);
            $connbas = \connection::getPDOConnection($app, $bid);

            $s_thits = ';';
            $sql = "SELECT DISTINCT value FROM thit";
            $stmt = $connbas->prepare($sql);
            $stmt->execute();
            $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            foreach ($rs as $rowbas) {
                $s_thits .= str_replace('d', '.', $rowbas['value']) . ';';
            }

            if ($request->get('typ') == 'CT') {
                $dom = $databox->get_dom_cterms();
            } else {
                $dom = $databox->get_dom_thesaurus();
            }

            if ($dom) {
                $xpath = new \DOMXPath($dom);

                if ($request->get('id') == "T") {
                    $q = "/thesaurus";
                } elseif ($request->get('id') == "C") {
                    $q = "/cterms";
                } else {
                    $q = "//te[@id='" . $request->get('id') . "']";
                }

                if ($znode = $xpath->query($q)->item(0)) {
                    $root->setAttribute('n_nohits', (string) $this->countNohits($znode, $s_thits));
                }
            }
        } catch (\Exception $e) {

        }

        return new Response($ret->saveXML(), 200, array('Content-Type' => 'text/xml'));
    }

    private function countNohits($node, &$s_thits)
    {
        $ret = 0;
        if ($node->nodeType == XML_ELEMENT_NODE) { // && $node->nodeName=='te')
            $id = $node->getAttribute('id') . '.';

            if ((strpos($s_thits, $id)) === false && ! $node->getAttribute('field')) {
                // this id has no hits, neither any of his children
                $ret = 1;
            } else {
                // this id (or a child) has hit, must check children
                for ($n = $node->firstChild; $n; $n = $n->nextSibling) {
                    $ret += $this->countNohits($n, $s_thits);
                }
            }
        }

        return $ret;
    }

    private function call($method)
    {
        return sprintf('%s::%s', __CLASS__, $method);
    }
}
