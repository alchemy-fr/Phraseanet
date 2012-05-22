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
 * @package     searchEngine
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class searchEngine_adapter_phrasea_queryParser
{
    public $ops = array(
        "et" => array("NODETYPE" => PHRASEA_OP_AND, "CANNUM"   => false),
        "and"      => array("NODETYPE" => PHRASEA_OP_AND, "CANNUM"   => false),
        "ou"       => array("NODETYPE" => PHRASEA_OP_OR, "CANNUM"   => false),
        "or"       => array("NODETYPE" => PHRASEA_OP_OR, "CANNUM"   => false),
        "sauf"     => array("NODETYPE" => PHRASEA_OP_EXCEPT, "CANNUM"   => false),
        "except"   => array("NODETYPE" => PHRASEA_OP_EXCEPT, "CANNUM"   => false),
        "pres"     => array("NODETYPE" => PHRASEA_OP_NEAR, "CANNUM"   => true),
        "near"     => array("NODETYPE" => PHRASEA_OP_NEAR, "CANNUM"   => true),
        "avant"    => array("NODETYPE" => PHRASEA_OP_BEFORE, "CANNUM"   => true),
        "before"   => array("NODETYPE" => PHRASEA_OP_BEFORE, "CANNUM"   => true),
        "apres"    => array("NODETYPE" => PHRASEA_OP_AFTER, "CANNUM"   => true),
        "after"    => array("NODETYPE" => PHRASEA_OP_AFTER, "CANNUM"   => true),
        "dans"     => array("NODETYPE" => PHRASEA_OP_IN, "CANNUM"   => false),
        "in"       => array("NODETYPE" => PHRASEA_OP_IN, "CANNUM"   => false)
    );
    public $opk = array(
        "<" => array("NODETYPE" => PHRASEA_OP_LT, "CANNUM"   => false),
        ">"        => array("NODETYPE" => PHRASEA_OP_GT, "CANNUM"   => false),
        "<="       => array("NODETYPE" => PHRASEA_OP_LEQT, "CANNUM"   => false),
        ">="       => array("NODETYPE" => PHRASEA_OP_GEQT, "CANNUM"   => false),
        "<>"       => array("NODETYPE" => PHRASEA_OP_NOTEQU, "CANNUM"   => false),
        "="        => array("NODETYPE" => PHRASEA_OP_EQUAL, "CANNUM"   => false),
        ":"        => array("NODETYPE" => PHRASEA_OP_COLON, "CANNUM"   => false)
    );
    public $spw = array(
        "all" => array(
            "CLASS"    => "PHRASEA_KW_ALL", "NODETYPE" => PHRASEA_KW_ALL, "CANNUM"   => false
        ),
        "last"     => array(
            "CLASS"    => "PHRASEA_KW_LAST", "NODETYPE" => PHRASEA_KW_LAST, "CANNUM"   => true
        ),
        //  "first"    => array("CLASS"=>PHRASEA_KW_FIRST, "CANNUM"=>true),
        //  "premiers" => array("CLASS"=>PHRASEA_KW_FIRST, "CANNUM"=>true),
        "tout"     => array(
            "CLASS"    => "PHRASEA_KW_ALL", "NODETYPE" => PHRASEA_KW_ALL, "CANNUM"   => false
        ),
        "derniers" => array(
            "CLASS"           => "PHRASEA_KW_LAST", "NODETYPE"        => PHRASEA_KW_LAST, "CANNUM"          => true
        )
    );
    public $quoted_defaultop = array(
        "VALUE"    => "default_avant", "NODETYPE" => PHRASEA_OP_BEFORE, "PNUM"     => 0
    );
    public $defaultop = array(
        "VALUE"      => "and", "NODETYPE"   => PHRASEA_OP_AND, "PNUM"       => NULL
    );
    public $defaultlast = 12;
    public $phq;
    public $errmsg = "";

    /**
     *
     * @var boolean
     */
    public $debug = false;

    /**
     * un tableau qui contiendra des propositions de thesaurus
     * pour les termes de l'arbre simple
     *
     * @var array
     */
    public $proposals = Array("QRY"   => "", "BASES" => array(), "QUERIES" => array());

    /**
     * Current language for thesaurus
     * @var <type>
     */
    public $lng = null;
    protected $unicode;

    public function __construct($lng = "???")
    {
        $this->lng = $lng;
        $this->unicode = new unicode();

        return $this;
    }

    public function mb_trim($s, $encoding)
    {
        return(trim($s));
    }

    public function mb_ltrim($s, $encoding)
    {
        return(ltrim($s));
    }

    public function parsequery($phq)
    {
        if ($this->debug) {
            for ($i = 0; $i < mb_strlen($phq, 'UTF-8'); $i ++ ) {
                $c = mb_substr($phq, $i, 1, 'UTF-8');
                printf("// %s : '%s' (%d octets)\n", $i, $c, strlen($c));
            }
        }

        $this->proposals = Array("QRY"   => "", "BASES" => array(), "QUERIES" => array());
        $this->phq = $this->mb_trim($phq, 'UTF-8');
        if ($this->phq != "") {
            return($this->maketree(0));
        } else {

            if ($this->errmsg != "") {
                $this->errmsg .= sprintf("\\n");
            }

            $this->errmsg .= _('qparser::la question est vide');

            return(null);
        }
    }

    public function astext($tree)
    {
        switch ($tree["CLASS"]) {
            case "SIMPLE":
                if (is_array($tree["VALUE"])) {
                    return(implode(" ", $tree["VALUE"]));
                } else {

                    return($tree["VALUE"]);
                }
                break;
            case "QSIMPLE":
                if (is_array($tree["VALUE"])) {
                    return("\"" . implode(" ", $tree["VALUE"]) . "\"");
                } else {
                    return("\"" . $tree["VALUE"] . "\"");
                }
                break;
            case "PHRASEA_KW_ALL":
                return($tree["VALUE"][0]);
                break;
            case "PHRASEA_KW_LAST":
                if ($tree["PNUM"] !== null) {
                    return("" . $tree["VALUE"][0] . "[" . $tree["PNUM"] . "]");
                } else {
                    return($tree["VALUE"][0]);
                }
                break;
            case "OPS":
            case "OPK":
                if (isset($tree["PNUM"])) {
                    return("(" . $this->astext($tree["LB"]) . " " . $tree["VALUE"] . "[" . $tree["PNUM"] . "] " . $this->astext($tree["RB"]) . ")");
                } else {
                    return("(" . $this->astext($tree["LB"]) . " " . $tree["VALUE"] . " " . $this->astext($tree["RB"]) . ")");
                }
                break;
        }
    }

    public function astable(&$tree)
    {
        $this->calc_complexity($tree);
        $txt = "";
        $this->astable2($txt, $tree);
        $txt = "<table border=\"1\">\n<tr>\n" . $txt . "</tr>\n</table>\n";

        return($txt);
    }

    public function calc_complexity(&$tree)
    {
        if ($tree) {
            if ($tree["CLASS"] == "OPS" || $tree["CLASS"] == "OPK") {
                return($tree["COMPLEXITY"] = $this->calc_complexity($tree["LB"]) + $this->calc_complexity($tree["RB"]));
            } else {
                return($tree["COMPLEXITY"] = 1);
            }
        }
    }

    public function astable2(&$out, &$tree, $depth = 0)
    {
        switch ($tree["CLASS"]) {
            case "SIMPLE":
                if (is_array($tree["VALUE"]))
                    $txt = implode(" ", $tree["VALUE"]);
                else
                    $txt = $tree["VALUE"];
                $out .= "\t<td>" . $txt . "</td>\n";
                break;
            case "QSIMPLE":
                if (is_array($tree["VALUE"]))
                    $txt = implode(" ", $tree["VALUE"]);
                else
                    $txt = $tree["VALUE"];
                $out .= "\t<td>&quot;" . $txt . "&quot;</td>\n";
                break;
            case "PHRASEA_KW_ALL":
                $out .= "\t<td>" . $tree["VALUE"][0] . "</td>\n";
                break;
            case "PHRASEA_KW_LAST":
                if ($tree["PNUM"] !== null)
                    $out .= "\t<td>" . $tree["VALUE"][0] . "[" . $tree["PNUM"] . "]" . "</td>\n";
                else
                    $out .= "\t<td>" . $tree["VALUE"][0] . "</td>\n";
                break;
            case "OPS":
            case "OPK":
                $op = $tree["VALUE"];
                if (isset($tree["PNUM"]))
                    $op .= "[" . $tree["PNUM"] . "]";
                $out .= "\t<td colspan=\"" . $tree["COMPLEXITY"] . "\">$op</td>\n";
                $this->astable2($out, $tree["LB"], $depth + 1);
                $this->astable2($out, $tree["RB"], $depth + 1);
                $out .= "</tr>\n<tr>\n";
                break;
        }
    }

    public function dumpDiv(&$tree)
    {
        print("<div class=\"explain\">\n");
        $this->dumpDiv2($tree);
        print("</div>\n");
    }

    public function dumpDiv2(&$tree, $depth = 0)
    {
        switch ($tree["CLASS"]) {
            case "SIMPLE":
                if (is_array($tree["VALUE"]))
                    $s = implode("</font></b> , <b><font color='green'>", $tree["VALUE"]);
                else
                    $s = $tree["VALUE"];
                print(str_repeat("\t", $depth) . "<b><font color='green'>" . $s . "</font></b>\n");
            case "QSIMPLE":
                $s = "";
                if (is_array($tree["VALUE"]))
                    $s = implode("</font></b> , <b><font color='green'>", $tree["VALUE"]);
                else
                    $s = $tree["VALUE"];
                print(str_repeat("\t", $depth) . "&quot;<b><font color='green'>" . $s . "</font></b>&quot;\n");
                break;
            case "PHRASEA_KW_ALL":
                printf(str_repeat("\t", $depth) . "<b><font color='red'>%s</font></b>\n", $tree["VALUE"][0]);
                break;
            case "PHRASEA_KW_LAST":
                if ($tree["PNUM"] !== null)
                    printf(str_repeat("\t", $depth) . "<b><font color='blue'>%s <i>%s</i></font></b>\n", $tree["VALUE"][0], $tree["PNUM"]);
                else
                    printf(str_repeat("\t", $depth) . "<b><font color='blue'>%s</font></b>\n", $tree["VALUE"][0]);
                break;
            //    case PHRASEA_KW_FIRST:
            //      if($tree["PNUM"]!==null)
            //        printf("<b><font color='blue'>%s <i>%s</i></font></b>", $tree["VALUE"], $tree["PNUM"]);
            //      else
            //        printf("<b><font color='blue'>%s</font></b>", $tree["VALUE"]);
            //      break;
            case "OPS":
            case "OPK":
                print(str_repeat("\t", $depth) . "<div>\n");
                $this->dumpDiv2($tree["LB"], $depth + 1);
                print(str_repeat("\t", $depth) . "</div>\n");
                print(str_repeat("\t", $depth) . "<div>\n");
                if (isset($tree["PNUM"]))
                    printf(str_repeat("\t", $depth + 1) . " %s[%s]\n", $tree["VALUE"], $tree["PNUM"]);
                else
                    printf(str_repeat("\t", $depth + 1) . " %s\n", $tree["VALUE"]);
                print(str_repeat("\t", $depth) . "</div>\n");
                print(str_repeat("\t", $depth) . "<div>\n");
                $this->dumpDiv2($tree["RB"], $depth + 1);
                print(str_repeat("\t", $depth) . "</div>\n");

                break;
        }
    }

    public function dump($tree)
    {
        switch ($tree["CLASS"]) {
            case "SIMPLE":
                if (is_array($tree["VALUE"]))
                    $s = implode("</font></b> , <b><font color='green'>", $tree["VALUE"]);
                else
                    $s = $tree["VALUE"];
                print("<b><font color='green'>" . $s . "</font></b>");
                break;
            case "QSIMPLE":
                if (is_array($tree["VALUE"]))
                    $s = implode("</font></b> , <b><font color='green'>", $tree["VALUE"]);
                else
                    $s = $tree["VALUE"];
                print("&quot;<b><font color='green'>" . $s . "</font></b>&quot;");
                break;
            case "PHRASEA_KW_ALL":
                printf("<b><font color='red'>%s</font></b>", $tree["VALUE"][0]);
                break;
            case "PHRASEA_KW_LAST":
                if ($tree["PNUM"] !== null)
                    printf("<b><font color='blue'>%s <i>%s</i></font></b>", $tree["VALUE"][0], $tree["PNUM"]);
                else
                    printf("<b><font color='blue'>%s</font></b>", $tree["VALUE"][0]);
                break;
            //    case PHRASEA_KW_FIRST:
            //      if($tree["PNUM"]!==null)
            //        printf("<b><font color='blue'>%s <i>%s</i></font></b>", $tree["VALUE"], $tree["PNUM"]);
            //      else
            //        printf("<b><font color='blue'>%s</font></b>", $tree["VALUE"]);
            //      break;
            case "OPS":
            case "OPK":
                print("<table border='1'>");
                print("<tr>");
                print("<td colspan='2' align='center'>");
                if (isset($tree["PNUM"]))
                    printf(" %s[%s] ", $tree["VALUE"], $tree["PNUM"]);
                else
                    printf(" %s ", $tree["VALUE"]);
                print("</td>");
                print("</tr>");
                print("<tr>");
                print("<td width='50%' align='center' valign='top'>");
                print($this->dump($tree["LB"]));
                print("</td>");
                print("<td width='50%' align='center' valign='top'>");
                print($this->dump($tree["RB"]));
                print("</td>");
                print("</tr>");
                print("</table>");
                break;
        }
    }

    public function priority_opk(&$tree, $depth = 0)
    {
        if ( ! $tree) {
            return;
        }

        if ($tree["CLASS"] == "OPK" && ($tree["LB"]["CLASS"] == "OPS" || $tree["LB"]["CLASS"] == "OPK")) {
            // on a un truc du genre ((a ou b) < 5), on le transforme en (a ou (b < 5))
            $t = $tree["LB"];
            $tree["LB"] = $t["RB"];
            $t["RB"] = $tree;
            $tree = $t;
        }
        if (isset($tree["LB"])) {
            $this->priority_opk($tree["LB"], $depth + 1);
        }if (isset($tree["RB"])) {
            $this->priority_opk($tree["RB"], $depth + 1);
        }
    }

    public function distrib_opk(&$tree, $depth = 0)
    {
        if ( ! $tree) {
            return;
        }

        if ($tree["CLASS"] == "OPK" && ($tree["RB"]["CLASS"] == "OPS")) {
            // on a un truc du genre (a = (5 ou 6)), on le transforme en ((a = 5) ou (a = 6))
            $tmp = array("CLASS"     => $tree["CLASS"],
                "NODETYPE"  => $tree["NODETYPE"],
                "VALUE"     => $tree["VALUE"],
                "PNUM"      => $tree["PNUM"],
                "LB"        => $tree["LB"],
                "RB"        => $tree["RB"]["RB"],
                "DEPTH"     => $tree["LB"]["DEPTH"]);
            $t = $tree["RB"];
            $tree["RB"] = $t["LB"];
            $t["LB"] = $tree;
            $t["RB"] = $tmp;
            $tree = $t;
        }
        if (isset($tree["LB"]))
            $this->distrib_opk($tree["LB"], $depth + 1);
        if (isset($tree["RB"]))
            $this->distrib_opk($tree["RB"], $depth + 1);
    }

    public function thesaurus2_apply(&$tree, $bid)
    {
        if ( ! $tree) {
            return;
        }

        if (($tree["CLASS"] == "SIMPLE" || $tree["CLASS"] == "QSIMPLE") && isset($tree["SREF"]) && isset($tree["SREF"]["TIDS"])) {
            $tids = array();
            foreach ($tree["SREF"]["TIDS"] as $tid) {
                if ($tid["bid"] == $bid)
                    $tids[] = $tid["pid"];
            }
            if (count($tids) >= 1) {
                /*
                  if (count($tids)==1) {
                  // on cherche un id simple, on utilisera la syntaxe sql 'like' (l'extension repérera elle méme la syntaxe car la value finit par '%')
                  $val = str_replace(".", "d", $tids[0]) . "d%";
                  $tree["VALUE"] = array($val);
                  } else {
                  // on cherche plusieurs id's, on utilisera la syntaxe 'regexp' (l'extension repérera elle méme la syntaxe car la value finit par '$'
                  $val = "";
                  foreach($tids as $tid)
                  $val .= ($val?"|":"") . "(" . str_replace(".", "d", $tid) . "d.*)";
                  $tree["VALUE"] = array("^" . $val);
                  }
                 */
                $tree["VALUE"] = array();
                foreach ($tids as $tid)
                    $tree["VALUE"][] = str_replace(".", "d", $tid) . "d%";;
            } else {
                // le mot n'est pas dans le thesaurus
            }
            /*
             */
        }
        if ($tree["CLASS"] == "OPS" || $tree["CLASS"] == "OPK") {
            $this->thesaurus2_apply($tree["LB"], $bid);
            $this->thesaurus2_apply($tree["RB"], $bid);
        }
    }

    // étend (ou remplace) la recherche sur les termes simples en recherche sur thesaurus
    // ex: (a et b)
    // full-text only :  ==> (a et b)
    // thesaurus only :  ==> ((th:a) et (th:b))
    // ft et thesaurus : ==> ((a ou (th:a)) et (b ou (th:b)))
    // RETOURNE l'arbre résultat sans modifier l'arbre d'origine
    public function extendThesaurusOnTerms(&$tree, $useFullText, $useThesaurus, $keepfuzzy)
    {
        $copy = $tree;
        $this->_extendThesaurusOnTerms($tree, $copy, $useFullText, $useThesaurus, $keepfuzzy, 0, "");

        $this->proposals["QRY"] = "<span id=\"thprop_q\">" . $this->_queryAsHTML($tree) . "</span>";

        return($copy);
    }

    public function _extendThesaurusOnTerms(&$tree, &$copy, $useFullText, $useThesaurus, $keepfuzzy, $depth, $path)
    {
        if ($depth == 0)
            $ret = $tree;
        if ( ! $useThesaurus) {
            return;  // full-text only : inchangé
        }

        if (($tree["CLASS"] == "SIMPLE" || $tree["CLASS"] == "QSIMPLE")) {
            if (isset($tree["CONTEXT"]))
                $copy = $this->_extendToThesaurus_Simple($tree, false, $keepfuzzy, $path);
            else
                $copy = $this->_extendToThesaurus_Simple($tree, $useFullText, $keepfuzzy, $path);
        } else {
            if ($tree["CLASS"] == "OPK" && $tree["NODETYPE"] == PHRASEA_OP_COLON) {
                // on a 'field:value'  , on traite 'value'
                $tree["RB"]["PATH"] = $copy["RB"]["PATH"] = $path . "R";
                if (isset($tree["RB"]["CONTEXT"]))
                    $copy["CONTEXT"] = $tree["CONTEXT"] = $tree["RB"]["CONTEXT"];
                else
                if ( ! $keepfuzzy)
                    $copy["CONTEXT"] = $tree["CONTEXT"] = "*";

                $copy["RB"]["SREF"] = &$tree["RB"];
            } else {
                $recursL = $recursR = false;
                if ($tree["CLASS"] == "OPS" && ($tree["NODETYPE"] == PHRASEA_OP_AND || $tree["NODETYPE"] == PHRASEA_OP_OR || $tree["NODETYPE"] == PHRASEA_OP_EXCEPT)) {
                    // on a une branche à gauche de 'ET', 'OU', 'SAUF'
                    $recursL = true;
                }
                if ($tree["CLASS"] == "OPS" && ($tree["NODETYPE"] == PHRASEA_OP_AND || $tree["NODETYPE"] == PHRASEA_OP_OR || $tree["NODETYPE"] == PHRASEA_OP_EXCEPT)) {
                    // on a une branche à droite de 'ET', 'OU', 'SAUF'
                    $recursR = true;
                }
                if ($recursL)
                    $this->_extendThesaurusOnTerms($tree["LB"], $copy["LB"], $useFullText, $useThesaurus, $keepfuzzy, $depth + 1, $path . "L");
                if ($recursR)
                    $this->_extendThesaurusOnTerms($tree["RB"], $copy["RB"], $useFullText, $useThesaurus, $keepfuzzy, $depth + 1, $path . "R");
            }
        }
    }

    // étend (ou remplace) un terme cherché en 'full-text' à une recherche thesaurus (champ non spécifié, tout le thésaurus = '*')
    // le contexte éventuel est rapporté à l'opérateur ':'
    // ex : a[k]   ==>   (a ou (TH :[k] a))
    public function _extendToThesaurus_Simple(&$simple, $keepFullText, $keepfuzzy, $path)
    {
        $simple["PATH"] = $path;
        $context = null;
        if (isset($simple["CONTEXT"])) {
            $context = $simple["CONTEXT"];
            // unset($simple["CONTEXT"]);
        }
        if ($keepFullText) {
            // on fait un OU entre la recherche ft et une recherche th
            $tmp = array("CLASS"    => "OPS",
                "NODETYPE" => PHRASEA_OP_OR,
                "VALUE"    => "OR",
                "PNUM"     => null,
                "DEPTH"    => $simple["DEPTH"],
                "LB"       => $simple,
                "RB"       => array("CLASS"    => "OPK",
                    "NODETYPE" => PHRASEA_OP_COLON,
                    "VALUE"    => ":",
                    // "CONTEXT"=>$context,
                    "PNUM"     => null,
                    "DEPTH"    => $simple["DEPTH"] + 1,
                    "LB"       => array("CLASS"    => "SIMPLE",
                        "NODETYPE" => PHRASEA_KEYLIST,
                        "VALUE"    => array("*"),
                        "DEPTH"                  => $simple["DEPTH"] + 2
                    ),
                    "RB"                     => $simple
                )
            );
            // on vire le contexte  du coté fulltext
            unset($tmp["LB"]["CONTEXT"]);
            // ajoute le contexte si nécéssaire
            if ($context !== null)
                $tmp["RB"]["CONTEXT"] = $context;
            else
            if ( ! $keepfuzzy)
                $tmp["RB"]["CONTEXT"] = "*";
            // corrige les profondeurs des 2 copies du 'simple' d'origine
            $tmp["LB"]["DEPTH"] += 1;
            $tmp["RB"]["RB"]["DEPTH"] += 2;
            // note une référence vers le terme d'origine
            $tmp["RB"]["RB"]["SREF"] = &$simple;
            $tmp["RB"]["RB"]["PATH"] = $path;
        } else {
            // on remplace le ft par du th
            $tmp = array("CLASS"    => "OPK",
                "NODETYPE" => PHRASEA_OP_COLON,
                "VALUE"    => ":",
                // "CONTEXT"=>$context,
                "PNUM"     => null,
                "DEPTH"    => $simple["DEPTH"] + 1,
                "LB"       => array("CLASS"    => "SIMPLE",
                    "NODETYPE" => PHRASEA_KEYLIST,
                    "VALUE"    => array("*"),
                    "DEPTH"            => $simple["DEPTH"] + 1
                ),
                "RB"               => $simple
            );
            // ajoute le contexte si nécéssaire
            if ($context !== null)
                $tmp["CONTEXT"] = $context;
            else
            if ( ! $keepfuzzy)
                $tmp["CONTEXT"] = "*";
            // corrige la profondeur de la copie du 'simple' d'origine
            $tmp["RB"]["DEPTH"] += 1;
            // note une référence vers le terme d'origine
            $tmp["RB"]["SREF"] = &$simple;
            $tmp["RB"]["PATH"] = $path;
        }

        return($tmp);
    }

    public function thesaurus2(&$tree, $bid, $name, &$domthe, $searchsynonyms = true, $depth = 0)
    {
        if ($this->debug)
            print("thesaurus2:\n\$tree=" . var_export($tree, true) . "\n");

        if ($depth == 0)
            $this->proposals["BASES"]["b$bid"] = array("BID"   => $bid, "NAME"  => $name, "TERMS" => array());

        if ( ! $tree) {
            return(0);
        }

        $ambigus = 0;
        if ($tree["CLASS"] == "OPK" && $tree["NODETYPE"] == PHRASEA_OP_COLON) {
//      $ambigus = $this->setTids($tree, $tree["RB"], $bid, $domthe, $searchsynonyms);
            $ambigus = $this->setTids($tree, $bid, $domthe, $searchsynonyms);
        } elseif ($tree["CLASS"] == "OPS" || $tree["CLASS"] == "OPK") {
            $ambigus += $this->thesaurus2($tree["LB"], $bid, $name, $domthe, $searchsynonyms, $depth + 1);
            $ambigus += $this->thesaurus2($tree["RB"], $bid, $name, $domthe, $searchsynonyms, $depth + 1);
        }

        return($ambigus);
    }

    public function propAsHTML(&$node, &$html, $path, $depth = 0)
    {
        global $parm;
        if ($depth > 0) {
            $tsy = array();
            $lngfound = "?";
            for ($n = $node->firstChild; $n; $n = $n->nextSibling) {
                if ($n->nodeName == "sy") {
                    $lng = $n->getAttribute("lng");
                    if ( ! array_key_exists($lng, $tsy))
                        $tsy[$lng] = array();
                    $zsy = array("v" => $n->getAttribute("v"), "w" => $n->getAttribute("w"), "k" => $n->getAttribute("k"));

                    if ($lngfound == "?" || ($lng == $this->lng && $lngfound != $lng)) {
                        $lngfound = $lng;
                        $syfound = $zsy;
                    } else {

                    }
                    $tsy[$lng][] = $zsy;
                }
            }
            $alt = "";
            foreach ($tsy as $lng => $tsy2) {
                foreach ($tsy2 as $sy) {
                    $alt .= $alt ? "\n" : "";
                    $alt .= "" . $lng . ": " . p4string::MakeString($sy["v"], "js");
                }
            }

            $this->proposals['QUERIES'][$syfound["w"]] = $syfound["w"];

            $thtml = $syfound["v"];
            $kjs = $syfound["k"] ? ("'" . p4string::MakeString($syfound["k"], "js") . "'") : "null";
            $wjs = "'" . p4string::MakeString($syfound["w"], "js") . "'";

            if ($node->getAttribute("term")) {
                $thtml = "<b>" . $thtml . "</b>";
                $node->removeAttribute("term");
            }

            $tab = str_repeat("\t", $depth);
            $html .= $tab . "<div style=\"position:relative; left:10px;\">\n";
            $html .= $tab . "\t<a title=\"" . $alt . "\" href=\"javascript:void();\" onclick=\"return(chgProp('" . $path . "', " . $wjs . ", " . $kjs . "));\">" . $thtml . "</a>\n";
        }

        $tsort = array();
        for ($n = $node->firstChild; $n; $n = $n->nextSibling) {
            if ($n->nodeType == XML_ELEMENT_NODE && $n->getAttribute("marked")) {  // only 'te' marked
                $lngfound = '?';
                $syfound = '?';
                for ($n2 = $n->firstChild; $n2; $n2 = $n2->nextSibling) {
                    if ($n2->nodeName == 'sy') {
                        $lng = $n2->getAttribute('lng');
                        if ($lngfound == "?" || ($lng == $this->lng && $lngfound != $lng)) {
                            $lngfound = $lng;
                            $syfound = $n2->getAttribute('w');
                        }
                    }
                }
                $n->removeAttribute("marked");
                for ($i = 0; array_key_exists($syfound . $i, $tsort) && $i < 9999; $i ++ )
                    ;
                $tsort[$syfound . $i] = $n;
            }
        }
        ksort($tsort);

        foreach ($tsort as $n) {
            $this->propAsHTML($n, $html, $path, $depth + 1);
        }

        if ($depth > 0)
            $html .= $tab . "</div>\n";
    }

    public function _queryAsHTML($tree, $depth = 0)
    {
        if ($depth == 0) {
            $ambiguites = array("n"    => 0, "refs" => array());
        }
        switch ($tree["CLASS"]) {
            case "SIMPLE":
            case "QSIMPLE":
                $w = is_array($tree["VALUE"]) ? implode(' ', $tree["VALUE"]) : $tree["VALUE"];
                if (isset($tree["PATH"])) {
                    $path = $tree["PATH"];
                    if (isset($tree["CONTEXT"]))
                        $w .= ' [' . $tree["CONTEXT"] . ']';
                    $txt = '<span id="thprop_a_' . $path . '">"' . $w . '"</span>';
                } else {
                    if (isset($tree["CONTEXT"]))
                        $w .= '[' . $tree["CONTEXT"] . ']';
                    if ($tree["CLASS"] == "QSIMPLE")
                        $txt = '"' . $w . '"';
                    else
                        $txt = $w;
                }

                return($txt);
                break;
            case "PHRASEA_KW_ALL":
                return($tree["VALUE"][0]);
                break;
            case "PHRASEA_KW_LAST":
                if ($tree["PNUM"] !== null) {
                    return("" . $tree["VALUE"][0] . "[" . $tree["PNUM"] . "]");
                } else {
                    return($tree["VALUE"][0]);
                }
                break;
            case "OPS":
            case "OPK":
                if (isset($tree["PNUM"])) {
                    return('(' . $this->_queryAsHTML($tree["LB"], $depth + 1) . ' ' . $tree["VALUE"] . '[' . $tree["PNUM"] . '] ' . $this->_queryAsHTML($tree["RB"], $depth + 1) . ')');
                } else {
                    return('(' . $this->_queryAsHTML($tree["LB"], $depth + 1) . ' ' . $tree["VALUE"] . ' ' . $this->_queryAsHTML($tree["RB"], $depth + 1) . ')');
                }
                break;
        }
    }

    public function setTids(&$tree, $bid, &$domthe, $searchsynonyms)
    {
        if ($this->debug)
            print("============================ setTids:\n\$tree=" . var_export($tree, true) . "\n");

        // $this->proposals["BASES"]["b$bid"] = array("BID"=>$bid, "TERMS"=>array());

        $ambigus = 0;
        if (is_array($w = $tree["RB"]["VALUE"]))
            $t = $w = implode(" ", $w);

        if (isset($tree["CONTEXT"])) {
            if ( ! $tree["CONTEXT"]) {
                $x0 = "@w=\"" . $w . "\" and not(@k)";
            } else {
                if ($tree["CONTEXT"] == "*") {
                    $x0 = "@w=\"" . $w . "\"";
                } else {
                    $x0 = "@w=\"" . $w . "\" and @k=\"" . $tree["CONTEXT"] . "\"";
                    $t .= " (" . $tree["CONTEXT"] . ")";
                }
            }
        } else {
            $x0 = "@w=\"" . $w . "\"";
        }

        $x = "/thesaurus//sy[" . $x0 . "]";

        if ($this->debug)
            printf("searching thesaurus with xpath='%s'<br/>\n", $x);

        $dxp = new DOMXPath($domthe);
        $nodes = $dxp->query($x);

        if ( ! isset($tree["RB"]["SREF"]["TIDS"]))
            $tree["RB"]["SREF"]["TIDS"] = array();
        if ($nodes->length >= 1) {
            if ($nodes->length == 1) {
                // on cherche un id simple, on utilisera la syntaxe sql 'like' (l'extension repérera elle méme la syntaxe car la value finira par '%')
                $this->addtoTIDS($tree["RB"], $bid, $nodes->item(0));
                // $this->thesaurusDOMNodes[] = $nodes->item(0);
            } else {
                // on cherche plusieurs id's, on utilisera la syntaxe 'regexp' (l'extension repérera elle meme la syntaxe car la value finira par '$')
                $val = "";
                foreach ($nodes as $node) {
                    if ( ! isset($tree["CONTEXT"]))
                        $ambigus ++;
                    $this->addtoTIDS($tree["RB"], $bid, $node);
                }
            }
            $path = $tree["RB"]["SREF"]["PATH"];
            $prophtml = "";
            $this->propAsHTML($domthe->documentElement, $prophtml, $path);
            $this->proposals["BASES"]["b$bid"]["TERMS"][$path]["HTML"] = $prophtml;
        } else {
            // le mot n'est pas dans le thesaurus
        }

        return($ambigus);
    }
    /*
      function dead_setTids(&$tree, &$simple, $bid, &$domthe, $searchsynonyms)
      {
      // if($this->debug)
      print("setTids:\n\$tree=" . var_export($tree, true) . "\n");

      $ambigus = 0;
      if(is_array($w = $simple["VALUE"]))
      $t = $w = implode(" ", $w);

      if (isset($tree["CONTEXT"])) {
      if (!$tree["CONTEXT"]) {
      $x0 = "@w=\"" . $w ."\" and not(@k)";
      } else {
      if ($tree["CONTEXT"]=="*") {
      $x0 = "@w=\"" . $w ."\"";
      } else {
      $x0 = "@w=\"" . $w ."\" and @k=\"" . $tree["CONTEXT"] . "\"";
      $t .= " (" . $tree["CONTEXT"] . ")";
      }
      }
      } else {
      $x0 = "@w=\"" . $w ."\"";
      }

      $x = "/thesaurus//sy[" . $x0 ."]";

      if($this->debug)
      printf("searching thesaurus with xpath='%s'<br/>\n", $x);

      $dxp = new DOMXPath($domthe);
      $nodes = $dxp->query($x);

      if(!isset($tree["RB"]["SREF"]["TIDS"]))
      $tree["RB"]["SREF"]["TIDS"] = array();
      if ($nodes->length >= 1) {
      if ($nodes->length == 1) {
      // on cherche un id simple, on utilisera la syntaxe sql 'like' (l'extension repérera elle méme la syntaxe car la value finira par '%')
      $this->addtoTIDS($tree["RB"], $bid, $nodes->item(0));
      // $this->thesaurusDOMNodes[] = $nodes->item(0);
      } else {
      // on cherche plusieurs id's, on utilisera la syntaxe 'regexp' (l'extension repérera elle meme la syntaxe car la value finira par '$')
      $val = "";
      foreach ($nodes as $node) {
      if(!isset($tree["CONTEXT"]))
      $ambigus++;
      $this->addtoTIDS($tree["RB"], $bid, $node);
      }
      }
      $path = $tree["RB"]["SREF"]["PATH"];
      $prophtml = "";
      $this->propAsHTML($domthe->documentElement, $prophtml, $path);
      $this->proposals["TERMS"][$path]["HTML"] = $prophtml;
      } else {
      // le mot n'est pas dans le thesaurus
      }

      return($ambigus);
      }
     */

    public function containsColonOperator(&$tree)
    {
        if ( ! $tree) {
            return(false);
        }
        if ($tree["CLASS"] == "OPK" && $tree["NODETYPE"] == PHRASEA_OP_COLON && ($tree["RB"]["CLASS"] == "SIMPLE" || $tree["RB"]["CLASS"] == "QSIMPLE")) {
            return(true);
        }
        $ret = false;
        if ($tree["CLASS"] == "OPS" || $tree["CLASS"] == "OPK") {
            $ret |= $this->containsColonOperator($tree["LB"]);
            $ret |= $this->containsColonOperator($tree["RB"]);
        }

        return($ret);
    }

    public function addtoTIDS(&$extendednode, $bid, $DOMnode) // ajoute un tid en évitant les doublons
    {
        $id = $DOMnode->getAttribute("id");
        $pid = $DOMnode->parentNode->getAttribute("id");
        $lng = $DOMnode->getAttribute("lng");
        $w = $DOMnode->getAttribute("w");
        $k = $DOMnode->getAttribute("k");
        $p = $DOMnode->parentNode->getAttribute("v"); // le terme général (pére) du terme recherché : utile pour la levée d'ambiguité

        $path = $extendednode["SREF"]["PATH"];
        if ($this->debug)
            printf("found node id='%s', v='%s' w='%s', k='%s', p='%s' for node-path=%s \n", $id, $DOMnode->getAttribute("v"), $w, $k, $p, $path);

        if ( ! $k)
            $k = null;

        $found = false;
        foreach ($extendednode["SREF"]["TIDS"] as $ztid) {
            if ($ztid["bid"] != $bid)
                continue;
            if ($ztid["pid"] == $pid) {
                $found = true;
            } else {
//        if($ztid["w"]==$w && $ztid["k"]==$k && $ztid["lng"]==$lng)
//        {
//          // FATAL : il y a un doublon réel dans le thesaurus de cette base (méme terme, méme contexte)
//          //    printf("<font color='red'>FATAL doublon on base %d (%s[%s])</font>\n", $bid, $w, $k);
//          $found = true;
//          break;
//        }
            }
        }
        if ( ! $found)
            $extendednode["SREF"]["TIDS"][] = array("bid" => $bid, "pid" => $pid, "id"  => $id, "w"   => $w, "k"   => $k, "lng" => $lng, "p"   => $p);

        // on liste les propositions de thésaurus pour ce node (dans l'arbre simple)
        if ( ! isset($this->proposals["BASES"]["b$bid"]["TERMS"][$path])) {
            //  $this->proposals["TERMS"][$path] = array("TERM"=>implode(" ", $extendednode["VALUE"]), "PROPOSALS"=>array());
            $term = implode(" ", $extendednode["VALUE"]);
            if (isset($extendednode["CONTEXT"]) && $extendednode["CONTEXT"]) {
                $term .= " (" . $extendednode["CONTEXT"] . ")";
            }
            $this->proposals["BASES"]["b$bid"]["TERMS"][$path] = array("TERM" => $term); // , "PROPOSALS"=>array() ); //, "PROPOSALS_TREE"=>new DOMDocument("1.0", "UTF-8"));
        }
// printf("<%s id='%s'><br/>\n", $DOMnode->tagName, $DOMnode->getAttribute("id"));
//    printf("<b>found node &lt;%s id='%s' w='%s' k='%s'></b><br/>\n", $DOMnode->nodeName, $DOMnode->getAttribute('id'), $DOMnode->getAttribute('w'), $DOMnode->getAttribute('k'));
        // on marque le terme principal
        $DOMnode->parentNode->setAttribute("term", "1");
        // on commence par marquer les fils directs. rappel:$DOMnode pointe sur un sy
        for ($node = $DOMnode->parentNode->firstChild; $node; $node = $node->nextSibling) {
            if ($node->nodeName == "te") {
                $node->setAttribute("marked", "1");
            }
        }
        // puis par remonter au père
        for ($node = $DOMnode->parentNode; $node && $node->nodeType == XML_ELEMENT_NODE && $node->parentNode; $node = $node->parentNode) {
            $id = $node->getAttribute("id");
            if ( ! $id)
                break; // on a dépassé la racine du thésaurus
            $node->setAttribute("marked", "1");
        }
    }

    public function astext_ambigu($tree, &$ambiguites, $mouseCallback = "void", $depth = 0)
    {
        if ($depth == 0) {
            $ambiguites = array("n"    => 0, "refs" => array());
        }
        switch ($tree["CLASS"]) {
            case "SIMPLE":
            case "QSIMPLE":
                $prelink = $postlink = "";
                $w = is_array($tree["VALUE"]) ? implode(" ", $tree["VALUE"]) : $tree["VALUE"];
                $tab = "\n" . str_repeat("\t", $depth);
                if (isset($tree["TIDS"]) && count($tree["TIDS"]) > 1) {
                    $ambiguites["refs"][$n = $ambiguites["n"]] = &$tree;
                    $txt = $tab . "<b><span onmouseover=\"return(" . $mouseCallback . "(event, $n));\" onmouseout=\"return(" . $mouseCallback . "(event, $n));\" id=\"thamb_a_" . $ambiguites["n"] . "\">";
                    $txt .= $tab . "\t\"" . $w . "";
                    $txt .= $tab . "\t<span id='thamb_w_" . $ambiguites["n"] . "'></span>\"";
                    $txt .= $tab . "</span></b>\n";
                    $ambiguites["n"] ++;
                } else {
                    if (isset($tree["CONTEXT"]))
                        $w .= "[" . $tree["CONTEXT"] . "]";
                    if ($tree["CLASS"] == "QSIMPLE")
                        $txt = $tab . "\"" . $w . "\"\n";
                    else
                        $txt = $tab . "" . $w . "\n";
                }

                return($txt);
                break;
            case "PHRASEA_KW_ALL":
                return($tree["VALUE"][0]);
                break;
            case "PHRASEA_KW_LAST":
                if ($tree["PNUM"] !== null) {
                    return("" . $tree["VALUE"][0] . "[" . $tree["PNUM"] . "]");
                } else {
                    return($tree["VALUE"][0]);
                }
                break;
            case "OPS":
            case "OPK":
                if (isset($tree["PNUM"])) {
                    return("(" . $this->astext_ambigu($tree["LB"], $ambiguites, $mouseCallback, $depth + 1) . " " . $tree["VALUE"] . "[" . $tree["PNUM"] . "] " . $this->astext_ambigu($tree["RB"], $ambiguites, $mouseCallback, $depth + 1) . ")");
                } else {
                    return("(" . $this->astext_ambigu($tree["LB"], $ambiguites, $mouseCallback, $depth + 1) . " " . $tree["VALUE"] . " " . $this->astext_ambigu($tree["RB"], $ambiguites, $mouseCallback, $depth + 1) . ")");
                }
                break;
        }
    }

    public function get_ambigu(&$tree, $mouseCallback = "void", $depth = 0)
    {
        if ( ! $tree) {
            return("");
        }

        unset($tree["DEPTH"]);
        if ($tree["CLASS"] == "OPS" || $tree["CLASS"] == "OPK") {
            $this->get_ambigu($tree["LB"], $mouseCallback, $depth + 1);
            $this->get_ambigu($tree["RB"], $mouseCallback, $depth + 1);
        } else {

        }
        if ($depth == 0) {
            $t_ambiguites = array();
            $r = ($this->astext_ambigu($tree, $t_ambiguites, $mouseCallback));
            $t_ambiguites["query"] = $r;

            return($t_ambiguites);
        }
    }

    public function set_default(&$tree, &$emptyw, $depth = 0)
    {
        if ( ! $tree) {
            return(true);
        }

        if ($tree["CLASS"] == "OPS" || $tree["CLASS"] == "OPK") {
            if ($tree["CLASS"] == "OPS") {
                if ( ! $this->set_default($tree["LB"], $emptyw, $depth + 1)) {
                    return(false);
                }
                if ( ! $this->set_default($tree["RB"], $emptyw, $depth + 1)) {
                    return(false);
                }
            } else { // OPK !
                // jy 20041223 : ne pas appliquer d'op. par def. derriere un op arith.
                // ex : "d < 1/2/2003" : grouper la liste "1","2","2004" en "mot" unique
                if ( ! $tree["LB"] || ($tree["LB"]["CLASS"] != "SIMPLE" && $tree["LB"]["CLASS"] != "QSIMPLE") || (is_array($tree["LB"]["VALUE"]) && count($tree["LB"]["VALUE"]) != 1)) {
                    // un op. arith. doit étre précédé d'un seul nom de champ
                    if ($this->errmsg != "")
                        $this->errmsg .= sprintf("\\n");
                    $this->errmsg .= sprintf(_('qparser::Formulation incorrecte, un nom de champs est attendu avant l operateur %s'), $tree["VALUE"]);

                    return(false);
                }
                if ( ! $tree["RB"] || ($tree["RB"]["CLASS"] != "SIMPLE" && $tree["RB"]["CLASS"] != "QSIMPLE")) {
                    // un op. arith. doit étre suivi d'une valeur
                    if ($this->errmsg != "")
                        $this->errmsg .= sprintf("\\n");
                    $this->errmsg .= sprintf(_('qparser::Formulation incorrecte, une valeur est attendue apres l operateur %s'), $tree["VALUE"]);

                    return(false);
                }
                if (is_array($tree["RB"]["VALUE"])) {
                    $lw = "";
                    foreach ($tree["RB"]["VALUE"] as $w)
                        $lw .= ( $lw == "" ? "" : " ") . $w;
                    $tree["RB"]["VALUE"] = $lw;
                }
            }

            /** gestion des branches null
             *   a revoir car ca ppete pas d'erreur mais corrige automatiquement
             * ** */
            if ( ! isset($tree["RB"]))
                $tree = $tree["LB"];
            else
            if ( ! isset($tree["LB"]))
                $tree = $tree["RB"];
        } else {
            if (($tree["CLASS"] == "SIMPLE" || $tree["CLASS"] == "QSIMPLE")) {
                if (is_array($tree["VALUE"])) {
                    $treetmp = null;
                    $pnum = 0;
                    for ($i = 0; $i < count($tree["VALUE"]); $i ++ ) {
                        // gestion mot vide
                        if (isset($emptyw[$tree["VALUE"][$i]]) || $tree["VALUE"][$i] == "?" || $tree["VALUE"][$i] == "*") {
                            // on a forcé les '?' ou '*' isolés comme des mots vides
                            $pnum ++;
                        } else {
                            if ($treetmp == null) {
                                $treetmp = array("CLASS"    => $tree["CLASS"],
                                    "NODETYPE" => $tree["NODETYPE"],
                                    "VALUE"    => $tree["VALUE"][$i],
                                    "PNUM"     => $tree["PNUM"],
                                    "DEPTH"    => $tree["DEPTH"]);
                                $pnum = 0;
                            } else {
                                $dop = $tree["CLASS"] == "QSIMPLE" ? $this->quoted_defaultop : $this->defaultop;
                                $treetmp = array("CLASS"    => "OPS",
                                    "VALUE"    => $dop["VALUE"],
                                    "NODETYPE" => $dop["NODETYPE"],
                                    "PNUM"     => $pnum, // peut-être écrasé par defaultop
                                    "DEPTH"    => $depth,
                                    "LB"       => $treetmp,
                                    "RB"       => array("CLASS"          => $tree["CLASS"],
                                        "NODETYPE"       => $tree["NODETYPE"],
                                        "VALUE"          => $tree["VALUE"][$i],
                                        "PNUM"           => $tree["PNUM"],
                                        "DEPTH"          => $tree["DEPTH"])
                                );
                                if (array_key_exists("PNUM", $dop))
                                    $treetmp["PNUM"] = $dop["PNUM"];
                                $pnum = 0;
                            }
                        }
                    }
                    $tree = $treetmp;
                }
            }
        }

        return(true);
    }

    public function factor_or(&$tree)
    {
        do
            $n = $this->factor_or2($tree); while ($n > 0);
    }

    public function factor_or2(&$tree, $depth = 0)
    {
        $nmodif = 0;
        if ($tree["CLASS"] == "OPS" || $tree["CLASS"] == "OPK") {
            if ($tree["NODETYPE"] == PHRASEA_OP_OR && ($tree["LB"]["CLASS"] == "SIMPLE" || $tree["LB"]["CLASS"] == "QSIMPLE") && ($tree["RB"]["CLASS"] == "SIMPLE" || $tree["RB"]["CLASS"] == "QSIMPLE")) {
                $tree["CLASS"] = "SIMPLE";
                $tree["NODETYPE"] = PHRASEA_KEYLIST;
                $tree["VALUE"] = is_array($tree["LB"]["VALUE"]) ? $tree["LB"]["VALUE"] : array($tree["LB"]["VALUE"]);
                if (is_array($tree["RB"]["VALUE"])) {
                    foreach ($tree["RB"]["VALUE"] as $v)
                        $tree["VALUE"][] = $v;
                } else
                    $tree["VALUE"][] = $tree["RB"]["VALUE"];
                unset($tree["LB"]);
                unset($tree["RB"]);
                unset($tree["PNUM"]);
                $nmodif ++;
            } else {
                $nmodif += $this->factor_or2($tree["LB"], $depth + 1);
                $nmodif += $this->factor_or2($tree["RB"], $depth + 1);
            }
        }

        return($nmodif);
    }

    public function setNumValue(&$tree, SimpleXMLElement $sxml_struct, $depth = 0)
    {
        if ($tree["CLASS"] == "OPK") {
            if (isset($tree["RB"]) && ($tree["RB"]["CLASS"] == "SIMPLE" || $tree["RB"]["CLASS"] == "QSIMPLE") && ($tree["LB"]["CLASS"] == "SIMPLE" || $tree["LB"]["CLASS"] == "QSIMPLE")) {
                $z = $sxml_struct->xpath('/record/description');
                if ($z && is_array($z)) {
                    foreach ($z[0] as $ki => $vi) {
                        $champ = null;
                        if (is_array($tree["LB"]["VALUE"]))
                            $champ = $tree["LB"]["VALUE"][0];
                        else
                            $champ = $tree["LB"]["VALUE"];
                        if ($champ && strtoupper($ki) == strtoupper($champ)) {
                            foreach ($vi->attributes() as $propname => $val) {
                                if (strtoupper($propname) == strtoupper("type")) {
                                    if ($tree["NODETYPE"] == PHRASEA_OP_EQUAL) // cas particulier du "=" sur une date
                                        $this->changeNodeEquals($tree, $val);
                                    else
                                        $this->setNumValue2($tree["RB"], $val);
                                }
                            }
                        }
                    }
                }
            }
        }
        if (isset($tree["LB"]))
            $this->setNumValue($tree["LB"], $sxml_struct, $depth + 1);
        if (isset($tree["RB"]))
            $this->setNumValue($tree["RB"], $sxml_struct, $depth + 1);
    }

    public function changeNodeEquals(&$branch, $type)
    {
        if (strtoupper($type) == strtoupper("Date")) {
            $branch = $this->changeNodeEquals2($branch);
        }
    }

    public function changeNodeEquals2($oneBranch)
    {
        ## creation branche gauche avec ">="
// print("changeNodeEquals2\n");
// print("creation branche gauche ( '>=' ) \n");
        $newTreeLB = array("CLASS"    => "OPK",
            "VALUE"    => ">=",
            "NODETYPE" => PHRASEA_OP_GEQT,
            "PNUM"     => NULL,
            "DEPTH"    => 0,
            "LB"       => $oneBranch["LB"],
            "RB"       => array("CLASS"    => "SIMPLE",
                "VALUE"    => $this->isoDate($oneBranch["RB"]["VALUE"], false),
                "NODETYPE" => PHRASEA_KEYLIST,
                "PNUM"     => NULL,
                "DEPTH"    => 0)
        );

        $newTreeRB = array("CLASS"    => "OPK",
            "VALUE"    => "<=",
            "NODETYPE" => PHRASEA_OP_LEQT,
            "PNUM"     => NULL,
            "DEPTH"    => 0,
            "LB"       => $oneBranch["LB"],
            "RB"       => array("CLASS"    => "SIMPLE",
                "VALUE"    => $this->isoDate($oneBranch["RB"]["VALUE"], true),
                "NODETYPE" => PHRASEA_KEYLIST,
                "PNUM"     => NULL,
                "DEPTH"    => 0)
        );
// print("fin creation branche droite avec '<=' \n");
        ## fin creation branche droite ( "<=" )

        $tree = array("CLASS"    => "OPS",
            "VALUE"    => "et",
            "NODETYPE" => PHRASEA_OP_AND,
            "PNUM"     => NULL,
            "DEPTH"    => 0,
            "LB"       => $newTreeLB,
            "RB"       => $newTreeRB);


        return $tree;
    }

    public function setNumValue2(&$branch, $type)
    {
        if (strtoupper($type) == strtoupper("Date")) {
            $dateEnIso = $this->isoDate($branch["VALUE"]);
            $branch["VALUE"] = $dateEnIso;
        }
    }

    public function isoDate($onedate, $max = false)
    {
        $v_y = "1900";
        $v_m = "01";
        $v_d = "01";

        $v_h = $v_minutes = $v_s = "00";
        if ($max) {
            $v_h = $v_minutes = $v_s = "99";
        }
        $tmp = $onedate;

        if ( ! is_array($tmp))
            $tmp = explode(" ", $tmp);

        switch (sizeof($tmp)) {
            // on a une date complete séparé avec des espaces, slash ou tiret
            case 3 :
                if (strlen($tmp[0]) == 4) {
                    $v_y = $tmp[0];
                    $v_m = $tmp[1];
                    $v_d = $tmp[2];
                    // on a l'année en premier, on suppose alors que c'est de la forme YYYY MM DD
                } elseif (strlen($tmp[2]) == 4) {
                    // on a l'année en dernier, on suppose alors que c'est de la forme  DD MM YYYY
                    $v_y = $tmp[2];
                    $v_m = $tmp[1];
                    $v_d = $tmp[0];
                } else {
                    // l'année est sur un 2 chiffre et pas 4
                    // ca fou la zone

                    $v_d = $tmp[0];
                    $v_m = $tmp[1];
                    if ($tmp[2] < 20)
                        $v_y = "20" . $tmp[2];
                    else
                        $v_y = "19" . $tmp[2];
                }
                break;

            case 2 :
                //   On supposerait n'avoir que le mois et l'année
                if (strlen($tmp[0]) == 4) {
                    $v_y = $tmp[0];
                    $v_m = $tmp[1];
                    // on a l'année en premier, on suppose alors que c'est de la forme YYYY MM DD
                    if ($max)
                        $v_d = "99";
                    else
                        $v_d = "00";
                } elseif (strlen($tmp[1]) == 4) {
                    // on a l'année en premier, on suppose alors que c'est de la forme  DD MM YYYY
                    $v_y = $tmp[1];
                    $v_m = $tmp[0];
                    if ($max)
                        $v_d = "99";
                    else
                        $v_d = "00";
                } else {
                    // on a l'anné sur 2 chiffres
                    if ($tmp[1] < 20)
                        $v_y = "20" . $tmp[1];
                    else
                        $v_y = "19" . $tmp[1];
                    $v_m = $tmp[0];
                    if ($max)
                        $v_d = "99";
                    else
                        $v_d = "00";
                }
                break;


            // lé ca devient la zone pour savoir si on a que l'année ou si c'est une date sans espaces,slash ou tiret
            case 1 :
                switch (strlen($tmp[0])) {
                    case 14 :
                        // date iso YYYYMMDDHHMMSS
                        $v_y = substr($tmp[0], 0, 4);
                        $v_m = substr($tmp[0], 4, 2);
                        $v_d = substr($tmp[0], 6, 2);
                        $v_h = substr($tmp[0], 8, 2);
                        $v_minutes = substr($tmp[0], 10, 2);
                        $v_s = substr($tmp[0], 12, 2);
                        break;
                    case 8 :
                        // date iso YYYYMMDD
                        $v_y = substr($tmp[0], 0, 4);
                        $v_m = substr($tmp[0], 4, 2);
                        $v_d = substr($tmp[0], 6, 2);
                        break;
                    case 6 :
                        // date iso YYYYMM
                        $v_y = substr($tmp[0], 0, 4);
                        $v_m = substr($tmp[0], 4, 2);
                        if ($max)
                            $v_d = "99";
                        else
                            $v_d = "00";
                        break;
                    case 4 :
                        // date iso YYYY
                        $v_y = $tmp[0];

                        if ($max)
                            $v_m = "99";
                        else
                            $v_m = "00";

                        if ($max)
                            $v_d = "99";
                        else
                            $v_d = "00";
                        break;
                    case 2 :
                        // date iso YY
                        if ($tmp[0] < 20)
                            $v_y = "20" . $tmp[0];
                        else
                            $v_y = "19" . $tmp[0];

                        if ($max)
                            $v_m = "99";
                        else
                            $v_m = "00";

                        if ($max)
                            $v_d = "99";
                        else
                            $v_d = "00";
                        break;
                }



                break;
        }

        return("" . $v_y . $v_m . $v_d . $v_h . $v_minutes . $v_s);
    }

    public function distrib_in(&$tree, $depth = 0)
    {
        $opdistrib = array(PHRASEA_OP_AND, PHRASEA_OP_OR, PHRASEA_OP_EXCEPT, PHRASEA_OP_NEAR, PHRASEA_OP_BEFORE, PHRASEA_OP_AFTER); // ces opérateurs sont 'distribuables' autour d'un 'IN'

        if ($tree["CLASS"] == "OPS" || $tree["CLASS"] == "OPK") {
            if ($tree["NODETYPE"] == PHRASEA_OP_IN || $tree["CLASS"] == "OPK") {
                if ($tree["LB"]["CLASS"] == "OPK") {
                    // on a un truc du genre '(t1 = t2) dans t3'
                    // ... on ne fait rien
                }
                if ($tree["LB"]["CLASS"] == "OPS" && in_array($tree["LB"]["NODETYPE"], $opdistrib)) {
                    // on a un truc du genre '(t1 op t2) {dans|=} t3', on distribue le dans é t1 et t2
                    // ==> ((t1 dans t3) op (t2 dans t3))
                    $m_v = $tree["VALUE"];
                    $m_t = $tree["CLASS"];
                    $m_o = $tree["NODETYPE"];
                    $m_n = $tree["PNUM"];

                    $tree["CLASS"] = $tree["LB"]["CLASS"];
                    $tree["NODETYPE"] = $tree["LB"]["NODETYPE"];
                    $tree["VALUE"] = $tree["LB"]["VALUE"];
                    $tree["PNUM"] = $tree["LB"]["PNUM"];

                    $tree["LB"]["CLASS"] = $m_t;
                    $tree["LB"]["NODETYPE"] = $m_o;
                    $tree["LB"]["VALUE"] = $m_v;
                    $tree["LB"]["PNUM"] = $m_n;

                    $tree["RB"] = array("CLASS"    => $m_t,
                        "NODETYPE" => $m_o,
                        "VALUE"    => $m_v,
                        "PNUM"     => $m_n,
                        "LB"       => $tree["LB"]["RB"],
                        "RB"       => $tree["RB"]);

                    $tree["LB"]["RB"] = $tree["RB"]["RB"];
                    // return;
                }


                if ($tree["RB"]["CLASS"] == "OPS" && in_array($tree["RB"]["NODETYPE"], $opdistrib)) {

                    // on a un truc du genre 't1 {dans|=} (t2 op t3)', on distribue le dans é t2 et t3
                    // ==> ((t1 dans t2) ou (t1 dans t3))
                    $m_v = $tree["VALUE"];
                    $m_t = $tree["CLASS"];
                    $m_o = $tree["NODETYPE"];
                    $m_n = $tree["PNUM"];

                    $tree["CLASS"] = $tree["RB"]["CLASS"];
                    $tree["NODETYPE"] = $tree["RB"]["NODETYPE"];
                    $tree["VALUE"] = $tree["RB"]["VALUE"];
                    $tree["PNUM"] = $tree["RB"]["PNUM"];

                    $tree["RB"]["CLASS"] = $m_t;
                    $tree["RB"]["NODETYPE"] = $m_o;
                    $tree["RB"]["VALUE"] = $m_v;
                    $tree["RB"]["PNUM"] = $m_n;

                    $tree["LB"] = array("CLASS"    => $m_t,
                        "NODETYPE" => $m_o,
                        "VALUE"    => $m_v,
                        "PNUM"     => $m_n,
                        "LB"       => $tree["LB"],
                        "RB"       => $tree["RB"]["LB"]);

                    $tree["RB"]["LB"] = $tree["LB"]["LB"];
                }
            }
            $this->distrib_in($tree["LB"], $depth + 1);
            $this->distrib_in($tree["RB"], $depth + 1);
        }
    }

    public function makequery($tree)
    {
        $a = array($tree["NODETYPE"]);
        switch ($tree["CLASS"]) {
            case "PHRASEA_KW_LAST":
                if ($tree["PNUM"] !== NULL)
                    $a[] = $tree["PNUM"];
                break;
            case "PHRASEA_KW_ALL":
                break;
            case "SIMPLE":
            case "QSIMPLE":
                // pas de tid, c'est un terme normal
                if (is_array($tree["VALUE"])) {
                    foreach ($tree["VALUE"] as $k => $v)
                        $a[] = $v;
                } else {
                    $a[] = $tree["VALUE"];
                }
                break;
            case "OPK":
                if ($tree["LB"] !== NULL)
                    $a[] = $this->makequery($tree["LB"]);
                if ($tree["RB"] !== NULL)
                    $a[] = $this->makequery($tree["RB"]);
                break;
            case "OPS":
                if ($tree["PNUM"] !== NULL)
                    $a[] = intval($tree["PNUM"]);
                if ($tree["LB"] !== NULL)
                    $a[] = $this->makequery($tree["LB"]);
                if ($tree["RB"] !== NULL)
                    $a[] = $this->makequery($tree["RB"]);
                break;
        }

        return($a);
    }

    public function maketree($depth, $inquote = false)
    {
//    printf("<!-- PARSING $depth  -->\n\n");
        $tree = null;
        while ($t = $this->nexttoken($inquote)) {
            if ($this->debug)
                printf("got token %s of class %s\n", $t["VALUE"], $t["CLASS"]);
            switch ($t["CLASS"]) {
                case "TOK_RP":
                    if ($inquote) {
                        // quand on est entre guillements les tokens perdent leur signification
                        $tree = $this->addtotree($tree, $t, $depth, $inquote);
                        if ( ! $tree) {
                            return(null);
                        }
                    } else {
                        if ($depth <= 0) {  // ')' : retour de récursivité
                            if ($this->errmsg != "")
                                $this->errmsg .= sprintf("\\n");
                            $this->errmsg .= _('qparser:: erreur : trop de parentheses fermantes');

                            return(null);
                        }

                        return($tree);
                    }
                    break;
                case "TOK_LP":
                    if ($inquote) {
                        // quand on est entre guillements les tokens perdent leur signification
                        $tree = $this->addtotree($tree, $t, $depth, $inquote);
                        if ( ! $tree) {
                            return(null);
                        }
                    } else {  // '(' : appel récursif
                        if ( ! $tree)
                            $tree = $this->maketree($depth + 1);
                        else {
                            if (($tree["CLASS"] == "OPS" || $tree["CLASS"] == "OPK") && $tree["RB"] == null) {
                                $tree["RB"] = $this->maketree($depth + 1);
                                if ( ! $tree["RB"])
                                    $tree = null;
                            } else {
                                // ici on applique l'opérateur par défaut
                                $tree = array("CLASS"    => "OPS",
                                    "VALUE"    => $this->defaultop["VALUE"],
                                    "NODETYPE" => $this->defaultop["NODETYPE"],
                                    "PNUM"     => $this->defaultop["PNUM"],
                                    "DEPTH"    => $depth,
                                    "LB"       => $tree,
                                    "RB"       => $this->maketree($depth + 1));
                            }
                        }
                        if ( ! $tree) {
                            return(null);
                        }
                    }
                    break;
                case "TOK_VOID":
                    // ce token est entre guillemets : on le saute
                    break;
                case "TOK_QUOTE":
                    // une expr entre guillemets est 'comme entre parenthéses',
                    //  sinon "a b" OU "x y" -> (((a B0 b) OU x) B0 y) au lieu de
                    //        "a b" OU "x y" -> ((a B0 b) OU (x B0 y))
                    if ($inquote) {
                        if ($this->debug) {
                            print("CLOSING QUOTE!\n");
                        }
                        // fermeture des guillemets -> retour de récursivité
                        if ($depth <= 0) {  // ')' : retour de récursivité
                            print("\nguillemets fermants en trop<br>");

                            return(null);
                        }

                        return($tree);
                    } else {
                        if ($this->debug) {
                            print("OPENING QUOTE!<br>");
                        }
                        // ouverture des guillemets -> récursivité
                        if ( ! $tree)
                            $tree = $this->maketree($depth + 1, true);
                        else {
                            if (($tree["CLASS"] == "OPS" || $tree["CLASS"] == "OPK") && $tree["RB"] == null) {
                                $tree["RB"] = $this->maketree($depth + 1, true);
                                if ( ! $tree["RB"])
                                    $tree = null;
                            } else {
                                // ici on applique l'opérateur par défaut
                                $tree = array("CLASS"    => "OPS",
                                    "VALUE"    => $this->defaultop["VALUE"],
                                    "NODETYPE" => $this->defaultop["NODETYPE"],
                                    "PNUM"     => $this->defaultop["PNUM"],
                                    "DEPTH"    => $depth,
                                    "LB"       => $tree,
                                    "RB"       => $this->maketree($depth + 1, true));
                            }
                        }
                        if ( ! $tree) {
                            return(null);
                        }
                    }
                    break;
                default:
                    $tree = $this->addtotree($tree, $t, $depth, $inquote);
                    if ($this->debug) {
                        print("---- après addtotree ----\n");
                        var_dump($tree);
                        print("-------------------------\n");
                    }
                    if ( ! $tree) {
                        return(null);
                    }
                    break;
            }
        }
        if (($tree["CLASS"] == "OPS" || $tree["CLASS"] == "OPK") && $tree["RB"] == null) {
            if ($this->errmsg != "")
                $this->errmsg .= sprintf("\\n");
            $this->errmsg .= sprintf(_('qparser::Formulation incorrecte, une valeur est attendu apres %s'), $tree["VALUE"]);
            $tree = $tree["LB"];
        }

        return($tree);
    }

    public function addtotree($tree, $t, $depth, $inquote)
    {
        if ($this->debug) {
            printf("addtotree({tree}, \$t[CLASS]='%s', \$t[VALUE]='%s', \$depth=%d, inquote=%s)\n", $t["CLASS"], $t["VALUE"], $depth, $inquote ? "true" : "false");
            print("---- avant addtotree ----\n");
            var_dump($tree);
            print("-------------------------\n");
        }

        if ( ! $t) {
            return($tree);
        }

        switch ($t["CLASS"]) {
            case "TOK_CONTEXT":
//        if($this->debug)
//        {
//          printf("addtotree({tree}, \$t='%s', \$depth=%d, inquote=%s)\n", $t["VALUE"], $depth, $inquote?"true":"false");
//        }
                if ($tree["CLASS"] == "SIMPLE" || $tree["CLASS"] == "QSIMPLE") {
                    // un [xxx] suit un terme : il introduit un contexte
                    $tree["CONTEXT"] = $t["VALUE"];
                } elseif ($tree["CLASS"] == "OPS" || $tree["CLASS"] == "OPK") {
                    if ( ! isset($tree["RB"]) || ! $tree["RB"]) {
                        // un [xxx] peut suivre un opérateur, c'est un paramétre normalement numérique
                        $tree["PNUM"] = $t["VALUE"];
                    } else {
                        // [xxx] suit un terme déjé en branche droite ? (ex: a ou b[k])
                        if ($tree["RB"]["CLASS"] == "SIMPLE" || $tree["RB"]["CLASS"] == "QSIMPLE")
                            $tree["RB"]["CONTEXT"] = $t["VALUE"];
                        else {
                            if ($this->errmsg != "")
                                $this->errmsg .= "\\n";
                            $this->errmsg .= sprintf("le contexte [%s] ne peut suivre qu'un terme ou un opérateur<br/>", $t["VALUE"]);

                            return(null);
                        }
                    }
                } else {
                    if ($this->errmsg != "")
                        $this->errmsg .= "\\n";
                    $this->errmsg .= sprintf("le contexte [%s] ne peut suivre qu'un terme ou un opérateur<br/>", $t["VALUE"]);

                    return(null);
                }

                return($tree);
                break;
            case "TOK_CMP":
                // < > <= >= <> = : sont des opérateurs de comparaison
                if ( ! $tree) {
                    // printf("\nUne question ne peut commencer par '" . $t["VALUE"] . "'<br>");
                    if ($this->errmsg != "")
                        $this->errmsg .= "\\n";
                    $this->errmsg .= sprintf(_('qparser::erreur : une question ne peut commencer par %s'), $t["VALUE"]);

                    return(null);
                }
                if (($tree["CLASS"] == "OPS" || $tree["CLASS"] == "OPK") && $tree["RB"] == null) {
                    // printf("'" . $t["VALUE"] . "' ne peut suivre un opérateur<br>");
                    if ($this->errmsg != "")
                        $this->errmsg .= "\\n";
                    $this->errmsg .= sprintf(_('qparser::Formulation incorrecte, ne peut suivre un operateur :  %s'), $t["VALUE"]);

                    return(null);
                }

                return(array("CLASS"    => "OPK", "VALUE"    => $t["VALUE"], "NODETYPE" => $this->opk[$t["VALUE"]]["NODETYPE"], "PNUM"     => null, "DEPTH"    => $depth, "LB"       => $tree, "RB"       => null));
                break;
            case "TOK_WORD":
                if ($t["CLASS"] == "TOK_WORD" && isset($this->ops[$t["VALUE"]]) && ! $inquote) {
                    // ce mot est un opérateur phrasea
                    if ( ! $tree) {
                        // printf("\n581 : Une question ne peut commencer par un opérateur<br>");
                        if ($this->errmsg != "")
                            $this->errmsg .= "\\n";
                        $this->errmsg .= sprintf(_('qparser::erreur : une question ne peut commencer par %s'), $t["VALUE"]);

                        return(null);
                    }
                    if (($tree["CLASS"] == "OPS" || $tree["CLASS"] == "OPK") && $tree["RB"] == null) {

                        // printf("\n586 : Un opérateur ne peut suivre un opérateur<br>");
                        if ($this->errmsg != "")
                            $this->errmsg .= "\\n";
                        $this->errmsg .= sprintf(_('qparser::Formulation incorrecte, %s ne peut suivre un operateur'), $t["VALUE"]);

                        return(null);
                    }
                    $pnum = null;
                    if ($this->ops[$t["VALUE"]]["CANNUM"]) {
                        // cet opérateur peut étre suivi d'un nombre ('near', 'before', 'after')
                        if ($tn = $this->nexttoken()) {
                            if ($tn["CLASS"] == "TOK_WORD" && is_numeric($tn["VALUE"]))
                                $pnum = (int) $tn["VALUE"];
                            else
                                $this->ungettoken($tn["VALUE"]);
                        }
                    }

                    return(array("CLASS"    => "OPS", "VALUE"    => $t["VALUE"], "NODETYPE" => $this->ops[$t["VALUE"]]["NODETYPE"], "PNUM"     => $pnum, "DEPTH"    => $depth, "LB"       => $tree, "RB"       => null));
                } else {
                    // ce mot n'est pas un opérateur
                    $pnum = null;
                    $nodetype = PHRASEA_KEYLIST;
                    if ($t["CLASS"] == "TOK_WORD" && isset($this->spw[$t["VALUE"]]) && ! $inquote) {
                        // mais c'est un mot 'spécial' de phrasea ('last', 'all')
                        $type = $this->spw[$t["VALUE"]]["CLASS"];
                        $nodetype = $this->spw[$t["VALUE"]]["NODETYPE"];
                        if ($this->spw[$t["VALUE"]]["CANNUM"]) {
                            // 'last' peut étre suivi d'un nombre
                            if ($tn = $this->nexttoken()) {
                                if ($tn["CLASS"] == "TOK_WORD" && is_numeric($tn["VALUE"]))
                                    $pnum = (int) $tn["VALUE"];
                                else
                                    $this->ungettoken($tn["VALUE"]);
                            }
                        }
                    } else {
                        //printf("sdfsdfsdfsd<br>");
                        $type = $inquote ? "QSIMPLE" : "SIMPLE";
                    }

                    return($this->addsimple($t, $type, $nodetype, $pnum, $tree, $depth));
                }
                break;
        }
    }

    public function addsimple($t, $type, $nodetype, $pnum, $tree, $depth)
    {
        $nok = 0;
        $registry = registry::get_instance();
        $w = $t["VALUE"];
        if ($w != "?" && $w != "*") {  // on laisse passer les 'isolés' pour les traiter plus tard comme des mots vides
            for ($i = 0; $i < strlen($w); $i ++ ) {
                $c = substr($w, $i, 1);
                if ($c == "?" || $c == "*") {
                    if ($nok < $registry->get('GV_min_letters_truncation')) {
                        if ($this->errmsg != "")
                            $this->errmsg .= sprintf("\\n");
                        $this->errmsg .= _('qparser:: Formulation incorrecte, necessite plus de caractere : ') . "<br>" . $registry->get('GV_min_letters_truncation');

                        return(null);
                    }
                    // $nok = 0;
                } else
                    $nok ++;
            }
        }
        if ( ! $tree) {
            return(array("CLASS"    => $type, "NODETYPE" => $nodetype, "VALUE"    => array($t["VALUE"]), "PNUM"  => $pnum, "DEPTH" => $depth));
        }
        switch ($tree["CLASS"]) {
            case "SIMPLE":
            case "QSIMPLE":
                if ($type == "SIMPLE" || $type == "QSIMPLE")
                    $tree["VALUE"][] = $t["VALUE"];
                else {
                    $tree = array("CLASS"    => "OPS",
                        "VALUE"    => "et",
                        "NODETYPE" => PHRASEA_OP_AND,
                        "PNUM"     => null,
                        "DEPTH"    => $depth,
                        "LB"       => $tree,
                        "RB"       => array("CLASS"    => $type,
                            "NODETYPE" => $nodetype,
                            "VALUE"    => array($t["VALUE"]),
                            "PNUM"  => $pnum,
                            "DEPTH" => $depth));
                }

                return($tree);
            case "OPS":
            case "OPK":
                if ($tree["RB"] == null) {
                    $tree["RB"] = array("CLASS"    => $type, "NODETYPE" => $nodetype, "VALUE"    => array($t["VALUE"]), "PNUM"  => $pnum, "DEPTH" => $depth);

                    return($tree);
                } else {
                    if (($tree["RB"]["CLASS"] == "SIMPLE" || $tree["RB"]["CLASS"] == "QSIMPLE") && $tree["RB"]["DEPTH"] == $depth) {
                        $tree["RB"]["VALUE"][] = $t["VALUE"];

                        return($tree);
                    }
                    if (($tree["RB"]["CLASS"] == "PHRASEA_KW_LAST" || $tree["RB"]["CLASS"] == "PHRASEA_KW_ALL") && $tree["RB"]["DEPTH"] == $depth) {
                        $tree["RB"] = array("CLASS"    => "OPS",
                            "VALUE"    => "et",
                            "NODETYPE" => PHRASEA_OP_AND,
                            "PNUM"     => null,
                            "DEPTH"    => $depth,
                            "LB"       => $tree["RB"],
                            "RB"       => array("CLASS"    => $type,
                                "NODETYPE" => $nodetype,
                                "VALUE"    => array($t["VALUE"]),
                                "PNUM"  => $pnum,
                                "DEPTH" => $depth));

                        return($tree);
                    }

                    return(array("CLASS"    => "OPS",
                        "VALUE"    => $this->defaultop["VALUE"],
                        "NODETYPE" => $this->defaultop["NODETYPE"],
                        "PNUM"     => $this->defaultop["PNUM"],
                        "DEPTH"    => $depth,
                        "LB"       => $tree,
                        "RB"       => array("CLASS"    => $type, "NODETYPE" => $nodetype, "VALUE"    => array($t["VALUE"]), "PNUM"  => $pnum, "DEPTH" => $depth)
                        ));
                }
            case "PHRASEA_KW_LAST":
            case "PHRASEA_KW_ALL":
                return(array("CLASS"    => "OPS",
                    "VALUE"    => "et",
                    "NODETYPE" => PHRASEA_OP_AND,
                    "PNUM"     => null,
                    "DEPTH"    => $depth,
                    "LB"       => $tree,
                    "RB"       => array("CLASS"    => $type,
                        "NODETYPE" => $nodetype,
                        "VALUE"    => array($t["VALUE"]),
                        "PNUM"  => $pnum,
                        "DEPTH" => $depth)));
        }
    }

    public function ungettoken($s)
    {
        $this->phq = $s . " " . $this->phq;
    }

    public function nexttoken($inquote = false)
    {
        if ($this->phq == "") {
            return(null);
        }

        switch ($c = substr($this->phq, 0, 1)) {
            case "<":
            case ">":
                if ($inquote) {
                    $this->phq = $this->mb_ltrim(mb_substr($this->phq, 1, 99999, 'UTF-8'), 'UTF-8');

                    return(array("CLASS" => "TOK_VOID", "VALUE" => $c));
                }
                $c2 = $c . substr($this->phq, 1, 1);
                if ($c2 == "<=" || $c2 == ">=" || $c2 == "<>") {
                    $this->phq = $this->mb_ltrim(mb_substr($this->phq, 2, 99999, 'UTF-8'), 'UTF-8');
                    $c = $c2;
                } else {
                    $this->phq = $this->mb_ltrim(mb_substr($this->phq, 1, 99999, 'UTF-8'), 'UTF-8');
                }

                return(array("CLASS" => "TOK_CMP", "VALUE" => $c));
                break;
            case "=":
                if ($inquote) {
                    $this->phq = $this->mb_ltrim(mb_substr($this->phq, 1, 99999, 'UTF-8'), 'UTF-8');

                    return(array("CLASS" => "TOK_VOID", "VALUE" => $c));
                }
                $this->phq = $this->mb_ltrim(mb_substr($this->phq, 1, 99999, 'UTF-8'), 'UTF-8');

                return(array("CLASS" => "TOK_CMP", "VALUE" => "="));
                break;
            case ":":
                if ($inquote) {
                    $this->phq = $this->mb_ltrim(mb_substr($this->phq, 1, 99999, 'UTF-8'), 'UTF-8');

                    return(array("CLASS" => "TOK_VOID", "VALUE" => $c));
                }
                $this->phq = $this->mb_ltrim(mb_substr($this->phq, 1, 99999, 'UTF-8'), 'UTF-8');

                return(array("CLASS" => "TOK_CMP", "VALUE" => ":"));
                break;
            case "(":
                if ($inquote) {
                    $this->phq = $this->mb_ltrim(mb_substr($this->phq, 1, 99999, 'UTF-8'), 'UTF-8');

                    return(array("CLASS" => "TOK_VOID", "VALUE" => $c));
                }
                $this->phq = $this->mb_ltrim(mb_substr($this->phq, 1, 99999, 'UTF-8'), 'UTF-8');

                return(array("CLASS" => "TOK_LP", "VALUE" => "("));
                break;
            case ")":
                if ($inquote) {
                    $this->phq = $this->mb_ltrim(mb_substr($this->phq, 1, 99999, 'UTF-8'), 'UTF-8');

                    return(array("CLASS" => "TOK_VOID", "VALUE" => $c));
                }
                $this->phq = $this->mb_ltrim(mb_substr($this->phq, 1, 99999, 'UTF-8'), 'UTF-8');

                return(array("CLASS" => "TOK_RP", "VALUE" => ")"));
                break;
            case "[":
                //  if($inquote)
                //  {
                //    $this->phq = ltrim(substr($this->phq, 1));
                //    return(array("CLASS"=>"TOK_VOID", "VALUE"=>$c));
                //  }
                // un '[' introduit un contexte qu'on lit jusqu'au ']'
                $closeb = mb_strpos($this->phq, "]", 1, 'UTF-8');
                if ($closeb !== false) {
                    $context = $this->mb_trim(mb_substr($this->phq, 1, $closeb - 1, 'UTF-8'), 'UTF-8');
                    $this->phq = $this->mb_ltrim(mb_substr($this->phq, $closeb + 1, 99999, 'UTF-8'), 'UTF-8');
                } else {
                    $this->phq = $this->mb_ltrim(mb_substr($this->phq, 1, 99999, 'UTF-8'), 'UTF-8');
                    $this->phq = "";
                }
                $context = $this->unicode->remove_indexer_chars($context);

                return(array("CLASS" => "TOK_CONTEXT", "VALUE" => $context));
                break;
            /*
              case "]":
              //  if($inquote)
              //  {
              //    $this->phq = ltrim(substr($this->phq, 1));
              //    return(array("CLASS"=>"TOK_VOID", "VALUE"=>$c));
              //  }
              $this->phq = ltrim(substr($this->phq, 1));

              return(array("CLASS"=>"TOK_RB", "VALUE"=>"]"));
              break;
             */
            case "\"":
                $this->phq = $this->mb_ltrim(mb_substr($this->phq, 1, 99999, 'UTF-8'), 'UTF-8');

                return(array("CLASS" => "TOK_QUOTE", "VALUE" => "\""));
                break;
            default:
                $l = mb_strlen($this->phq, 'UTF-8');
                $t = "";
                $c_utf8 = "";
                for ($i = 0; $i < $l; $i ++ ) {
                    if ( ! $this->unicode->has_indexer_bad_char(($c_utf8 = mb_substr($this->phq, $i, 1, 'UTF-8')))) {
                        //  $c = mb_strtolower($c);
                        //  $t .= isset($this->noaccent[$c]) ? $this->noaccent[$c] : $c;
                        $t .= $this->unicode->remove_diacritics(mb_strtolower($c_utf8));
                    } else
                        break;
                }
//        if ($c_utf8 == "(" || $c_utf8 == ")" || $c_utf8 == "[" || $c_utf8 == "]" || $c_utf8 == "=" || $c_utf8 == ":" || $c_utf8 == "<" || $c_utf8 == ">" || $c_utf8 == "\"")
                if (in_array($c_utf8, array("(", ")", "[", "]", "=", ":", "<", ">", "\""))) {
                    // ces caractéres sont des délimiteurs avec un sens, il faut les garder
                    $this->phq = $this->mb_ltrim(mb_substr($this->phq, $i, 99999, 'UTF-8'), 'UTF-8');
                } else {
                    // le délimiteur était une simple ponctuation, on le saute
                    $this->phq = $this->mb_ltrim(mb_substr($this->phq, $i + 1, 99999, 'UTF-8'), 'UTF-8');
                }
                if ($t != "") {
                    return(array("CLASS" => "TOK_WORD", "VALUE" => $t));
                } else {
                    return(array("CLASS" => "TOK_VOID", "VALUE" => $t));
                }
                break;
        }
    }
}

