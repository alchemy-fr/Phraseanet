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
/* @var $Core \Alchemy\Phrasea\Core */
$Core = require_once __DIR__ . "/../../lib/bootstrap.php";

$appbox = appbox::get_instance($Core);
$session = $appbox->get_session();

set_time_limit(60 * 60);
phrasea::headers(200, true);

$request = http_request::getInstance();
$parm = $request->get_parms(
    "bid"
    , "piv"
    , "id"
    , "typ"
    , "dlg"
    , "osl"
    , "iln"
    , "ilg"
    , "hit"
    , "smp"
);

if ($parm["dlg"]) {
    $opener = "window.dialogArguments.win";
} else {
    $opener = "opener";
}
?>

<html lang="<?php echo $session->get_I18n(); ?>">
    <head>
        <title><?php echo p4string::MakeString(_('thesaurus:: export au format texte')) ?></title>

        <link REL="stylesheet" TYPE="text/css" HREF="./thesaurus.css?u=<?php echo mt_rand() ?>" />

        <script type="text/javascript">
            function loaded()
            {
                // window.name="EXPORT2";
                self.focus();
            }
        </script>
    </head>
    <body id="idbody" onload="loaded();" style="background-color:#ffffff" >
<?php
$thits = array();
if ($parm["typ"] == "TH" || $parm["typ"] == "CT") {
    $loaded = false;
    try {
        $databox = databox::get_instance((int) $parm['bid']);
        $connbas = connection::getPDOConnection($parm['bid']);

        if ($parm["typ"] == "TH") {
            $domth = $databox->get_dom_thesaurus();
        } else {
            $domth = $databox->get_dom_cterms();
        }

        if ($domth) {
            $sql = "SELECT value, SUM(1) as hits FROM thit GROUP BY value";

            $stmt = $connbas->prepare($sql);
            $stmt->execute();
            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            foreach ($rs as $rowbas2) {
                $thits[str_replace('d', '.', $rowbas2["value"])] = $rowbas2["hits"];
            }

            $xpathth = new DOMXPath($domth);
            printf("<pre style='font-size: %dpx;'>\n", $parm["smp"] ? 9 : 12);
            if ($parm["id"] == "T")
                $q = "/thesaurus";
            elseif ($parm["id"] == "C")
                $q = "/cterms";
            else
                $q = "//te[@id='" . $parm["id"] . "']";
            export0($xpathth->query($q)->item(0));
            print("</pre>\n");
        }
    } catch (Exception $e) {

    }
}

$tnodes = NULL;

function printTNodes()
{
    global $tnodes;
    global $thits;
    global $parm;

    $numlig = ($parm["iln"] == "1");
    $hits = ($parm["hit"] == "1");
    $ilg = ($parm["ilg"] == "1");
    $oneline = ($parm["osl"] == "1");

    $ilig = 1;

    foreach ($tnodes as $node) {
        $tabs = str_repeat("\t", $node["depth"]);
        switch ($node["type"]) {
            case "ROOT":
                if ($numlig)
                    print($ilig ++ . "\t");
                if ($hits && ! $oneline)
                    print("\t");
                print($tabs . $node["name"] . "\n");
                break;
            case "TRASH":
                if ($numlig)
                    print($ilig ++ . "\t");
                if ($hits && ! $oneline)
                    print("\t");
                print($tabs . "{TRASH}\n");
                break;
            case "FIELD":
                if ($numlig)
                    print($ilig ++ . "\t");
                if ($hits && ! $oneline)
                    print("\t");
                print($tabs . $node["name"] . "\n");
                break;
            case "TERM":
                $isyn = 0;
                if ($oneline) {
                    if ($numlig)
                        print($ilig ++ . "\t");
                    print($tabs);
                    $isyn = 0;
                    foreach ($node["syns"] as $syn) {
                        if ($isyn > 0)
                            print(" ; ");
                        print($syn["v"]);
                        if ($ilg)
                            print(" [" . $syn["lng"] . "]");
                        if ($hits)
                            print(" [" . $syn["hits"] . "]");
                        $isyn ++;
                    }
                    print("\n");
                } else {
                    $isyn = 0;
                    foreach ($node["syns"] as $syn) {
                        if ($numlig)
                            print($ilig ++ . "\t");
                        if ($hits)
                            print( $syn["hits"] . "\t");
                        print($tabs);
                        if ($isyn > 0)
                            print("; ");
                        print($syn["v"]);
                        if ($ilg)
                            print(" [" . $syn["lng"] . "]");
                        print("\n");
                        $isyn ++;
                    }
                }
                break;
        }
        if ( ! $oneline) {
            if ($numlig)
                print($ilig ++ . "\t");
            print("\n");
        }
    }
}

function exportNode(&$node, $depth)
{
    global $thits;
    global $tnodes;
    if ($node->nodeType == XML_ELEMENT_NODE) {
        if (($nname = $node->nodeName) == "thesaurus" || $nname == "cterms") {
            $tnodes[] = array("type"  => "ROOT", "depth" => $depth, "name"  => $nname, "cdate" => $node->getAttribute("creation_date"), "mdate" => $node->getAttribute("modification_date"));
        } elseif (($fld = $node->getAttribute("field"))) {
            if ($node->getAttribute("delbranch"))
                $tnodes[] = array("type"    => "TRASH", "depth"   => $depth, "name"    => $fld);
            else
                $tnodes[] = array("type"  => "FIELD", "depth" => $depth, "name"  => $fld);
        } else {
            $tsy = array();
            for ($n = $node->firstChild; $n; $n = $n->nextSibling) {
                if ($n->nodeName == "sy") {
                    $id = $n->getAttribute("id");
                    if (array_key_exists($id . '.', $thits))
                        $hits = 0 + $thits[$id . '.'];
                    else
                        $hits = 0;

                    $tsy[] = array("v"       => $n->getAttribute("v"), "lng"     => $n->getAttribute("lng"), "hits"    => $hits);
                }
            }
            $tnodes[] = array("type"  => "TERM", "depth" => $depth, "syns"  => $tsy);
        }
    }
}

function export0($znode)
{
    global $tnodes;
    $tnodes = array();

    $nodes = array();
    $depth = 0;

    for ($node = $znode->parentNode; $node; $node = $node->parentNode) {
        if ($node->nodeType == XML_ELEMENT_NODE)
            $nodes[] = $node;
    }
    $nodes = array_reverse($nodes);

    foreach ($nodes as $depth => $node) {
        //  print( exportNode($node, $depth) );
        exportNode($node, $depth);
    }

    export($znode, count($nodes));

    printTNodes();
}

function export($node, $depth = 0)
{
    global $tnodes;
    if ($node->nodeType == XML_ELEMENT_NODE) {
        // print( exportNode($node, $depth) );
        exportNode($node, $depth);
    }
    for ($n = $node->firstChild; $n; $n = $n->nextSibling) {
        if ($n->nodeName == "te")
            export($n, $depth + 1);
    }
}
?>
    </body>
</html>
