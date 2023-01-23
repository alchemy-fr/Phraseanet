<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Controller\Thesaurus;

use Alchemy\Phrasea\Application\Helper\DispatcherAware;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Core\Event\Thesaurus as ThesaurusEvent;
use Alchemy\Phrasea\Core\Event\Thesaurus\ThesaurusEvents;
use Alchemy\Phrasea\SearchEngine\Elastic\ElasticsearchOptions;
use Alchemy\Phrasea\WorkerManager\Event\PopulateIndexEvent;
use Alchemy\Phrasea\WorkerManager\Event\WorkerEvents;
use Doctrine\DBAL\Driver\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class ThesaurusController extends Controller
{
    use DispatcherAware;

    public function accept(Request $request)
    {
        if (null === $bid = $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }

        $dom = $this->getXMLTerm($bid, $request->get('src'), 'CT', $request->get('piv'), '0', null, '1', null);

        $cterm_found = (int) $dom->documentElement->getAttribute('found');

        $fullpath_src = $fullpath_tgt = $nts = $cfield = $term_found = $acceptable = null;

        if ($cterm_found) {
            $fullpath_src = $dom->getElementsByTagName("fullpath_html")->item(0)->firstChild->nodeValue;

            /** @var \DOMElement $node */
            $node = $dom->getElementsByTagName("ts_list")->item(0);
            $nts = $node->getAttribute("nts");

            /** @var \DOMElement $cfield */
            $cfield = $dom->getElementsByTagName("cfield")->item(0);
            if ($cfield) {
                if ($cfield->getAttribute("delbranch")) {
                    $cfield_t = '*';
                } else {
                    $cfield_t = $cfield->getAttribute("field");
                }
            } else {
                $cfield_t = null;
            }

            $dom = $this->getXMLTerm($bid, $request->get('tgt'), 'TH', $request->get('piv'), '0', null, '1', $cfield_t);

            $term_found = (int) $dom->documentElement->getAttribute('found');

            if ($term_found) {
                $fullpath_tgt = $dom->getElementsByTagName("fullpath_html")->item(0)->firstChild->nodeValue;
                $acceptable = (int) $dom->getElementsByTagName("cfield")->item(0)->getAttribute("acceptable");
            }
        }

        return $this->render('thesaurus/accept.html.twig', [
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
        ]);
    }

    public function exportText(Request $request)
    {
        $tnodes = [];
        $output = '';

        if (null === $bid = $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }

        if ($request->get("typ") == "TH" || $request->get("typ") == "CT") {
            try {
                $databox = $this->findDataboxById((int) $bid);

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
                    $this->export0($xpathth->query($q)->item(0), $tnodes, $output, $request->get('iln'), $request->get('ilg'), $request->get('osl'));
                }
            } catch (\Exception $e) {

            }
        }

        return $this->render('thesaurus/export-text.html.twig', [
            'output'  => $output,
            'smp' => $request->get('smp'),
        ]);
    }

    private function printTNodes(&$output, &$tnodes, $iln, $ilg, $osl)
    {
        $numlig = $iln == "1";
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
                    $output .= $tabs . $node["name"] . "\n";
                    break;
                case "TRASH":
                    if ($numlig) {
                        $output .= $ilig ++ . "\t";
                    }
                    $output .= $tabs . "{TRASH}\n";
                    break;
                case "FIELD":
                    if ($numlig) {
                        $output .= $ilig ++ . "\t";
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
                        foreach ($node["syns"] as $syn) {
                            if ($isyn > 0) {
                                $output .= " ; ";
                            }
                            $output .= $syn["v"];
                            if ($ilg) {
                                $output .= " [" . $syn["lng"] . "]";
                            }
                            $isyn ++;
                        }
                        $output .= "\n";
                    } else {
                        foreach ($node["syns"] as $syn) {
                            if ($numlig) {
                                $output .= $ilig ++ . "\t";
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

    private function exportNode(\DOMElement $node, &$tnodes, $depth)
    {
        if ($node->nodeType == XML_ELEMENT_NODE) {
            if (($nname = $node->nodeName) == "thesaurus" || $nname == "cterms") {
                $tnodes[] = [
                    "type"  => "ROOT",
                    "depth" => $depth,
                    "name"  => $nname,
                    "cdate" => $node->getAttribute("creation_date"),
                    "mdate" => $node->getAttribute("modification_date")
                ];
            } elseif (($fld = $node->getAttribute("field"))) {
                if ($node->getAttribute("delbranch")) {
                    $tnodes[] = [
                        "type"    => "TRASH",
                        "depth"   => $depth,
                        "name"    => $fld
                    ];
                } else {
                    $tnodes[] = [
                        "type"  => "FIELD",
                        "depth" => $depth,
                        "name"  => $fld
                    ];
                }
            } else {
                $tsy = [];
                for ($n = $node->firstChild; $n; $n = $n->nextSibling) {
                    if ($n->nodeName == "sy") {
                        $tsy[] = [
                            "v"       => $n->getAttribute("v"),
                            "lng"     => $n->getAttribute("lng")
                        ];
                    }
                }
                $tnodes[] = ["type"  => "TERM", "depth" => $depth, "syns"  => $tsy];
            }
        }
    }

    private function export0($znode, &$tnodes, &$output, $iln, $ilg, $osl)
    {
        $nodes = [];
        $depth = 0;

        for ($node = $znode->parentNode; $node; $node = $node->parentNode) {
            if ($node->nodeType == XML_ELEMENT_NODE) {
                $nodes[$depth] = $node;
                $depth++;
            }
        }
        $nodes = array_reverse($nodes);

        foreach ($nodes as $depth => $node) {
            $this->exportNode($node, $tnodes, $depth);
        }

        $this->export($znode, $tnodes, count($nodes));
        $this->printTNodes($output, $tnodes, $iln, $ilg, $osl);
    }

    private function export($node, &$tnodes, $depth = 0)
    {
        if ($node->nodeType == XML_ELEMENT_NODE) {
            $this->exportNode($node, $tnodes, $depth);
        }
        for ($n = $node->firstChild; $n; $n = $n->nextSibling) {
            if ($n->nodeName == "te") {
                $this->export($n, $tnodes, $depth + 1);
            }
        }
    }

    public function exportTextDialog(Request $request)
    {
        return $this->render('thesaurus/export-text-dialog.html.twig', [
            'dlg' => $request->get('dlg'),
            'bid' => $request->get('bid'),
            'typ' => $request->get('typ'),
            'piv' => $request->get('piv'),
            'id'  => $request->get('id'),
        ]);
    }

    public function exportTopics(Request $request)
    {
        $obr = explode(';', $request->get('obr'));

        $t_lng = [];

        if ($request->get('ofm') == 'tofiles') {
            $t_lng = array_map(function ($code) {
                $lng_code = explode('_', $code);

                return $lng_code[0];
            }, array_keys($this->getAvailableLocales()));
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
            default:
            case 'all_closed':
                $default_display = 'closed';
                $opened_display = '';
                break;
        }

        $now = date('YmdHis');
        $lngs = [];
        try {
            $databox = $this->findDataboxById((int) $request->get("bid"));
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
                    $dom->preserveWhiteSpace = false;
                    $dom->formatOutput = true;
                    $root = $dom->appendChild($dom->createElementNS('www.phraseanet.com', 'phraseanet:topics'));

                    $root->appendChild($dom->createComment($this->app->trans('thesaurus:: fichier genere le %date%', ['%date%' => $now])));

                    $root->appendChild($dom->createElement('display'))
                        ->appendChild($dom->createElement('defaultview'))
                        ->appendChild($dom->createTextNode($default_display));

                    $this->export0Topics(
                        $xpathth->query($q)
                            ->item(0),
                        $dom,
                        $root,
                        $lng,
                        $request->get("srt"),
                        $request->get("sth"),
                        $request->get("sand"),
                        $opened_display,
                        $obr
                    );

                    if ($request->get("ofm") == 'toscreen') {
                        $lngs[$lng] = str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $dom->saveXML());
                    } elseif ($request->get("ofm") == 'tofiles') {
                        $fname = 'topics_' . $lng . '.xml';

                        $topicsPath = $this->app['root.path'] . '/config/topics/';
                        @rename($topicsPath . $fname, $topicsPath . 'topics_' . $lng . '_BKP_' . $now . '.xml');

                        if ($dom->save($topicsPath . $fname)) {
                            $lngs[$lng] = \p4string::MakeString($this->app->trans('thesaurus:: fichier genere : %filename%', ['%filename%' => $fname]));
                        } else {
                            $lngs[$lng] = \p4string::MakeString($this->app->trans('thesaurus:: erreur lors de l\'enregsitrement du fichier'));
                        }
                    }
                }
            }
        } catch (\Exception $e) {

        }

        return $this->render('thesaurus/export-topics.html.twig', [
            'lngs' => $lngs,
            'ofm'  => $request->get('ofm'),
        ]);
    }

    private function export0Topics(
        $znode,
        \DOMDocument $dom,
        \DOMNode $root,
        $lng,
        $srt,
        $sth,
        $sand,
        $opened_display,
        $obr
    )
    {
        $topics = $root->appendChild($dom->createElement('topics'));
        $this->doExportTopics($znode, $dom, $topics, '', $lng, $srt, $sth, $sand, $opened_display, $obr, 0);
    }

    private function doExportTopics(
        \DOMElement $node,
        \DOMDocument $dom,
        \DOMNode $topics,
        $prevQuery,
        $lng,
        $srt,
        $sth,
        $sand,
        $opened_display,
        $obr,
        $depth = 0
    )
    {
        $ntopics = 0;
        if ($node->nodeType == XML_ELEMENT_NODE) {
            /** @var \DOMElement[] $t_node */
            $t_node = [];
            $t_label = [];
            $t_sort = [];
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
                    $t_node[$i] = $n;
                    $t_label[$i] = $label;

                    $i ++;
                }
            }

            if ($srt) {
                natcasesort($t_sort);
            }

            foreach ($t_sort as $i => $query) {
                /** @var \DOMElement $topic */
                $topic = $topics->appendChild($dom->createElement('topic'));
                if ($opened_display != '' && in_array($t_node[$i]->getAttribute('id'), $obr)) {
                    $topic->setAttribute('view', $opened_display);
                }
                $topic->appendChild($dom->createElement('label'))->appendChild($dom->createTextNode($t_label[$i]));

                $query = '"' . $query . '"';
                if ($sth) {
                    $query = '*:' . $query;
                    if ($sand) {
                        $query = '(' . $query . ')';
                    }
                }

                if ($sand && $prevQuery != '') {
                    $query = $prevQuery . ' ' . $this->app->trans('phraseanet::technique:: et') . ' ' . $query . '';
                }

                $topic->appendChild($dom->createElement('query'))->appendChild($dom->createTextNode('' . $query . ''));

                $topics2 = $dom->createElement('topics');

                if ($this->doExportTopics(
                        $t_node[$i],
                        $dom,
                        $topics2,
                        $query,
                        $lng,
                        $srt,
                        $sth,
                        $sand,
                        $opened_display,
                        $obr,
                        $depth + 1
                    ) > 0) {
                    $topic->appendChild($topics2);
                }
            }
        }

        return $ntopics;
    }

    public function exportTopicsDialog(Request $request)
    {
        return $this->render('thesaurus/export-topics-dialog.html.twig', [
            'bid' => $request->get('bid'),
            'piv' => $request->get('piv'),
            'typ' => $request->get('typ'),
            'dlg' => $request->get('dlg'),
            'id'  => $request->get('id'),
            'obr'  => $request->get('obr'),
        ]);
    }

    public function import(Request $request)
    {
        set_time_limit(300);

        $err = '';

        if (($bid = $request->get("bid")) === null) {
            return new Response('Missing bid parameter', 400);
        }

        if ($request->files->get('fil') === null) {
            return new Response('Missing file to import', 400);
        }



        try {
            $databox = $this->findDataboxById((int) $bid);

            $dom = $databox->get_dom_thesaurus();

            if ($dom) {
                if ($request->get('id') == '') {
                    // on importe un theaurus entier
                    /** @var \DOMElement $node */
                    $node = $dom->documentElement;
                    while ($node->firstChild) {
                        $node->removeChild($node->firstChild);
                    }

                    $cbad = [];
                    $cok = [];
                    for ($i = 0; $i < 32; $i ++) {
                        $cbad[] = chr($i);
                        $cok[] = '_';
                    }

                    $file = $request->files->get('fil')->getPathname();

                    if (($fp = fopen($file, 'rb'))) {
                        $iline = 0;
                        $curdepth = -1;
                        while ( ! $err && ! feof($fp) && ($line = fgets($fp)) !== FALSE) {
                            $iline ++;
                            if (trim($line) == '') {
                                continue;
                            }
                            for ($depth = 0; $line != '' && $line[0] == "\t"; $depth ++) {
                                $line = substr($line, 1);
                            }
                            if ($depth > $curdepth + 1) {
                                $err = $this->app->trans("over-indent at line %line%", ['%line%' => $iline]);
                                continue;
                            }

                            $line = trim($line);

                            if ( ! $this->checkEncoding($line, 'UTF-8')) {
                                $err = $this->app->trans("bad encoding at line %line%", ['%line%' => $iline]);
                                continue;
                            }

                            $line = str_replace($cbad, $cok, ($oldline = $line));
                            if ($line != $oldline) {
                                $err = $this->app->trans("bad character at line %line%", ['%line%' => $iline]);
                                continue;
                            }

                            while ($curdepth >= $depth) {
                                $curdepth --;
                                $node = $node->parentNode;
                            }
                            $curdepth = $depth;

                            $nid = (int) ($node->getAttribute('nextid'));
                            $pid = $node->getAttribute('id');

                            $te_id = ($pid ? ($pid . '.') : 'T') . $nid;

                            $node->setAttribute('nextid', (string) ($nid + 1));

                            /** @var \DOMElement $te */
                            $te = $node->appendChild($dom->createElement('te'));
                            $te->setAttribute('id', $te_id);

                            $node = $te;

                            $tsy = explode(';', $line);
                            $nsy = 0;
                            foreach ($tsy as $syn) {
                                $lng = $request->get('piv');
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
                                            $syn = substr($syn, 0, $ob) . substr($syn, $cb + 1);
                                        } else {
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

                                /** @var \DOMElement $sy */
                                $sy = $node->appendChild($dom->createElement('sy'));
                                $sy->setAttribute('id', $te_id . '.' . $nsy);
                                $v = $syn;
                                if ($kon) {
                                    $v .= ' (' . $kon . ')';
                                }
                                $sy->setAttribute('v', $v);
                                $unicode = $this->getUnicode();
                                $sy->setAttribute('w', $unicode->remove_indexer_chars($syn));
                                if ($kon) {
                                    $sy->setAttribute('k', $unicode->remove_indexer_chars($kon));
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

                $this->dispatch(
                    ThesaurusEvents::IMPORTED,
                    new ThesaurusEvent\Imported($databox)
                );
            }
        } catch (\Exception $e) {

        }

        return $this->render('thesaurus/import.html.twig', ['err' => $err]);
    }

    private function checkEncoding($string, $string_encoding)
    {
        $fs = $string_encoding == 'UTF-8' ? 'UTF-32' : $string_encoding;
        $ts = $string_encoding == 'UTF-32' ? 'UTF-8' : $string_encoding;

        return $string === mb_convert_encoding(mb_convert_encoding($string, $fs, $ts), $ts, $fs);
    }

    public function importDialog(Request $request)
    {
        return $this->render('thesaurus/import-dialog.html.twig', [
            'dlg' => $request->get('dlg'),
            'bid' => $request->get('bid'),
            'id'  => $request->get('id'),
            'piv' => $request->get('piv'),
        ]);
    }

    public function indexThesaurus()
    {
        $sql = "SELECT"
            . "    sbas.sbas_id,"
            . "    sbasusr.bas_manage AS bas_manage,"
            . "    sbasusr.bas_modify_struct AS bas_modify_struct,"
            . "    sbasusr.bas_modif_th AS bas_edit_thesaurus"
            . " FROM"
            . "   (Users u INNER JOIN sbasusr"
            . "    ON u.id = :usr_id"
            . "    AND u.id = sbasusr.usr_id"
            . "    AND u.model_of IS NULL)"
            . " INNER JOIN"
            . "    sbas ON sbas.sbas_id = sbasusr.sbas_id"
            . " HAVING bas_edit_thesaurus > 0"
            . " ORDER BY sbas.ord";

        $bases = $languages = [];

        $appbox = $this->getApplicationBox();
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute([':usr_id' => $this->getAuthenticatedUser()->getId()]);
        $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row) {
            try {
                $this->findDataboxById($row['sbas_id'])->get_connection()->connect();
            } catch (\Exception $e) {
                continue;
            }
            $bases[$row['sbas_id']] = \phrasea::sbas_labels($row['sbas_id'], $this->app);
        }

        foreach ($this->getAvailableLocales() as $lng_code => $lng) {
            $lng_code = explode('_', $lng_code);
            $languages[$lng_code[0]] = $lng;
        }

        return $this->render('thesaurus/index.html.twig', [
            'languages' => $languages,
            'bases'     => $bases,
        ]);
    }

    public function linkFieldStep1(Request $request)
    {
        if (null === $bid = $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }

        $fullBranch = "";
        $fieldnames = [];
        try {
            $databox = $this->findDataboxById((int) $bid);
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
                if ($nodes->length == 1) {
                    /** @var \DOMElement $n */
                    for ($n = $nodes->item(0); $n && $n->nodeType == XML_ELEMENT_NODE && $n->getAttribute("id") !== ""; $n = $n->parentNode) {
                        /** @var \DOMElement $sy */
                        $sy = $xpathth->query("sy", $n)->item(0);
                        $t = $sy ? $sy->getAttribute("v") : "";
                        if (!$t) {
                            $t = "...";
                        }
                        $fullBranch = " / " . htmlspecialchars($t) . $fullBranch;
                    }
                }
                $nodes = $xpathstruct->query("/record/description/*");
                for ($i = 0; $i < $nodes->length; $i ++) {
                    /** @var \DOMElement $node */
                    $node = $nodes->item($i);
                    $fieldname = $node->nodeName;
                    $tbranch = $node->getAttribute("tbranch");
                    $ck = false;
                    if ($tbranch) {
                        // ce champ a deja un tbranch, est-ce qu'il pointe sur la branche selectionnee ?
                        $thnodes = $xpathth->query($tbranch);
                        for ($j = 0; $j < $thnodes->length; $j ++) {
                            $node = $thnodes->item($j);
                            if ($node->getAttribute("id") == $request->get('tid')) {
                                $ck = true;
                            }
                        }
                    }
                    $fieldnames[$fieldname] = $ck;
                }
            }
        } catch (\Exception $e) {

        }

        return $this->render('thesaurus/link-field-step1.html.twig', [
            'piv'        => $request->get('piv'),
            'bid'        => $request->get('bid'),
            'tid'        => $request->get('tid'),
            'fullBranch' => $fullBranch,
            'fieldnames' => $fieldnames
        ]);
    }

    public function linkFieldStep2(Request $request)
    {
        if (null === $bid = $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }

        $oldlinks = [];
        $needreindex = false;

        try {
            $databox = $this->findDataboxById((int) $bid);
            $domstruct = $databox->get_dom_structure();
            $domth = $databox->get_dom_thesaurus();

            if ($domstruct && $domth) {
                $xpathth = new \DOMXPath($domth);
                $xpathstruct = new \DOMXPath($domstruct);
                $nodes = $xpathstruct->query("/record/description/*");

                for ($i = 0; $i < $nodes->length; $i ++) {
                    /** @var \DOMElement $node */
                    $node = $nodes->item($i);
                    $fieldname = $node->nodeName;

                    $oldbranch = $node->getAttribute("tbranch");
                    $ck = false;
                    $tids = []; // les ids de branches liees e ce champ
                    if ($oldbranch) {
                        // ce champ a deja un tbranch, on balaye les branches auxquelles il est lie
                        $thnodes = $xpathth->query($oldbranch);
                        for ($j = 0; $j < $thnodes->length; $j ++) {
                            $node = $thnodes->item($j);
                            if ($node->getAttribute("id") == $request->get('tid')) {
                                // il etait deja lie a la branche selectionnee
                                $tids[$node->getAttribute("id")] = $node;
                                $ck = true;
                            } else {
                                // il etait lie e une autre branche
                                $tids[$node->getAttribute("id")] = $node;
                            }
                        }
                    }

                    if (in_array($fieldname, $request->get('field', [])) != $ck) {
                        if ($ck) {
                            unset($tids[$request->get('tid')]);
                        } else {
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

                        $oldlinks[$fieldname] = [
                            'old_branch' => $oldbranch,
                            'new_branch' => $newtbranch
                        ];

                        if ($newtbranch != "") {
                            $needreindex = true;
                        }
                    }
                }
            }
        } catch (\Exception $e) {

        }

        return $this->render('thesaurus/link-field-step2.html.twig', [
            'piv'          => $request->get('piv'),
            'bid'          => $request->get('bid'),
            'tid'          => $request->get('tid'),
            'oldlinks'     => $oldlinks,
            'need_reindex' => $needreindex,
        ]);
    }

    public function linkFieldStep3(Request $request)
    {
        if (null === $bid = $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }

        $candidates2del = [];
        $ctchanged = false;
        try {
            /** @var \databox $databox */
            $databox = $this->findDataboxById((int) $bid);
            $meta_struct = $databox->get_meta_structure();
            $domct = $databox->get_dom_cterms();
            $domst = $databox->get_dom_structure();

            if ($domct && $domst) {
                $xpathct = new \DOMXPath($domct);

                foreach ($request->get("f2unlk", []) as $f2unlk) {
                    $q = "/cterms/te[@field='" . \thesaurus::xquery_escape($f2unlk) . "']";
                    $nodes = $xpathct->query($q);
                    for ($i = 0; $i < $nodes->length; $i ++) {
                        $candidates2del[] = [
                            "field" => $f2unlk,
                            "node"  => $nodes->item($i)
                        ];
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

                foreach ($request->get("fbranch", []) as $fbranch) {
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

            if ($request->get("reindex")) {
                $this->dispatch(
                    ThesaurusEvents::FIELD_LINKED,
                    new ThesaurusEvent\FieldLinked($databox)
                );
            }
        } catch (\Exception $e) {

        }

        return $this->render('thesaurus/link-field-step3.html.twig', [
            'field2del'      => $request->get('f2unlk', []),
            'candidates2del' => $candidates2del,
            'branch2del'     => $request->get('fbranch', []),
            'ctchanged'      => $ctchanged,
            'reindexed'      => $request->get('reindex'),
        ]);
    }

    private function fixThesaurus(\DOMDocument $domct, \DOMDocument $domth, Connection $connbas)
    {
        $version = $domth->documentElement->getAttribute("version");

        if ('' === trim($version)) {
            $version = '1.0.0';
        }

        while (class_exists($cls = "patchthesaurus_" . str_replace(".", "", $version))) {

            $last_version = $version;
            $zcls = new $cls;
            $version = $zcls->patch($version, $domct, $domth, $connbas, $this->getUnicode());

            if ($version == $last_version) {
                break;
            }
        }

        return $version;
    }

    public function loadThesaurus(Request $request)
    {
        if (null === $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }

        $updated = false;
        $validThesaurus = true;
        $ctlist = [];
        $name = \phrasea::sbas_labels($request->get('bid'), $this->app);

        try {
            $databox = $this->findDataboxById((int) $request->get('bid'));
            $connbas = $databox->get_connection();

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
                if ($this->fixThesaurus($domct, $domth, $connbas) != $oldversion) {
                    $updated = true;
                    $databox->saveCterms($domct);
                    $databox->saveThesaurus($domth);
                }

                for ($ct = $domct->documentElement->firstChild; $ct; $ct = $ct->nextSibling) {
                    if ($ct->nodeName == "te") {
                        $ctlist[] = [
                            'id' => $ct->getAttribute("id"),
                            'field' => $ct->getAttribute("field")
                        ];
                    }
                }
            } else {
                $validThesaurus = false;
            }
        } catch (\Exception $e) {

        }

        return $this->render('thesaurus/load-thesaurus.html.twig', [
            'bid' => $request->get('bid'),
            'name' => $name,
            'cterms' => $ctlist,
            'valid_thesaurus' => $validThesaurus,
            'updated' => $updated
        ]);
    }

    public function newTerm(Request $request)
    {
        list($term, $context) = $this->splitTermAndContext($request->get("t"));

        $dom = $this->doSearchCandidate(
            $request->get('bid'),
            $request->get('pid'),
            $term,
            $context,
            $request->get('piv')
        );

        $xpath = new \DOMXPath($dom);

        $candidates = $xpath->query("/result/candidates_list/ct");

        $nb_candidates_ok = $nb_candidates_bad = 0;
        $flist_ok = $flist_bad = "";
        for ($i = 0; $i < $candidates->length; $i ++) {
            /** @var \DOMElement $candidate */
            $candidate = $candidates->item($i);
            if ($candidate->getAttribute("sourceok") == "1") { // && $candidates->item($i)->getAttribute("cid"))
                $flist_ok .= ( $flist_ok ? ", " : "") . $candidate->getAttribute("field");
                $nb_candidates_ok ++;
            } else {
                $flist_bad .= ( $flist_bad ? ", " : "") . $candidate->getAttribute("field");
                $nb_candidates_bad ++;
            }
        }
        $candidates_list = [];
        for ($i = 0; $i < $candidates->length; $i ++) {
            /** @var \DOMElement $candidate */
            $candidate = $candidates->item($i);
            if ($candidate->getAttribute("sourceok") == "1") {
                $candidates_list[] = array(
                    'id'    => $candidate->getAttribute("id"),
                    'field' => $candidate->getAttribute("field"),
                );
            }
        }

        return $this->render('thesaurus/new-term.html.twig', [
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
        ]);
    }

    public function properties(Request $request)
    {
        $dom = $this->getXMLTerm(
            $request->get('bid'),
            $request->get('id'),
            $request->get('typ'),
            $request->get('piv'),
            '0',
            null,
            '1',
            null
        );
        $fullpathHtml = $dom->getElementsByTagName("fullpath_html")->item(0)->firstChild->nodeValue;
        $hits = $dom->getElementsByTagName("allhits")->item(0)->firstChild->nodeValue;

        $languages = $synonyms = [];

        $sy_list = $dom->getElementsByTagName("sy_list")->item(0);
        /** @var \DOMElement $n */
        for ($n = $sy_list->firstChild; $n; $n = $n->nextSibling) {
            $synonyms[] = [
                'id' => $n->getAttribute("id"),
                'lng' => $n->getAttribute("lng"),
                't' => $n->getAttribute("t"),
                'hits' => $n->getAttribute("hits"),
            ];
        }

        foreach ($this->getAvailableLocales() as $code => $language) {
            $lng_code = explode('_', $code);
            $languages[$lng_code[0]] = $language;
        }

        //  Escape path  between span tag in fullpath_html
        preg_match_all("'(<[^><]*>)(.*?)(<[^><]*>)'", $fullpathHtml, $matches, PREG_SET_ORDER);

        $safeFullpath = '';
        foreach($matches as $match) {
            unset($match[0]);  // full match result not used
            $match[2] = htmlspecialchars($match[2]);
            $safeFullpath .= implode('', $match);
        }

        return $this->render('thesaurus/properties.html.twig', [
            'typ' => $request->get('typ'),
            'bid' => $request->get('bid'),
            'piv' => $request->get('piv'),
            'id' => $request->get('id'),
            'dlg' => $request->get('dlg'),
            'languages' => $languages,
            'fullpath' => $safeFullpath,
            'hits' => $hits,
            'synonyms' => $synonyms,
        ]);
    }

    public function thesaurus(Request $request)
    {
        $flags = $jsFlags = [];

        foreach ($this->getAvailableLocales() as $code => $language) {
            $lng_code = explode('_', $code);
            $flags[$lng_code[0]] = $language;
            $jsFlags[$lng_code[0]] = ['w' => 18, 'h' => 13];
        }
        $jsFlags = json_encode($jsFlags);

        return $this->render('thesaurus/thesaurus.html.twig', [
            'piv'     => $request->get('piv'),
            'bid'     => $request->get('bid'),
            'flags'   => $flags,
            'jsFlags' => $jsFlags,
        ]);
    }

    /**
     * Order to populate databox
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function populate(Request $request)
    {
        $options = $this->getElasticsearchOptions();

        $data['host'] = $options->getHost();
        $data['port'] = $options->getPort();
        $data['indexName'] = $options->getIndexName();
        $data['databoxIds'] = [$request->get('databox_id')];

        $this->getDispatcher()->dispatch(WorkerEvents::POPULATE_INDEX, new PopulateIndexEvent($data));

        return $this->app->json(["status" => "success"]);
    }

    /**
     * @param Request $request
     * @return Response
     *
     * rétablit un terme candidat rejeté (R) et tous ses enfants
     * comme candidat (C)
     * appelé par le menu contextuel sur candidat / kcterm_accept
     */
    public function acceptXml(Request $request)
    {
        $ret = new \DOMDocument("1.0", "UTF-8");
        $ret->preserveWhiteSpace = false;

        $root = $ret->appendChild($ret->createElement("result"));
        $root->appendChild($ret->createCDATASection(var_export([
            "bid"   => $request->get('bid'),
            "id"    => $request->get('id'),
            "piv"   => $request->get('piv'),
        ], true)));

        $refresh_list = $root->appendChild($ret->createElement("refresh_list"));

        if (null === $bid = $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }

        try {
            /** @var \databox $databox */
            $databox = $this->findDataboxById((int) $bid);
            $databox->get_connection()->connect();

            $dom = $databox->get_dom_cterms();
            $xpath = new \DOMXPath($dom);
            $q = "/cterms//te[@id='" . $request->get('id') . "']";

            /** @var \DOMElement $te */
            $te = $xpath->query($q)->item(0);
            if ($te) {
                $this->acceptBranch($bid, $te);

                $databox->saveCterms($dom);

                /** @var \DOMElement $r */
                $r = $refresh_list->appendChild($ret->createElement("refresh"));
                /** @var \DOMElement $p */
                $p = $te->parentNode;
                $r->setAttribute("id", $p->getAttribute("id"));
                $r->setAttribute("type", "CT");
            }
        } catch (\Exception $e) {

        }

        return new Response($ret->saveXML(), 200, ['Content-Type' => 'text/xml']);
    }

    /**
     * transforme (R)ejected en (C)andidate sur un node et tous ses enfants
     * @param             $sbas_id
     * @param \DOMElement $node
     */
    private function acceptBranch($sbas_id, \DOMElement $node)
    {
        if (strlen($oldid = $node->getAttribute("id")) > 1) {
            $node->setAttribute("id", $newid = ("C" . substr($oldid, 1)));
        }
        for ($n = $node->firstChild; $n; $n = $n->nextSibling) {
            if ($n->nodeType == XML_ELEMENT_NODE) {
                $this->acceptBranch($sbas_id, $n);
            }
        }
    }



    public function acceptCandidatesXml(Request $request)
    {
        $ret = new \DOMDocument("1.0", "UTF-8");
        $ret->preserveWhiteSpace = false;

        $root = $ret->appendChild($ret->createElement("result"));
        $root->appendChild($ret->createCDATASection(var_export([
            'bid'   => $request->get('bid'),
            'piv'   => $request->get('piv'),
            'cid'   => $request->get('cid'),
            'pid'   => $request->get('pid'),
            'typ'   => $request->get('typ'),
        ], true)));

        $refresh_list = $root->appendChild($ret->createElement("refresh_list"));

        if (null === $bid = $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }

        try {
            $databox = $this->findDataboxById((int) $bid);

            $domct = $databox->get_dom_cterms();
            $domth = $databox->get_dom_thesaurus();

            if ($domct !== false && $domth !== false) {
                $xpathth = new \DOMXPath($domth);

                if ($request->get('pid') == "T") {
                    $q = "/thesaurus";
                } else {
                    $q = "/thesaurus//te[@id='" . $request->get('pid') . "']";
                }

                /** @var \DOMElement $parentnode */
                $parentnode = $xpathth->query($q)->item(0);

                if ($parentnode) {
                    $xpathct = new \DOMXPath($domct);
                    $ctchanged = false;

                    $icid = 0;
                    foreach ($request->get("cid") as $cid) {
                        $q = "//te[@id='" . $cid . "']";

                        /** @var \DOMElement $ct */
                        $ct = $xpathct->query($q)->item(0);

                        if ($ct) {
                            if ($request->get("typ") == "TS") {
                                // importer tt la branche candidate comme nouveau ts
                                $nid = $parentnode->getAttribute("nextid");
                                $parentnode->setAttribute("nextid", (int) $nid + 1);

                                /** @var \DOMElement $te */
                                $te = $domth->importNode($ct, true);
                                $chgids = [];

                                if (($pid = $parentnode->getAttribute("id")) == "") {
                                    $pid = "T" . $nid;
                                } else {
                                    $pid .= "." . $nid;
                                }

                                $this->renumerate($te, $pid, $chgids);

                                /** @var \DOMElement $new_te */
                                $new_te = $parentnode->appendChild($te);

                                $databox->saveThesaurus($domth);

                                $this->dispatch(
                                    ThesaurusEvents::CANDIDATE_ACCEPTED_AS_CONCEPT,
                                    new ThesaurusEvent\CandidateAccepted($databox, $new_te->getAttribute('id'))
                                );

                                /** @var \DOMElement $r */
                                if ($icid == 0) { // on update la destination une seule fois
                                    $r = $refresh_list->appendChild($ret->createElement("refresh"));
                                    $r->setAttribute("id", $parentnode->getAttribute("id"));
                                    $r->setAttribute("type", "TH");
                                }

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

                                    /** @var \DOMElement $te */
                                    $te = $domth->importNode($ct2, true);
                                    $chgids = [];
                                    if (($pid = $parentnode->getAttribute("id")) == "") {
                                        $pid = "T" . $nid;
                                    } else {
                                        $pid .= "." . $nid;
                                    }

                                    $this->renumerate($te, $pid, $chgids);
                                    /** @var \DOMElement $new_te */
                                    $new_te = $parentnode->appendChild($te);

                                    $databox->saveThesaurus($domth);

                                    $this->dispatch(
                                        ThesaurusEvents::CANDIDATE_ACCEPTED_AS_SYNONYM,
                                        new ThesaurusEvent\CandidateAccepted($databox, $new_te->getAttribute('id'))
                                    );
                                }
                                /** @var \DOMElement $r */
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
                }
            }
        } catch (\Exception $e) {

        }

        return new Response($ret->saveXML(), 200, ['Content-Type' => 'text/xml']);
    }

    private function renumerate(\DOMElement $node, $id, &$chgids, $depth = 0)
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

    public function changeSynonymLanguageXml(Request $request)
    {
        $ret = new \DOMDocument("1.0", "UTF-8");
        $ret->preserveWhiteSpace = false;

        $root = $ret->appendChild($ret->createElement("result"));
        $root->appendChild($ret->createCDATASection(var_export([
            'bid'    => $request->get('bid'),
            'piv'    => $request->get('piv'),
            'newlng' => $request->get('cid'),
            'id'     => $request->get('id'),
            'typ'    => $request->get('typ'),
        ], true)));

        if (null === $bid = $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }

        try {
            $databox = $this->findDataboxById((int) $bid);

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

                /** @var \DOMElement $sy0 */
                $sy0 = $xpath->query($q)->item(0);
                if ($sy0) {
                    $sy0->setAttribute("lng", $request->get('newlng'));

                    if ($xqroot == "cterms") {
                        $databox->saveCterms($dom);
                    } elseif ($xqroot == "thesaurus") {
                        $databox->saveThesaurus($dom);

                        $this->dispatch(
                            ThesaurusEvents::SYNONYM_LNG_CHANGED,
                            new ThesaurusEvent\SynonymLngChanged($databox, $sy0->getAttribute('id'))
                        );
                    }

                    $ret = $this->getXMLTerm(
                        $bid,
                        $sy0->parentNode->getAttribute("id"),
                        $request->get('typ'),
                        $request->get('piv'),
                        null,
                        $request->get('id'),
                        '1',
                        null
                    );

                    $root = $ret->getElementsByTagName("result")->item(0);
                    $refresh_list = $root->appendChild($ret->createElement("refresh_list"));

                    /** @var \DOMElement $r */
                    $r = $refresh_list->appendChild($ret->createElement("refresh"));
                    $r->setAttribute("id", $sy0->parentNode->parentNode->getAttribute("id"));
                    $r->setAttribute("type", $request->get('typ'));
                }
            }
        } catch (\Exception $e) {

        }

        return new Response($ret->saveXML(), 200, ['Content-Type' => 'text/xml']);
    }

    public function changeSynonymPositionXml(Request $request)
    {
        $ret = new \DOMDocument("1.0", "UTF-8");
        $ret->preserveWhiteSpace = false;

        $root = $ret->appendChild($ret->createElement("result"));
        $root->appendChild($ret->createCDATASection(var_export([
            'bid'   => $request->get('bid'),
            'piv'   => $request->get('piv'),
            'dir'   => $request->get('dir'),
            'id'    => $request->get('id'),
            'typ'   => $request->get('typ'),
        ], true)));

        if (null === $bid = $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }

        $root->appendChild($ret->createElement("refresh_list"));

        try {
            $databox = $this->findDataboxById((int) $bid);

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

                /** @var \DOMElement $sy0 */
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

                        $this->dispatch(
                            ThesaurusEvents::SYNONYM_POSITION_CHANGED,
                            new ThesaurusEvent\SynonymPositionChanged($databox, $sy0->getAttribute('id'))
                        );

                    }

                    $ret = $this->getXMLTerm(
                        $bid,
                        $sy0->parentNode->getAttribute("id"),
                        $request->get('typ'),
                        $request->get('piv'),
                        null,
                        $request->get('id'),
                        '1',
                        null
                    );

                    $root = $ret->getElementsByTagName("result")->item(0);
                    $refresh_list = $root->appendChild($ret->createElement("refresh_list"));

                    /** @var \DOMElement $r */
                    $r = $refresh_list->appendChild($ret->createElement("refresh"));
                    $r->setAttribute("id", $sy0->parentNode->parentNode->getAttribute("id"));
                    $r->setAttribute("type", $request->get('typ'));
                }
            }
        } catch (\Exception $e) {

        }

        return new Response($ret->saveXML(), 200, ['Content-Type' => 'text/xml']);
    }

    /**
     * supprime un synonyme
     * appelé par le menu contextuel sur un synonyme, option "delete_sy"
     * dans le dialog des properties sur un terme (properties.html.twig)
     *
     * @param Request $request
     * @return Response
     */
    public function removeSynonymXml(Request $request)
    {
        $ret = new \DOMDocument("1.0", "UTF-8");
        $ret->preserveWhiteSpace = false;

        $root = $ret->appendChild($ret->createElement("result"));
        $root->appendChild($ret->createCDATASection(var_export([
            'bid'   => $request->get('bid'),
            'id'    => $request->get('id'),
            'piv'   => $request->get('piv'),
            'typ'   => $request->get('typ'),
        ], true)));

        $refresh_list = $root->appendChild($ret->createElement("refresh_list"));

        if (null === $bid = $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }

        try {
            $databox = $this->findDataboxById((int) $bid);
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

                /** @var \DOMElement $sy0 */
                $sy0 = $xpath->query($q)->item(0);
                if ($sy0) {
                    $xpathct = new \DOMXPath($domct);

                    // on cherche la branche 'deleted' dans les cterms
                    $nodes = $xpathct->query("/cterms/te[@delbranch='1']");
                    if ( ! $nodes || ($nodes->length == 0)) {
                        // 'deleted' n'existe pas, on la cree
                        $id = $domct->documentElement->getAttribute("nextid");
                        $domct->documentElement->setAttribute("nextid", (int) ($id) + 1);
                        /** @var \DOMElement $del */
                        $del = $domct->documentElement->appendChild($domct->createElement("te"));
                        $del->setAttribute("id", "C" . $id);
                        $del->setAttribute("field", $this->app->trans('thesaurus:: corbeille'));
                        $del->setAttribute("nextid", "0");
                        $del->setAttribute("delbranch", "1");

                        /** @var \DOMElement $r */
                        $r = $refresh_list->appendChild($ret->createElement("refresh"));
                        $r->setAttribute("id", "C");
                        $r->setAttribute("type", "CT");
                    } else {
                        // 'deleted' existe
                        /** @var \DOMElement $del */
                        $del = $nodes->item(0);
                        /** @var \DOMElement $r */
                        $r = $refresh_list->appendChild($ret->createElement("refresh"));
                        $r->setAttribute("id", $del->getAttribute("id"));
                        $r->setAttribute("type", "CT");
                    }

                    // on cree une branche 'te'
                    $refrid = $sy0->parentNode->parentNode->getAttribute("id");
                    $delid = $del->getAttribute("id");
                    $delteid = (int) ($del->getAttribute("nextid"));

                    $del->setAttribute("nextid", $delteid + 1);
                    /** @var \DOMElement $delte */
                    $delte = $del->appendChild($domct->createElement("te"));
                    $delte->setAttribute("id", $delid . "." . $delteid);
                    $delte->setAttribute("nextid", "1");

                    /** @var \DOMElement $delsy */
                    $delsy = $delte->appendChild($domct->createElement("sy"));
                    $delsy->setAttribute("id", $newid = ($delid . "." . $delteid . ".0"));
                    $delsy->setAttribute("lng", $sy0->getAttribute("lng"));
                    $delsy->setAttribute("v", $sy0->getAttribute("v"));
                    $delsy->setAttribute("w", $sy0->getAttribute("w"));

                    if ($sy0->hasAttribute("k")) {
                        $delsy->setAttribute("k", $sy0->getAttribute("k"));
                    }

                    $te = $sy0->parentNode;
                    $te->removeChild($sy0);

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
                        $databox->saveThesaurus($dom);

                        $this->dispatch(
                            ThesaurusEvents::SYNONYM_TRASHED,
                            new ThesaurusEvent\ItemTrashed($databox, $te->getAttribute('id'), $delsy->getAttribute('id'))
                        );

                        $r = $refresh_list->appendChild($ret->createElement("refresh"));
                        $r->setAttribute("type", "TH");
                        if ($refrid) {
                            $r->setAttribute("id", $refrid);
                        } else {
                            $r->setAttribute("id", "T");
                        }
                    }

                    $ret2 = $this->getXMLTerm(
                        $request->get('bid'),
                        $te->getAttribute("id"),
                        $request->get('typ'),
                        $request->get('piv'),
                        null,
                        null,
                        '1',
                        null
                    );

                    if ($sl = $ret2->getElementsByTagName("sy_list")->item(0)) {
                        $ret->importNode($sl, true);
                    }
                }
            }
        } catch (\Exception $e) {

        }

        return new Response($ret->saveXML(), 200, ['Content-Type' => 'text/xml']);
    }

    /**
     * Supprime (déplace dans cterms/trash) toute une branche TE avec les SY et tous les TE enfants
     * appelé par le menu contextuel sur terme, option "kterm_delete"
     * @param Request $request
     * @return Response
     */
    public function removeSpecificTermXml(Request $request)
    {
        $ret = new \DOMDocument("1.0", "UTF-8");
        $ret->preserveWhiteSpace = false;

        $root = $ret->appendChild($ret->createElement("result"));
        $root->appendChild($ret->createCDATASection(var_export([
            'bid'   => $request->get('bid'),
            'id'    => $request->get('id'),
            'piv'   => $request->get('piv'),
        ], true)));

        $refresh_list = $root->appendChild($ret->createElement("refresh_list"));

        if (null === $bid = $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }

        try {
            /** @var \databox $databox */
            $databox = $this->findDataboxById((int) $bid);
            $domth = $databox->get_dom_thesaurus();
            $domct = $databox->get_dom_cterms();

            if ($domth && $domct) {
                $xpathth = new \DOMXPath($domth);
                $xpathct = new \DOMXPath($domct);
                if ($request->get('id') !== "") {    // secu pour pas exploser le thesaurus
                    $q = "/thesaurus//te[@id='" . $request->get('id') . "']";

                    $thnode = $xpathth->query($q)->item(0);
                    if ($thnode) {
                        /** @var \DOMElement $thnode_parent */
                        $thnode_parent = $thnode->parentNode;
                        $chgids = [];
                        $pid = $thnode_parent->getAttribute("id");
                        if ($pid === "") {
                            $pid = "T";
                        }

                        $nodes = $xpathct->query("/cterms/te[@delbranch='1']");
                        if ( ! $nodes || ($nodes->length == 0)) {
                            $id = $domct->documentElement->getAttribute("nextid");

                            $domct->documentElement->setAttribute("nextid", (int) ($id) + 1);
                            /** @var \DOMElement $ct */
                            $ct = $domct->documentElement->appendChild($domct->createElement("te"));
                            $ct->setAttribute("id", "C" . $id);
                            $ct->setAttribute("field", $this->app->trans('thesaurus:: corbeille'));
                            $ct->setAttribute("nextid", "0");
                            $ct->setAttribute("delbranch", "1");

                            /** @var \DOMElement $r */
                            $r = $refresh_list->appendChild($ret->createElement("refresh"));
                            $r->setAttribute("id", "C");
                            $r->setAttribute("type", "CT");
                        } else {
                            /** @var \DOMElement $ct */
                            $ct = $nodes->item(0);
                            /** @var \DOMElement $r */
                            $r = $refresh_list->appendChild($ret->createElement("refresh"));
                            $r->setAttribute("id", $ct->getAttribute("id"));
                            $r->setAttribute("type", "CT");
                        }
                        $teid = (int) ($ct->getAttribute("nextid"));
                        $ct->setAttribute("nextid", $teid + 1);

                        /** @var \DOMElement $newte */
                        $newte = $ct->appendChild($domct->importNode($thnode, true));

                        $this->renumerate($newte, $ct->getAttribute("id") . "." . $teid, $chgids);

                        $databox->saveCterms($domct);

                        $this->dispatch(
                            ThesaurusEvents::CONCEPT_TRASHED,
                            new ThesaurusEvent\ItemTrashed($databox, $thnode_parent->getAttribute('id'), $newte->getAttribute('id'))
                        );

                        $thnode_parent->removeChild($thnode);

                        $databox->saveThesaurus($domth);

                        $r = $refresh_list->appendChild($ret->createElement("refresh"));
                        $r->setAttribute("id", $pid);
                        $r->setAttribute("type", "TH");
                    }
                }
            }
        } catch (\Exception $e) {

        }

        return new Response($ret->saveXML(), 200, ['Content-Type' => 'text/xml']);
    }


    public function getSynonymXml(Request $request)
    {
        $ret = new \DOMDocument("1.0", "UTF-8");
        $ret->preserveWhiteSpace = false;

        $root = $ret->appendChild($ret->createElement("result"));
        $root->appendChild($ret->createCDATASection(var_export([
            'bid'   => $request->get('bid'),
            'id'    => $request->get('id'),
            'typ'   => $request->get('typ'),
        ], true)));

        if (null === $bid = $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }

        try {
            $databox = $this->findDataboxById((int) $bid);

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

                $nodes = $xpath->query($q);
                if ($nodes->length > 0) {
                    /** @var \DOMElement $node */
                    $node = $nodes->item(0);

                    $t = $node->getAttribute("v");
                    if (($k = $node->getAttribute("k"))) {
                        $t .= " (" . $k . ")";
                    }

                    $fullpath_html = "<span class='path_separator'> / </span><span class='main_term'>" . $t . "</span>";
                    $fullpath = " / " . $t;

                    /** @var \DOMElement $sy */
                    $sy = $root->appendchild($ret->createElement("sy"));
                    $sy->setAttribute("t", $t);
                    foreach (["v", "w", "k", "lng", "id"] as $a) {
                        if ($node->hasAttribute($a)) {
                            $sy->setAttribute($a, $node->getAttribute($a));
                        }
                    }

                    for ($depth = -1, $n = $node->parentNode->parentNode; $n; $n = $n->parentNode, $depth -- ) {
                        if ($n->nodeName == "te") {
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

                    $n = $root->appendchild($ret->createElement("hits"));
                    $n->appendChild($ret->createTextNode(''));
                }
            }
        } catch (\Exception $e) {

        }

        return new Response($ret->saveXML(), 200, ['Content-Type' => 'text/xml']);
    }

    public function getTermXml(Request $request)
    {
        return new Response(
            $this->getXMLTerm(
                $request->get('bid'),
                $request->get('id'),
                $request->get('typ'),
                $request->get('piv'),
                $request->get('sortsy'),
                $request->get('sel'),
                $request->get('nots'),
                $request->get('acf')
            )->saveXML(),
            200,
            ['Content-Type' => 'text/xml']
        );
    }

    /**
     * @param $bid
     * @param $id
     * @param $typ
     * @param $piv
     * @param $sortsy
     * @param $sel
     * @param $nots
     * @param $acf
     * @return \DOMDocument
     * @internal param Application $app
     */
    private function getXMLTerm($bid, $id, $typ, $piv, $sortsy, $sel, $nots, $acf)
    {
        $ret = new \DOMDocument("1.0", "UTF-8");
        $ret->preserveWhiteSpace = false;

        /** @var \DOMElement $root */
        $root = $ret->appendChild($ret->createElement("result"));
        $root->appendChild($ret->createCDATASection(var_export([
            "bid"    => $bid,
            "id"     => $id,
            "typ"    => $typ,
            "piv"    => $piv,
            "sortsy" => $sortsy,
            "sel"    => $sel,
            "nots"   => $nots,
            "acf"    => $acf,
        ], true)));

        /** @var \DOMElement $cfield */
        $cfield = $root->appendChild($ret->createElement("cfield"));
        /** @var \DOMElement $ts_list */
        $ts_list = $root->appendChild($ret->createElement("ts_list"));
        $sy_list = $root->appendChild($ret->createElement("sy_list"));

        if ($bid !== null) {
            try {
                /** @var \databox $databox */
                $databox = $this->findDataboxById((int) $bid);

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

                    $nodes = $xpath->query($q);
                    $root->setAttribute('found', '' . $nodes->length);
                    if ($nodes->length > 0) {
                        $nts = 0;
                        $tts = [];
                        // on dresse la liste des termes specifiques avec comme cle le synonyme
                        // dans la langue pivot
                        for ($n = $nodes->item(0)->firstChild; $n; $n = $n->nextSibling) {
                            /** @var \DOMElement $n */
                            if ($n->nodeName == "te") {
                                $nts ++;
                                if (! $nots) {
                                    if ($typ == "CT" && $id == "C") {
                                        $realksy = $allsy = $n->getAttribute("field");
                                    } else {
                                        $allsy = "";
                                        $firstksy = null;
                                        $realksy = null;
                                        // on liste les sy pour fabriquer la cle
                                        for ($n2 = $n->firstChild; $n2; $n2 = $n2->nextSibling) {
                                            if ($n2->nodeName == "sy") {
                                                $lng = $n2->getAttribute("lng");
                                                $t = $n2->getAttribute("v");
                                                $ksy = $n2->getAttribute("w");
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
                                        $tts[$realksy . "_" . $uniq] = [
                                            "id"     => $n->getAttribute("id"),
                                            "allsy"  => $allsy,
                                            "nchild" => $xpath->query("te", $n)->length
                                        ];
                                    } else {
                                        $tts[] = [
                                            "id"     => $n->getAttribute("id"),
                                            "allsy"  => $allsy,
                                            "nchild" => $xpath->query("te", $n)->length
                                        ];
                                    }
                                }
                            } elseif ($n->nodeName == "sy") {
                                /** @var \DOMElement $sy */
                                $sy = $sy_list->appendChild($ret->createElement("sy"));

                                $sy->setAttribute("id", $n->getAttribute("id"));
                                $sy->setAttribute("v", $t = $n->getAttribute("v"));
                                $sy->setAttribute("w", $n->getAttribute("w"));
                                $sy->setAttribute("hits", '');
                                $sy->setAttribute("lng", $lng = $n->getAttribute("lng"));
                                if (($k = $n->getAttribute("k"))) {
                                    $sy->setAttribute("k", $k);
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
                        foreach ($tts as $ts) {
                            /** @var \DOMElement $newts */
                            $newts = $ts_list->appendChild($ret->createElement("ts"));
                            $newts->setAttribute("id", $ts["id"]);
                            $newts->setAttribute("nts", $ts["nchild"]);
                            $newts->appendChild($ret->createTextNode($ts["allsy"]));
                        }

                        $fullpath_html = $fullpath = "";
                        for ($depth = 0, $n = $nodes->item(0); $n; $n = $n->parentNode, $depth-- ) {
                            /** @var \DOMElement $n */
                            if ($n->nodeName == "te") {
                                if ($typ == "CT" && ($fld = $n->getAttribute("field")) != "") {
                                    // la source provient des candidats pour ce champ

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
                                        $t = $n2->getAttribute("v");
                                        if (! $firstsy) {
                                            $firstsy = $t;
                                        }
                                        if ($n2->getAttribute("lng") == $piv) {
                                            $goodsy = $t;
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

                        $n = $root->appendchild($ret->createElement("hits"));
                        $n->appendChild($ret->createTextNode(''));

                        $n = $root->appendchild($ret->createElement("allhits"));
                        $n->appendChild($ret->createTextNode(''));
                    }
                }
            } catch (\Exception $e) {

            }
        }

        return $ret;
    }

    public function killTermXml(Request $request)
    {
        $ret = new \DOMDocument("1.0", "UTF-8");
        $ret->preserveWhiteSpace = false;

        $root = $ret->appendChild($ret->createElement("result"));
        $root->appendChild($ret->createCDATASection(var_export([
            'bid'   => $request->get('bid'),
            'id'    => $request->get('id'),
            'piv'   => $request->get('piv'),
            'typ'   => $request->get('typ'),
        ], true)));

        $refresh_list = $root->appendChild($ret->createElement("refresh_list"));

        if (null === $bid = $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }

        try {
            $databox = $this->findDataboxById((int) $bid);

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

                /** @var \DOMElement $te */
                $te = $xpath->query($q)->item(0);
                if ($te) {
                    $refrid = $te->parentNode->getAttribute("id");

                    $sy_evt_parm = $this->buildSynonymsFromTe($te); // to pass as event parameter

                    $parent = $te->parentNode;
                    $parent->removeChild($te);

                    if ($request->get('typ') == "CT") {
                        $databox->saveCterms($dom);

                        /** @var \DOMElement $r */
                        $r = $refresh_list->appendChild($ret->createElement("refresh"));
                        $r->setAttribute("type", "CT");
                        $r->setAttribute("id", $refrid);

                    } else {

                        $databox->saveThesaurus($dom);

                        $this->dispatch(
                            ThesaurusEvents::CONCEPT_DELETED,
                            new ThesaurusEvent\ConceptDeleted($databox, $refrid, $sy_evt_parm)
                        );

                        /** @var \DOMElement $r */
                        $r = $refresh_list->appendChild($ret->createElement("refresh"));
                        $r->setAttribute("type", "TH");
                        if ($refrid) {
                            $r->setAttribute("id", $refrid);
                        } else {
                            $r->setAttribute("id", "T");
                        }
                    }

                    $ret2 = $this->getXMLTerm(
                        $bid,
                        $parent->getAttribute("id"),
                        $request->get('typ'),
                        $request->get('piv'),
                        null,
                        null,
                        '1',
                        null
                    );

                    if ($sl = $ret2->getElementsByTagName("sy_list")->item(0)) {
                        $ret->importNode($sl, true);
                    }
                }
            }
        } catch (\Exception $e) {

        }

        return new Response($ret->saveXML(), 200, ['Content-Type' => 'text/xml']);
    }

    public function newSynonymXml(Request $request)
    {
        $ret = new \DOMDocument("1.0", "UTF-8");
        $ret->preserveWhiteSpace = false;

        $root = $ret->appendChild($ret->createElement("result"));
        $root->appendChild($ret->createCDATASection(var_export([
            "bid"   => $request->get('bid'),
            "pid"   => $request->get('pid'),
            "piv"   => $request->get('piv'),
            "sylng" => $request->get('sylng'),
            "t"     => $request->get('t'),
            "k"     => $request->get('k'),
        ], true)));

        $refresh_list = $root->appendChild($ret->createElement("refresh_list"));

        if (null === $bid = $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }

        try {
            $databox = $this->findDataboxById((int) $bid);
            $domth = $databox->get_dom_thesaurus();

            if ($domth) {
                $xpathth = new \DOMXPath($domth);
                if ($bid === "T") {
                    $q = "/thesaurus";
                } else {
                    $q = "/thesaurus//te[@id='" . $request->get('pid') . "']";
                }

                /** @var \DOMElement $te */
                $te = $xpathth->query($q)->item(0);
                if ($te) {
                    $tenextid = (int) $te->getAttribute("nextid");
                    $te->setAttribute("nextid", $tenextid + 1);

                    /** @var \DOMElement $sy */
                    $sy = $te->appendChild($domth->createElement("sy"));
                    $syid = $te->getAttribute("id") . "." . $tenextid;
                    $sy->setAttribute("id", $syid);

                    if ($request->get('sylng')) {
                        $sy->setAttribute("lng", $request->get('sylng'));
                    } else {
                        $sy->setAttribute("lng", "");
                    }

                    list($v, $k) = $this->splitTermAndContext($request->get('t'));

                    $k = trim($k) . trim($request->get('k'));
                    $unicode = $this->getUnicode();
                    $w = $unicode->remove_indexer_chars($v);

                    if ($k) {
                        $v .= " (" . $k . ")";
                    }

                    $k = $unicode->remove_indexer_chars($k);

                    $sy->setAttribute("v", $v);
                    $sy->setAttribute("w", $w);

                    if ($k) {
                        $sy->setAttribute("k", $k);
                    }

                    $databox->saveThesaurus($domth);

                    $this->dispatch(
                        ThesaurusEvents::SYNONYM_ADDED,
                        new ThesaurusEvent\ItemAdded($databox, $syid)
                    );

                    /** @var \DOMElement $r */
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

        return new Response($ret->saveXML(), 200, ['Content-Type' => 'text/xml']);
    }

    public function newSpecificTermXml(Request $request)
    {
        $ret = new \DOMDocument("1.0", "UTF-8");
        $ret->preserveWhiteSpace = false;

        $root = $ret->appendChild($ret->createElement("result"));
        $root->appendChild($ret->createCDATASection(var_export([
            "bid"     => $request->get('bid'),
            "pid"     => $request->get('pid'),
            "t"       => $request->get('t'),
            "k"       => $request->get('k'),
            "sylng"   => $request->get('sylng'),
            "reindex" => $request->get('reindex'),
        ], true)));

        $refresh_list = $root->appendChild($ret->createElement("refresh_list"));

        if (null === $bid = $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }

        try {
            $databox = $this->findDataboxById((int) $bid);
            $domth = $databox->get_dom_thesaurus();

            if ($domth) {
                $xpathth = new \DOMXPath($domth);

                if ($request->get('pid') === "T") {
                    $q = "/thesaurus";
                } else {
                    $q = "/thesaurus//te[@id='" . $request->get('pid') . "']";
                }

                /** @var \DOMElement $parentnode */
                $parentnode = $xpathth->query($q)->item(0);
                if ($parentnode) {
                    $nid = $parentnode->getAttribute("nextid");
                    $parentnode->setAttribute("nextid", (int) $nid + 1);
                    /** @var \DOMElement $te */
                    $te = $parentnode->appendChild($domth->createElement("te"));

                    if ($request->get('pid') === "T") {
                        $te->setAttribute("id", $teid = "T" . ($nid));
                    } else {
                        $te->setAttribute("id", $teid = ($request->get('pid') . "." . $nid));
                    }

                    $te->setAttribute("nextid", "1");
                    /** @var \DOMElement $sy */
                    $sy = $te->appendChild($domth->createElement("sy"));
                    $syid = $teid . ".0";
                    $sy->setAttribute("id", $syid);

                    if ($request->get('sylng')) {
                        $sy->setAttribute("lng", $request->get('sylng'));
                    } else {
                        $sy->setAttribute("lng", "");
                    }

                    list($v, $k) = $this->splitTermAndContext($request->get('t'));
                    $k = trim($k) . trim($request->get('k'));

                    $unicode = $this->getUnicode();
                    $w = $unicode->remove_indexer_chars($v);

                    if ($k) {
                        $v .= " (" . $k . ")";
                    }

                    $k = $unicode->remove_indexer_chars($k);

                    $sy->setAttribute("v", $v);
                    $sy->setAttribute("w", $w);

                    if ($k) {
                        $sy->setAttribute("k", $k);
                    }

                    $databox->saveThesaurus($domth);

                    $this->dispatch(
                        ThesaurusEvents::CONCEPT_ADDED,
                        new ThesaurusEvent\ItemAdded($databox, $syid)
                    );

                    /** @var \DOMElement $r */
                    $r = $refresh_list->appendChild($ret->createElement("refresh"));
                    $r->setAttribute("type", "TH");
                    $r->setAttribute("id", $request->get('pid'));
                }
            }
        } catch (\Exception $e) {

        }

        return new Response($ret->saveXML(), 200, ['Content-Type' => 'text/xml']);
    }

    public function openBranchesXml(Request $request)
    {
        $ret = new \DOMDocument("1.0", "UTF-8");
        $ret->preserveWhiteSpace = false;

        $root = $ret->appendChild($ret->createElement("result"));
        $root->appendChild($ret->createCDATASection(var_export([
            "bid"    => $request->get('bid'),
            "id"     => $request->get('id'),
            "typ"    => $request->get('typ'),
            "t"      => $request->get('t'),
            "method" => $request->get('method'),
        ], true)));

        /** @var \DOMElement $html */
        $html = $root->appendChild($ret->createElement("html"));

        if (null === $bid = $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }

        try {
            $databox = $this->findDataboxById((int) $bid);
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

                /** @var \DOMElement $znode */
                $znode = $xpath->query($q)->item(0);
                if ($znode) {
                    $q2 = "//sy";
                    if ($request->get('t')) {
                        $t = $this->splitTermAndContext($request->get('t'));
                        $unicode = $this->getUnicode();
                        switch ($request->get('method')) {
                            case "begins":
                                $q2 = "starts-with(@w, '" . \thesaurus::xquery_escape(
                                        $unicode->remove_indexer_chars($t[0])) . "')";
                                if ($t[1]) {
                                    $q2 .= " and starts-with(@k, '" . \thesaurus::xquery_escape(
                                            $unicode->remove_indexer_chars($t[1])) . "')";
                                }
                                break;
                            case "contains":
                                $q2 = "contains(@w, '" . \thesaurus::xquery_escape($unicode->remove_indexer_chars($t[0])) . "')";
                                if ($t[1]) {
                                    $q2 .= " and contains(@k, '" . \thesaurus::xquery_escape(
                                            $unicode->remove_indexer_chars($t[1])) . "')";
                                }
                                break;
                            case "equal":
                            default:
                                $q2 = "(@w='" . \thesaurus::xquery_escape($unicode->remove_indexer_chars($t[0])) . "')";
                                if ($t[1]) {
                                    $q2 .= " and (@k='" . \thesaurus::xquery_escape(
                                            $unicode->remove_indexer_chars($t[1])) . "')";
                                }
                                break;
                        }
                        $q2 = "//sy[" . $q2 . "]";
                    }

                    $nodes = $xpath->query($q2, $znode);
                    for ($i = 0; $i < $nodes->length; $i ++) {
                        for ($n = $nodes->item($i)->parentNode; $n && $n->nodeType == XML_ELEMENT_NODE && $n->nodeName == "te"; $n = $n->parentNode) {
                            /** @var \DOMElement $n */
                            $n->setAttribute("open", "1");
                        }
                    }

                    $this->getBranchHTML($request->get('typ'), $znode, $ret, $html, 0);
                }
            }
        } catch (\Exception $e) {

        }

        return new Response($ret->saveXML(), 200, ['Content-Type' => 'text/xml']);
    }

    public function openBranchesJson(Request $request)
    {
        $dom_ret = new \DOMDocument("1.0", "UTF-8");
        $dom_ret->preserveWhiteSpace = false;
        $dom_ret->formatOutput = true;
        $dom_html = $dom_ret->appendChild($dom_ret->createElement("html"));

        $ret = array(
            "parms" => array(
                "bid"    => $request->get('bid'),
                "id"     => $request->get('id'),
                "typ"    => $request->get('typ'),
                "t"      => $request->get('t'),
                "method" => $request->get('method'),
                "debug"  => $request->get('debug')
            ),
            "result" => array(
                "html" => ""
            )
        );

        if (null === $bid = $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }

        try {
            $databox = $this->findDataboxById((int) $bid);
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

                /** @var \DOMElement $znode */
                $znode = $xpath->query($q)->item(0);
                if ($znode) {
                    $q2 = "//sy";
                    if ($request->get('t')) {
                        $t = $this->splitTermAndContext($request->get('t'));
                        $unicode = $this->getUnicode();
                        switch ($request->get('method')) {
                            case "begins":
                                $q2 = "starts-with(@w, '" . \thesaurus::xquery_escape(
                                        $unicode->remove_indexer_chars($t[0])) . "')";
                                if ($t[1]) {
                                    $q2 .= " and starts-with(@k, '" . \thesaurus::xquery_escape(
                                            $unicode->remove_indexer_chars($t[1])) . "')";
                                }
                                break;
                            case "contains":
                                $q2 = "contains(@w, '" . \thesaurus::xquery_escape($unicode->remove_indexer_chars($t[0])) . "')";
                                if ($t[1]) {
                                    $q2 .= " and contains(@k, '" . \thesaurus::xquery_escape(
                                            $unicode->remove_indexer_chars($t[1])) . "')";
                                }
                                break;
                            case "equal":
                            default:
                                $q2 = "(@w='" . \thesaurus::xquery_escape($unicode->remove_indexer_chars($t[0])) . "')";
                                if ($t[1]) {
                                    $q2 .= " and (@k='" . \thesaurus::xquery_escape(
                                            $unicode->remove_indexer_chars($t[1])) . "')";
                                }
                                break;
                        }
                        $q2 = "//sy[" . $q2 . "]";
                    }

                    $nodes = $xpath->query($q2, $znode);
                    for ($i = 0; $i < $nodes->length; $i ++) {
                        for ($n = $nodes->item($i)->parentNode; $n && $n->nodeType == XML_ELEMENT_NODE && $n->nodeName == "te"; $n = $n->parentNode) {
                            /** @var \DOMElement $n */
                            $n->setAttribute("open", "1");
                        }
                    }

                    $this->getBranchHTML($request->get('typ'), $znode, $dom_ret, $dom_html, 0);
                }
            }
        } catch (\Exception $e) {

        }

        $ret["result"]["html"] = trim(str_replace(array("<html>", "</html>"), "", $dom_ret->saveXML($dom_html)));

        return new Response(json_encode($ret), 200, ['Content-Type' => 'application/json']);
    }

    private function getBranchHTML($type, \DOMElement $srcnode, \DOMDocument $dstdom, \DOMElement $dstnode, $depth)
    {
        $allsy = "";
        $nts = 0;
        for ($n = $srcnode->firstChild; $n; $n = $n->nextSibling) {
            if ($n->nodeType == XML_ELEMENT_NODE) {
                if ($n->nodeName == "te") {
                    $nts ++;
                    if ($n->getAttribute("open")) {
                        $id = $n->getAttribute("id");
                        /** @var \DOMElement $div_the */
                        $div_the = $dstnode->appendChild($dstdom->createElement("div"));
                        $div_the->setAttribute("id", "THE_" . $id);
                        $div_the->setAttribute("class", "s_");

                        /** @var \DOMElement $u */
                        $u = $div_the->appendChild($dstdom->createElement("u"));
                        $u->setAttribute("id", "THP_" . $id);

                        /** @var \DOMElement $div_thb */
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

        return ["allsy" => $allsy, "nts"   => $nts];
    }

    public function RejectXml(Request $request)
    {
        $ret = new \DOMDocument("1.0", "UTF-8");
        $ret->preserveWhiteSpace = false;

        $root = $ret->appendChild($ret->createElement("result"));
        $root->appendChild($ret->createCDATASection(var_export([
            'bid'   => $request->get('bid'),
            'id'    => $request->get('id'),
            'piv'   => $request->get('piv'),
        ], true)));

        $refresh_list = $root->appendChild($ret->createElement("refresh_list"));

        if (null === $bid = $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }

        try {
            $databox = $this->findDataboxById((int) $bid);
            $connbas = $databox->get_connection();

            $dom = $databox->get_dom_cterms();

            if ($dom) {
                $xpath = new \DOMXPath($dom);
                $q = "/cterms//te[@id='" . $request->get('id') . "']";

                /** @var \DOMElement $te */
                $te = $xpath->query($q)->item(0);
                if ($te) {
                    $this->doRejectBranch($connbas, $te);

                    $databox->saveCterms($dom);

                    /** @var \DOMElement $r */
                    $r = $refresh_list->appendChild($ret->createElement("refresh"));
                    $r->setAttribute("id", $te->parentNode->getAttribute("id"));
                    $r->setAttribute("type", "CT");
                }
            }
        } catch (\Exception $e) {

        }

        return new Response($ret->saveXML(), 200, ['Content-Type' => 'text/xml']);
    }

    private function doRejectBranch(Connection $connbas, \DOMElement $node)
    {
        if (strlen($oldid = $node->getAttribute("id")) > 1) {
            $node->setAttribute("id", $newid = ("R" . substr($oldid, 1)));
        }
        for ($n = $node->firstChild; $n; $n = $n->nextSibling) {
            if ($n->nodeType == XML_ELEMENT_NODE) {
                $this->doRejectBranch($connbas, $n);
            }
        }
    }

    public function searchCandidateXml(Request $request)
    {
        if (null === $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }

        $ret = $this->doSearchCandidate(
            $request->get('bid'),
            $request->get('pid'),
            $request->get('t'),
            $request->get('k'),
            $request->get('piv')
        );

        return new Response($ret->saveXML(), 200, ['Content-Type' => 'text/xml']);
    }

    private function doSearchCandidate($bid, $pid, $t, $k, $piv)
    {
        $ret = new \DOMDocument("1.0", "UTF-8");
        $ret->preserveWhiteSpace = false;

        $root = $ret->appendChild($ret->createElement("result"));
        $root->appendChild($ret->createCDATASection(var_export([
            "bid"   => $bid,
            "pid"   => $pid,
            "t"     => $t,
            "k"     => $k,
            "piv"   => $piv,
        ], true)));

        $ctlist = $root->appendChild($ret->createElement("candidates_list"));

        try {
            $databox = $this->findDataboxById((int) $bid);

            $domstruct = $databox->get_dom_structure();
            $domth = $databox->get_dom_thesaurus();
            $domct = $databox->get_dom_cterms();

            if ($domstruct && $domth && $domct) {
                $xpathth = new \DOMXPath($domth);
                $xpathct = new \DOMXPath($domct);

                // on cherche les champs d'ou peut provenir un candidat, en fct de l'endroit oe on veut inserer le nouveau terme
                $fields = array();
                $xpathstruct = new \DOMXPath($domstruct);
                $nodes = $xpathstruct->query("/record/description/*[@tbranch]");
                for ($i = 0; $i < $nodes->length; $i ++) {
                    /** @var \DOMElement $node */
                    $node = $nodes->item($i);
                    $fieldname = $node->nodeName;
                    $tbranch = $node->getAttribute("tbranch");
                    if ($pid != "") {
                        $q = "(" . $tbranch . ")/descendant-or-self::te[@id='" . $pid . "']";
                    } else {
                        $q = "(" . $tbranch . ")/descendant-or-self::te[not(@id)]";
                    }

                    $fields[$fieldname] = [
                        "name"     => $fieldname,
                        "tbranch"  => $tbranch,
                        "cid"      => null,
                        "sourceok" => false
                    ];

                    if (! $tbranch) {
                        continue;
                    }

                    $l = $xpathth->query($q)->length;

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
                $fields["[deleted]"] = [
                    "name"     => $this->app->trans('thesaurus:: corbeille'),
                    "tbranch"  => null,
                    "cid"      => null,
                    "sourceok" => true
                ];

                if (count($fields) > 0) {
                    $unicode = $this->getUnicode();
                    $q = "@w='" . \thesaurus::xquery_escape($unicode->remove_indexer_chars($t)) . "'";
                    if ($k) {
                        if ($k != "*") {
                            $q .= " and @k='" . \thesaurus::xquery_escape($unicode->remove_indexer_chars($k)) . "'";
                        }
                    } else {
                        $q .= " and not(@k)";
                    }
                    $q = "/cterms//te[./sy[$q]]";

                    $nodes = $xpathct->query($q);
                    // le terme peut etre present dans plusieurs candidats
                    for ($i = 0; $i < $nodes->length; $i ++) {
                        // on a trouve le terme dans les candidats, mais en provenance de quel champ ?.. on remonte au champ candidat
                        /** @var \DOMElement $node */
                        $node = $nodes->item($i);
                        /** @var \DOMElement $n */
                        for ($n = $nodes->item($i)->parentNode; $n && $n->parentNode && $n->parentNode->nodeName != "cterms"; $n = $n->parentNode) {
                            ;
                        }
                        if ($n && array_key_exists($f = $n->getAttribute("field"), $fields)) {
                            $fields[$f]["cid"] = $node->getAttribute("id");
                        }
                    }
                }

                foreach ($fields as $kfield => $field) {
                    if ($field["cid"] === null) {
                        continue;
                    }
                    /** @var \DOMElement $ct */
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

        return [$term, $context];
    }

    /**
     * @param \DOMElement $sy
     * @return ThesaurusEvent\SynonymParm|null
     *
     * helper to build event parameter
     */
    private function buildSynonymFromSy(\DOMElement $sy)
    {
        if($sy->nodeName == 'sy') {
            return new ThesaurusEvent\SynonymParm($sy->getAttribute('v'), $sy->getAttribute('lng'));
        }
        return null;
    }

    /**
     * @param \DOMElement $te
     * @return array|null
     *
     * helper to build event parameter
     */
    private function buildSynonymsFromTe(\DOMElement $te)
    {
        $ret = array();
        if(strtolower($te->nodeName) == 'te') {
            foreach ($te->childNodes as $child) {
                if($child->nodeType == XML_ELEMENT_NODE && $child->nodeName == 'sy') {
                    $ret[] = $this->buildSynonymFromSy($child);
                }
            }
            return $ret;
        }
        return null;
    }

    /**
     * @return \unicode
     */
    private function getUnicode()
    {
        return $this->app['unicode'];
    }

    /**
     * @return array
     */
    private function getAvailableLocales()
    {
        return $this->app['locales.available'];
    }

    /**
     * @return ElasticsearchOptions
     */
    private function getElasticsearchOptions()
    {
        return $this->app['elasticsearch.options'];
    }
}
