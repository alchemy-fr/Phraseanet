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
require_once __DIR__ . '/../../lib/bootstrap.php';
$registry = registry::get_instance();
phrasea::headers();
$debug = false;

$request = http_request::getInstance();
$parm = $request->get_parms(
    "bid", "piv", "repair"
);

function fixW(&$node, $depth = 0)
{
    if ($node && $node->nodeType == XML_ELEMENT_NODE) {
        if (($v = $node->getAttribute("v")) != "") {
            $unicode = new unicode();
            $node->setAttribute("w", $unicode->remove_indexer_chars($v));
        }
        for ($c = $node->firstChild; $c; $c = $c->nextSibling)
            fixW($c, $depth + 1);
    }
}
if ($hdir = opendir($registry->get('GV_RootPath') . "www/thesaurus2/patch")) {
    while (false !== ($file = readdir($hdir))) {
        if (substr($file, 0, 1) == ".")
            continue;
        if (is_file($f = $registry->get('GV_RootPath') . "www/thesaurus2/patch/" . $file)) {
            require_once($f);
            print("<!-- patch '$f' included -->\n");
        }
    }
    closedir($hdir);
}

function fixThesaurus(&$domct, &$domth, &$connbas)
{
    $oldversion = $version = $domth->documentElement->getAttribute("version");
//  $cls = "patch_th_".str_replace(".","_",$version);
//printf("---- %s %s %s \n", $version, $cls, class_exists($cls) );
//printf("---- %s %s \n", $version, $cls );
    while (class_exists($cls = "patch_th_" . str_replace(".", "_", $version), false)) {
        print("// ==============  patching from version='$version'\n");

        $last_version = $version;
        $zcls = new $cls;
        print("// ----------- calling class '$cls'\n");
        $version = $zcls->patch($version, $domct, $domth, $connbas);
        print("// ----------- method 'patch' -> returned '$version'\n");

        if ($version == $last_version)
            break;
    }

    return($version);
}
?>
<script language="javascript">
<?php
$th = $ct = $name = "";
$found = false;
if ($parm["bid"] !== null) {
    $name = phrasea::sbas_names($parm['bid']);
    $loaded = false;
    try {
        $databox = databox::get_instance((int) $parm['bid']);
        $connbas = connection::getPDOConnection($parm['bid']);

        $domct = $databox->get_dom_cterms();
        $domth = $databox->get_dom_thesaurus();
        ?>
            parent.currentBaseId   = <?php echo $parm["bid"] ?>;
            parent.currentBaseName = "<?php echo p4string::MakeString($name, "js") ?>";
            parent.document.title = "<?php echo p4string::MakeString(_('phraseanet:: thesaurus'), "js") ?>";
            parent.document.getElementById("baseName").innerHTML = "<?php echo p4string::MakeString(_('phraseanet:: thesaurus'), "js") /* thesaurus de la base xxx */ ?>";
            parent.thesaurusChanged = false;
        <?php
        $now = date("YmdHis");

        if ( ! $domct && $parm['repair'] == 'on') {
            $domct = DOMDocument::load("./blank_cterms.xml");
            $domct->documentElement->setAttribute("creation_date", $now);
            $databox->saveCterms($domct);
        }
        if ( ! $domth && $parm['repair'] == 'on') {
            $domth = DOMDocument::load("./blank_thesaurus.xml");
            $domth->documentElement->setAttribute("creation_date", $now);
            $databox->saveThesaurus($domth);
        }

        if ($domct && $domth) {

            $oldversion = $domth->documentElement->getAttribute("version");
            if (($version = fixThesaurus($domct, $domth, $connbas)) != $oldversion) {
                print("alert('" . utf8_encode("le thesaurus a ete converti en version $version") . "');\n");

                $databox->saveCterms($domct);
                $databox->saveThesaurus($domth);
            }

            $xpathct = new DOMXPath($domct);
            // on cherche la branche 'deleted' dans les cterms
            $nodes = $xpathct->query("/cterms/te[@delbranch='1']");
            if ($nodes && ($nodes->length > 0)) {
                // on change le nom e la volee
                $nodes->item(0)->setAttribute("field", _('thesaurus:: corbeille'));
            }

            print("parent.document.getElementById(\"T0\").innerHTML='");
            print(str_replace(array("'", "\n", "\r"), array("\\'", "", ""), $html = cttohtml($domct, $name)));
            print("';\n");

            print("parent.document.getElementById(\"T1\").innerHTML='");
            print(str_replace(array("'", "\n", "\r"), array("\\'", "", ""), $html = thtohtml($domth, "THE", $name)));
            print("';\n");
        } else {
            ?>
                if(confirm("Thesaurus ou CTerms invalide\n effacer (OK) ou quitter (Annuler) ?"))
                {
                    parent.document.forms['fBase'].repair.value = "on";
                    parent.document.forms['fBase'].submit();
                }
                else
                {
                    parent.window.close();
                }
            <?php
        }
    } catch (Exception $e) {

    }
}

function cttohtml($ctdom, $name)
{
    $html = "<DIV class='glossaire' id='CTERMS'>";
//  $html .= "  <div id='TCE_C' class='s_' style='display:none'><u id='THP_C'>-</u>STOCK</div>\n";
    $html .= "  <div id='TCE_C' class='s_' style='font-weight:900'><u id='THP_C'>-</u>" . ($name) . "</div>\n";
//  $html .= "  <div id='THB_C' class='ctroot'>\n";
    $html .= "  <div id='THB_C' class='OB'>\n";
    for ($ct = $ctdom->documentElement->firstChild; $ct; $ct = $ct->nextSibling) {
        if ($ct->nodeName == "te") {
            $id = $ct->getAttribute("id");
            $t = $ct->getAttribute("field");
            $html .= "    <div id='TCE_$id' class='s_'><u id='THP_$id'>+</u>" . ($t) . "</div>\n";
            $html .= "    <div id='THB_$id' class='ob'>\n";
            $html .= "    </div>\n";
        }
    }
    $html .= "  </div>";
    $html .= "</DIV>";

    return($html);
}

function thtohtml($thdom, $typ, $name)
{
    $html = "<DIV class='glossaire'>\n";
    $html .= "  <div id='" . $typ . "_T' class='s_' style='font-weight:900'><u id='THP_T'>+</u>" . ($name) . "</div>\n";
    $html .= "  <div id='THB_T' class='ob'>\n";
    for ($n = $thdom->documentElement->firstChild; $n; $n = $n->nextSibling) {
//    if($n->nodeName=="te")
//      tetohtml($n, $html);
    }
    $html .= "  </div>\n";
    $html .= "</DIV>";

    return($html);
}

function tetohtml($tenode, &$html, $depth = 0)
{
    $tab = str_repeat("\t", $depth);
    $id = $tenode->getAttribute("id");
    $nextid = $tenode->getAttribute("nextid");
    $t = "";
    for ($n = $tenode->firstChild; $n; $n = $n->nextSibling) {
        if ($n->nodeName == "sy")
            $t .= $t ? " ; " : "" . $n->getAttribute("v");
    }
//  if($t=="")
//    $t = $depth==0 ? "THESAURUS" : "!vide/empty!";
    $t = str_replace(array("&", "<", ">", "\""), array("&amp;", "&lt;", "&gt;", "&quot;"), $t);
    $html .= "$tab<div id='THE_$id' class='s_'><u id='THP_$id'>+</u>" . $t . "</div>\n";
    $html .= "$tab<div id='THB_$id' class='ob'>\n";

    $html .= "$tab</div>\n";
}
?>
</script>
