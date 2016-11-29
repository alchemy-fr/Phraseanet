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

use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Model\Entities\Preset;
use Alchemy\Phrasea\Model\Entities\User;
use caption_field;
use caption_Field_Value;
use databox;
use DOMElement;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ThesaurusXmlHttpController extends Controller
{
    const SEARCH_REPLACE_MAXREC = 25;

    public function acceptCandidatesJson(Request $request)
    {
        $ret = ['refresh' => []];
        $refresh = [];

        $sbas_id = $request->get('sbid');

        try {
            $databox = $this->findDataboxById($sbas_id);

            $domct = $databox->get_dom_cterms();
            if (!($domct instanceof \DOMDocument)) {
                throw new \Exception('Unable to load cterms');
            }

            $domth = $databox->get_dom_thesaurus();
            if (!($domth instanceof \DOMDocument)) {
                throw new \Exception('Unable to load thesaurus');
            }

            $xpathth = new \DOMXPath($domth);

            if ($request->get("tid") == "T") {
                $q = "/thesaurus";
            } else {
                $q = "/thesaurus//te[@id='" . $request->get("tid") . "']";
            }

            /** @var DOMElement $parentnode */
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

                    $te = $domth->importNode($ct, true);
                    $chgids = [];
                    if (($pid = $parentnode->getAttribute("id")) == "") {
                        $pid = "T" . $nid;
                    } else {
                        $pid .= "." . $nid;
                    }

                    $this->renumerate($request->get('piv'), $te, $pid, $chgids);
                    /** @var DOMElement $te */
                    $te = $parentnode->appendChild($te);

                    if ($request->get("debug")) {
                        printf("newid=%s<br/>\n", $te->getAttribute("id"));
                    }

                    $refreshid = $parentnode->getAttribute('id');
                    $refresh['T' . $refreshid] = [
                        'type' => 'T',
                        'sbid' => $sbas_id,
                        'id'   => $refreshid
                    ];
                    $thchanged = true;

                    $refreshid = $ct->parentNode->getAttribute("id");
                    $refresh['C' . $refreshid] = [
                        'type' => 'C',
                        'sbid' => $sbas_id,
                        'id'   => $refreshid
                    ];

                    $ct->parentNode->removeChild($ct);

                    $ctchanged = true;
                } elseif ($request->get("typ") == "SY") {
                    // importer tt le contenu de la branche sous la destination
                    for ($ct2 = $ct->firstChild; $ct2; $ct2 = $ct2->nextSibling) {
                        if ($ct2->nodeType != XML_ELEMENT_NODE || $ct2->nodeName != 'sy') {
                            continue;
                        }
                        $nid = $parentnode->getAttribute("nextid");
                        $parentnode->setAttribute("nextid", (int) $nid + 1);

                        $te = $domth->importNode($ct2, true);
                        $chgids = [];
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

                        $thchanged = true;
                    }

                    $refreshid = $parentnode->parentNode->getAttribute("id");
                    $refresh['T' . $refreshid] = [
                        'type' => 'T',
                        'sbid' => $sbas_id,
                        'id'   => $refreshid
                    ];

                    $refreshid = $ct->parentNode->getAttribute("id");
                    $refresh['C' . $refreshid] = [
                        'type' => 'C',
                        'sbid' => $sbas_id,
                        'id'   => $refreshid
                    ];

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

        return $this->app->json($ret);
    }

    private function renumerate($lang, DOMElement $node, $id, &$chgids, $depth = 0)
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

    public function checkCandidateTargetJson(Request $request)
    {
        $json = [];

        if (null === $sbas_id = $request->get("sbid")) {
            return $this->app->json($json);
        }

        $databox = $this->findDataboxById((int) $sbas_id);

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
                        /** @var DOMElement $n2 */
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

        return $this->app->json($json);
    }

    public function editingPresetsJson(Request $request)
    {
        $ret = ['parm' => [
            'act'      => $request->get('act'),
            'sbas'     => $request->get('sbas'),
            'presetid' => $request->get('presetid'),
            'title'    => $request->get('title'),
            'fields'   => $request->get('fields'),
            'debug'    => $request->get('debug'),
        ]];

        switch ($request->get('act')) {
            case 'DELETE':
                if (null === $preset = $this->getPresetRepository()->find($id = $request->get('presetid'))) {
                    $this->app->abort(404, sprintf("Preset with id '%' could not be found", $id));
                }
                $this->getPresetManipulator()->delete($preset);

                $ret['html'] = $this->getPresetHTMLList($request->get('sbas'), $this->getAuthenticatedUser());
                break;
            case 'SAVE':
                $this->getPresetManipulator()->create(
                    $this->getAuthenticatedUser(),
                    $request->get('sbas'),
                    $request->get('title'),
                    $request->get('fields')
                );

                $ret['html'] = $this->getPresetHTMLList($request->get('sbas'), $this->getAuthenticatedUser());
                break;
            case 'LIST':
                $ret['html'] = $this->getPresetHTMLList($request->get('sbas'), $this->getAuthenticatedUser());
                break;
            case "LOAD":
                if (null === $preset = $this->getPresetRepository()->find($id = $request->get('presetid'))) {
                    $this->app->abort(404, sprintf("Preset with id '%' could not be found", $id));
                }

                $fields = [];
                foreach ($preset->getData() as $field) {
                    $fields[$field['name']][] = $field['value'];
                }

                $ret['fields'] = $fields;
                break;
        }

        return $this->app->json($ret);
    }

    private function getPresetHTMLList($sbasId, User $user)
    {
        $data = [];
        /** @var Preset[] $presets */
        $presets = $this->getPresetRepository()->findBy(['user' => $user, 'sbasId' => $sbasId], ['created' => 'asc']);
        foreach ($presets as $preset) {
            $presetData = $fields = [];
            array_walk($preset->getData(), function ($field) use ($fields) {
                $fields[$field['name']][] = $field['value'];
            });
            $presetData['id'] = $preset->getId();
            $presetData['title'] = $preset->getTitle();
            $presetData['fields'] = $fields;

            $data[] = $presetData;
        }

        return $this->render('thesaurus/presets.html.twig', ['presets' => $data]);
    }

    public function getSynonymsXml(Request $request)
    {
        $ret = new \DOMDocument("1.0", "UTF-8");
        $ret->preserveWhiteSpace = false;
        /** @var DOMElement $root */
        $root = $ret->appendChild($ret->createElement("result"));
        $root->appendChild($ret->createCDATASection(var_export([
            'bid' => $request->get('bid'),
            'id'  => $request->get('id'),
        ], true)));

        if (null !== $request->get('bid')) {
            $databox = $this->findDataboxById((int) $request->get('bid'));
            $dom = $databox->get_dom_thesaurus();

            if ($dom) {
                $xpath = $databox->get_xpath_thesaurus();
                $q = "/thesaurus//sy[@id='" . $request->get('id') . "']";

                $nodes = $xpath->query($q);
                if ($nodes->length > 0) {
                    /** @var DOMElement $n2 */
                    $n2 = $nodes->item(0);
                    $root->setAttribute("t", $n2->getAttribute("v"));
                }
            }
        }

        return new Response($ret->saveXML(), 200, ['Content-Type' => 'text/xml']);
    }

    public function getTermHtml(Request $request)
    {
        $html = '';

        if (null === $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }
        $databox = $this->findDataboxById((int) $request->get("bid"));
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
            $tts = [];
            // on dresse la liste des termes specifiques avec comme cle le synonyme
            // dans la langue pivot
            /** @var DOMElement $n */
            for ($n = $nodes->item(0)->firstChild; $n; $n = $n->nextSibling) {
                if ($n->nodeName == "te") {
                    $nts++;
                    $allsy = "";
                    $tsy = [];
                    $firstksy = null;
                    $ksy = $realksy = null;
                    // on liste les sy pour fabriquer la cle
                    /** @var DOMElement $n2 */
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
                            if (!$realksy && $request->get("lng") && $lng == $request->get("lng")) {
                                $realksy = $ksy;
                                $allsy = $t . ($allsy ? " ; " : "") . $allsy;

                                array_push($tsy, [
                                    "id" => $n2->getAttribute("id"),
                                    "sy" => $t
                                ]);
                            } else {
                                $allsy .= ( $allsy ? " ; " : "") . $t;
                                array_push($tsy, [
                                    "id" => $n2->getAttribute("id"),
                                    "sy" => $t
                                ]);
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
                        $tts[$realksy . "_" . $uniq] = [
                            "id"     => $n->getAttribute("id"),
                            "allsy"  => $allsy,
                            "nchild" => $xpath->query("te", $n)->length,
                            "tsy"    => $tsy
                        ];
                    } else {
                        $tts[] = [
                            "id"     => $n->getAttribute("id"),
                            "allsy"  => $allsy,
                            "nchild" => $xpath->query("te", $n)->length,
                            "tsy"    => $tsy
                        ];
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

    public function getTermXml(Request $request)
    {
        $ret = new \DOMDocument("1.0", "UTF-8");
        $ret->preserveWhiteSpace = false;
        $root = $ret->appendChild($ret->createElement("result"));
        $root->appendChild($ret->createCDATASection(var_export([
            "bid"    => $request->get('bid'),
            "id"     => $request->get('id'),
            "sortsy" => $request->get('sortsy'),
            "debug"  => $request->get('debug'),
        ], true)));

        $html = $root->appendChild($ret->createElement("html"));

        if (null === $request->get("bid")) {
            return new Response('Missing bid parameter', 400);
        }

        $databox = $this->findDataboxById((int) $request->get('bid'));
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
            $tts = [];
            // on dresse la liste des termes specifiques avec comme cle le synonyme
            // dans la langue pivot
            $locale = $this->app['locale'];
            /** @var DOMElement $n */
            for ($n = $nodes->item(0)->firstChild; $n; $n = $n->nextSibling) {
                if ($n->nodeName == "te") {
                    $nts++;
                    $allsy = "";
                    $tsy = [];
                    $firstksy = null;
                    $ksy = $realksy = null;
                    // on liste les sy pour fabriquer la cle
                    /** @var DOMElement $n2 */
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

                            if (!$realksy && $locale && $lng == $locale) {
                                $realksy = $ksy;
                                $allsy = $t . ($allsy ? " ; " : "") . $allsy;

                                array_push($tsy, [
                                    "id" => $n2->getAttribute("id"),
                                    "sy" => $t
                                ]);
                            } else {
                                $allsy .= ( $allsy ? " ; " : "") . $t;
                                array_push($tsy, [
                                    "id" => $n2->getAttribute("id"),
                                    "sy" => $t
                                ]);
                            }
                        }
                    }
                    if (!$realksy)
                        $realksy = $firstksy;

                    if ($request->get("sortsy") && $locale) {
                        for ($uniq = 0; $uniq < 9999; $uniq++) {
                            if (!isset($tts[$realksy . "_" . $uniq])) {
                                break;
                            }
                        }
                        $tts[$realksy . "_" . $uniq] = [
                            "id"     => $n->getAttribute("id"),
                            "allsy"  => $allsy,
                            "nchild" => $xpath->query("te", $n)->length,
                            "tsy"    => $tsy
                        ];
                    } else {
                        $tts[] = [
                            "id"     => $n->getAttribute("id"),
                            "allsy"  => $allsy,
                            "nchild" => $xpath->query("te", $n)->length,
                            "tsy"    => $tsy
                        ];
                    }
                } elseif ($n->nodeName == "sy") {

                }
            }

            if ($request->get("sortsy") && $locale) {
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

        return new Response($ret->saveXML(), 200, ['Content-Type' => 'text/xml']);
    }

    public function openBranchJson(Request $request)
    {
        if (null === ($lng = $request->get('lng'))) {
            $data = explode('_', $this->app['locale']);
            if (count($data) > 0) {
                $lng = $data[0];
            }
        }

        $html = '';

        $sbid = (int) $request->get('sbid');

        $lcoll = '';

        $acl = $this->getAclForUser();
        $collections = $acl->get_granted_base([], [$sbid]); // array(), $sbid);
        foreach ($collections as $collection) {
            $lcoll .= ($lcoll?",":"") . $collection->get_coll_id();
        }

        $tids = explode('.', $request->get('id'));
        $thid = implode('.', $tids);

        try {
            $databox = $this->findDataboxById($sbid);
            $dbname = \phrasea::sbas_labels($sbid, $this->app);

            if ($request->get('type') == 'T') {
                $xqroot = 'thesaurus';
                $dom = $databox->get_dom_thesaurus();
            } else { // C
                $xqroot = 'cterms';
                $dom = $databox->get_dom_cterms();
            }

            if ($dom) {
                $xpath = new \DOMXPath($dom);
                if ($thid == 'T' || $thid == 'C') {
                    $q = '/' . $xqroot;
                } else {
                    $q = '/' . $xqroot . '//te[@id=\'' . $thid . '\']';
                }

                $nodes = $xpath->query($q);
                if ($nodes->length > 0) {
                    $node0 = $nodes->item(0);

                    $key0 = null; // key of the sy in the current language (or key of the first sy if we can't find good lng)

                    // on dresse la liste des termes specifiques avec comme cle le synonyme dans la langue pivot
                    $nts = 0;
                    $tts = [];
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
                            $tts[$key0 . '_' . $uniq] = [
                                /** @Ignore */
                                'label' => $label,
                                'nts'   => $nts0,
                                'n'     => $n
                            ];
                        }
                    }

                    $field0 = $node0->getAttribute('field');
                    if ($field0) {
                        $field0 = 'field="' . $field0 . '"';
                    }

                    $html .= '<UL ' . $field0 . '>' . "\n";

                    if ($nts > 0) {
                        if ($request->get('sortsy') && $lng != '') {
                            ksort($tts, SORT_STRING);
                        } elseif ($request->get('type') == 'C') {
                            $tts = array_reverse($tts);
                        }

                        /** @var DOMElement[] $ts */
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

                            $html .= "\n";

                            if ($ts['nts'] > 0) {
                                $html .= '<UL style="display:none">loading</UL>' . "\n";
                            }

                            $html .= '</LI>' . "\n";
                        }
                    }

                    $html .= '</UL>' . "\n";
                }
            }
        } catch (\Exception $e) {

        }

        return $this->app->json(['parm' => [
            'sbid'   => $request->get('sbid'),
            'type'   => $request->get('type'),
            'id'     => $request->get('id'),
            'lng'    => $request->get('lng'),
            'sortsy' => $request->get('sortsy'),
            'debug'  => $request->get('debug'),
            'root'   => $request->get('root'),
            'last'   => $request->get('last'),
        ], 'html' => $html]);
    }

    private function buildBranchLabel($dbname, $language, DOMElement $n, &$key0, &$nts0)
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

    public function openBranchesHtml(Request $request)
    {
        if (null === $mod = $request->get('mod')) {
            $mod = 'TREE';
        }

        if (null === $bid = $request->get('bid')) {
            return new Response('Missing bid parameter', 400);
        }

        $html = '';

        $databox = $this->findDataboxById((int) $bid);
        $dom = $databox->get_dom_thesaurus();

        if (!$dom) {
            return new Response('Unable to load thesaurus', 500);
        }

        $xpath = $databox->get_xpath_thesaurus();
        $q = '/thesaurus';

        if (($znode = $xpath->query($q)->item(0))) {
            $q2 = '//sy';
            if ($request->get('t')) {

                $t = $this->splitTermAndContext($request->get('t'));
                $unicode = $this->getUnicode();
                $q2 = 'starts-with(@w, \'' . \thesaurus::xquery_escape($unicode->remove_indexer_chars($t[0])) . '\')';
                if ($t[1]) {
                    $q2 .= ' and starts-with(@k, \'' . \thesaurus::xquery_escape($unicode->remove_indexer_chars($t[1])) . '\')';
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

        return [$term, $context];
    }

    private function getBranchesHTML($bid, DOMElement $srcnode, &$html, $depth)
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

    public function openBranchesJson(Request $request)
    {
        if ('' === ($mod = strtoupper($request->get('mod')))) {
            $mod = 'TREE';
        }

        $ret = array(
            'parms'=> array(
                'bid'   => $request->get('bid'),
                't'     => $request->get('t'),
                'mod'   => $request->get('mod'),
                'debug' => $request->get('debug')
            ),
            'result'=>NULL,
            'error'=>'',
            'error_code'=>0,
        );

        if (null === ($bid = $request->get('bid'))) {
            $ret['error'] = 'Missing bid parameter';
            $ret['error_code'] = 400;

            return json_encode($ret);
        }
        if ($mod != 'TREE' && $mod != "LIST") {
            $ret['error'] = 'bad mod, TREE|LIST';
            $ret['error_code'] = 400;

            return json_encode($ret);
        }

        /** @var \databox $databox */
        $databox = $this->findDataboxById((int) $bid);
        $dom = $databox->get_dom_thesaurus();

        if (!$dom) {
            $ret['error'] = 'Unable to load thesaurus';
            $ret['error_code'] = 500;

            return json_encode($ret);
        }

        $xpath = $databox->get_xpath_thesaurus();
        $q = '/thesaurus';

        if (($znode = $xpath->query($q)->item(0))) {
            $q2 = '//sy';
            if ($request->get('t')) {
                $t = $this->splitTermAndContext($request->get('t'));
                $unicode = $this->getUnicode();
                $q2 = 'starts-with(@w, \'' . \thesaurus::xquery_escape($unicode->remove_indexer_chars($t[0])) . '\')';
                if ($t[1])
                    $q2 .= ' and starts-with(@k, \'' . \thesaurus::xquery_escape($unicode->remove_indexer_chars($t[1])) . '\')';
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
                $ret['result'] = $this->getBrancheJson($bid, $znode, $ret['result'], 0);
            } else {
                $ret['result'] = array();
                for ($i = 0; $i < $nodes->length; $i++) {
                    $n = $nodes->item($i);
                    $t = $n->getAttribute('v');
                    $tid = $n->getAttribute('id');

                    $ret['result'][] = array(
                        'id' => $n->getAttribute('id'),
                        't'  => $n->getAttribute('v'),
                    );
                }
            }
        }
        if($request->get('debug')) {
            printf("<pre>%s</pre>", var_export($ret, true));
            // printf("<pre>%s</pre>", json_encode($ret, JSON_PRETTY_PRINT));
            die;
        }
        return json_encode($ret, JSON_PRETTY_PRINT);
    }


    public function openBranchesXml(Request $request)
    {
        if (null === $mod = $request->get('mod')) {
            $mod = 'TREE';
        }

        $ret = new \DOMDocument('1.0', 'UTF-8');
        $ret->preserveWhiteSpace = false;
        $root = $ret->appendChild($ret->createElement('result'));
        $root->appendChild($ret->createCDATASection(var_export([
            'bid'   => $request->get('bid'),
            't'     => $request->get('t'),
            'mod'   => $request->get('mod'),
            'debug' => $request->get('debug'),
        ], true)));

        $html = $root->appendChild($ret->createElement('html'));

        if (null === $bid = $request->get('bid')) {
            return new Response('Missing bid parameter', 400);
        }

        /** @var \databox $databox */
        $databox = $this->findDataboxById((int) $bid);
        $dom = $databox->get_dom_thesaurus();

        if (!$dom) {
            return new Response('Unable to load thesaurus', 500);
        }

        $xpath = $databox->get_xpath_thesaurus();
        $q = '/thesaurus';

        $zhtml = '';
        if (($znode = $xpath->query($q)->item(0))) {
            $q2 = '//sy';
            if ($request->get('t')) {
                $t = $this->splitTermAndContext($request->get('t'));
                $unicode = $this->getUnicode();
                $q2 = 'starts-with(@w, \'' . \thesaurus::xquery_escape($unicode->remove_indexer_chars($t[0])) . '\')';
                if ($t[1])
                    $q2 .= ' and starts-with(@k, \'' . \thesaurus::xquery_escape($unicode->remove_indexer_chars($t[1])) . '\')';
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

                $this->getBrancheXML($bid, $znode, $zhtml, 0);
            } else {
                for ($i = 0; $i < $nodes->length; $i++) {
                    $n = $nodes->item($i);
                    $t = $n->getAttribute('v');
                    $tid = $n->getAttribute('id');

                    $zhtml .= '<p id=\'TH_T.' . $bid . '.' . $tid . '\'>';
                    $zhtml .= '<b id=\'GL_W.' . $bid . '.' . $tid . '\'>' . $t . '</b>';
                    $zhtml .= '</p>';
                }
            }
            $html->appendChild($ret->createTextNode($zhtml));
        }

        return new Response($zhtml, 200, array('Content-Type' => 'text/xml'));
    }

    private function getBrancheJson($bid, DOMElement $srcnode, &$ret, $depth)
    {
        $tid = $srcnode->getAttribute('id');
        $nts = 0;
        $allsy = array();
        for ($n = $srcnode->firstChild; $n; $n = $n->nextSibling) {
            if ($n->nodeName == 'sy') {
                $t = $n->getAttribute('v');
                $allsy[] = array(
                    'id' => $n->getAttribute('id'),
                    't'  => $t,
                    'lng' => $n->getAttribute('lng'),
                    'bold' => (bool)$n->getAttribute('bold'),
                );
            } elseif ($n->nodeName == 'te') {
                $nts++;
            }
        }

        $nret = array(
            'id' => $tid,
            'nts' => $nts,
            'synonyms' => $allsy,
            'children' => array(),
        );

        for ($n = $srcnode->firstChild; $n; $n = $n->nextSibling) {
            if ($n->nodeName == 'te') {
                if ($n->getAttribute('open')) {
                    $nret['children'][] = $this->getBrancheJson($bid, $n, $ret['children'], $depth + 1);
                }
            }
        }

        return $nret;
    }

    private function getBrancheXML($bid, DOMElement $srcnode, &$html, $depth)
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

    public function replaceCandidateJson(Request $request)
    {
        $ret = [
            'ctermsDeleted'    => [],
            'maxRecsUpdatable' => self::SEARCH_REPLACE_MAXREC,
            'nRecsToUpdate'    => 0,
            'nRecsUpdated'     => 0,
            'msg'              => ''
        ];

        // group ids by base
        $tsbas = [];
        foreach ($request->get('id') as $id) {
            $id = explode('.', $id);
            $sbas_id = array_shift($id);
            if (!array_key_exists($sbas_id, $tsbas)) {
                $tsbas[$sbas_id] = [];
            }
            $tsbas[$sbas_id][] = implode('.', $id);
        }

        // loop on bases
        foreach ($tsbas as $sbas_id => $sbas) {
            try {
                $databox = $this->findDataboxById($sbas_id);
                $domct = $databox->get_dom_cterms();
            } catch (\Exception $e) {
                continue;
            }

            if (!$domct) {
                continue;
            }

            $domct_changed = false;
            $xpathct = new \DOMXPath($domct);

            foreach ($sbas as $tid) {
                $xp = '//te[@id="' . $tid . '"]/sy';
                $nodes = $xpathct->query($xp);
                if ($nodes->length == 1) {
                    $sy = $nodes->item(0);
                    $te = $sy->parentNode;
                    $ret['ctermsDeleted'][] = $sbas_id . '.' . $te->getAttribute('id');
                    $te->parentNode->removeChild($te);
                    $domct_changed = true;
                }
            }

            if ($domct_changed && !$request->get('debug')) {
                $databox->saveCterms($domct);
            }
        }

        return $this->app->json($ret);
    }

    public function searchTermJson(Request $request)
    {
        if (null === $lng = $request->get('lng')) {
            $data = explode('_', $this->app['locale']);
            if (count($data) > 0) {
                $lng = $data[0];
            }
        }

        $html = '';
        $sbid = (int) $request->get('sbid');

        try {
            $databox = $this->findDataboxById($sbid);

            $html = "" . '<LI id="TX_P.' . $sbid . '.T" class="expandable">' . "\n";
            $html .= "\t" . '<div class="hitarea expandable-hitarea"></div>' . "\n";
            $html .= "\t" . '<span>' . \phrasea::sbas_labels($sbid, $this->app) . '</span>' . "\n";

            if ($request->get('t')) {
                $dom_struct = null;
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

                    $t = $this->splitTermAndContext($request->get('t'));
                    $unicode = $this->getUnicode();
                    $q2 = 'starts-with(@w, \'' . \thesaurus::xquery_escape($unicode->remove_indexer_chars($t[0])) . '\')';
                    if ($t[1])
                        $q2 .= ' and starts-with(@k, \'' . \thesaurus::xquery_escape(
                                $unicode->remove_indexer_chars($t[1])) . '\')';
                    $q2 = '//sy[' . $q2 . ' and @lng=\'' . $lng . '\']';

                    $q .= $q2;

                    $nodes = $xpath->query($q);

                    for ($i = 0; $i < $nodes->length; $i++) {
                        $nodes->item($i)->setAttribute('bold', '1');
                        for ($n = $nodes->item($i)->parentNode; $n && $n->nodeType == XML_ELEMENT_NODE && $n->nodeName == 'te'; $n = $n->parentNode) {
                            $n->setAttribute('open', '1');
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

        return $this->app->json(['parm' => [
            'sbid'  => $request->get('sbid'),
            't'     => $request->get('t'),
            'field' => $request->get('field'),
            'lng'   => $request->get('lng'),
            'debug' => $request->get('debug'),
        ], 'html' => $html]);
    }

    private function buildTermLabel($language, DOMElement $n, &$key0, &$nts0)
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

    private function getHTMLTerm($sbid, $lng, DOMElement $srcnode, &$html, $depth = 0)
    {
        $tid = $srcnode->getAttribute('id');

        // let's work on each 'te' (=ts) subnode
        $nts = 0;
        $ntsopened = 0;
        $tts = [];
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
                    $tts[$key0 . '_' . $uniq] = [
                        /** @Ignore */
                        'label' => $label,
                        'nts'   => $nts0,
                        'n'     => $n
                    ];
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
                /** @var DOMElement[] $ts */
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

    /**
     * @return \unicode
     */
    private function getUnicode()
    {
        return $this->app['unicode'];
    }
}
