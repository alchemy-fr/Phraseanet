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

require_once __DIR__ . "/../../lib/bootstrap.php";
phrasea::headers(200, true);
$app = new Application();

$request = http_request::getInstance();
$parm = $request->get_parms(
    "bid"
    , "piv"
    , "f2unlk"
    , "fbranch"
    , "reindex"
);
?>
<html lang="<?php echo $app['locale.I18n']; ?>">
    <head>
        <title><?php echo p4string::MakeString(_('thesaurus:: Lier la branche de thesaurus')) ?></title>

        <link REL="stylesheet" TYPE="text/css" HREF="./thesaurus.css?u=<?php echo mt_rand() ?>" />

    </head>
    <body  class="dialog">
    <center>
        <br/>
        <br/>
        <br/>
        <form onsubmit="return(false);">
            <div style="width:70%; height:200px; overflow:scroll;" class="x3Dbox">
                <?php
                if ($parm["f2unlk"] == NULL)
                    $parm["f2unlk"] = array();
                if ($parm["fbranch"] == NULL)
                    $parm["fbranch"] = array();

                if ($parm["bid"] !== null) {
                    $loaded = false;
                    try {
                        $databox = $app['phraseanet.appbox']->get_databox((int) $parm['bid']);
                        $connbas = connection::getPDOConnection($app, $parm['bid']);
                        $meta_struct = $databox->get_meta_structure();
                        $domct = $databox->get_dom_cterms();
                        $domst = $databox->get_dom_structure();

                        if ($domct && $domst) {
                            $xpathct = new DOMXPath($domct);
                            $xpathst = new DOMXPath($domst);
                            $ctchanged = false;

                            $candidates2del = array();
                            foreach ($parm["f2unlk"] as $f2unlk) {
                                $q = "/cterms/te[@field='" . thesaurus::xquery_escape($f2unlk) . "']";
                                $nodes = $xpathct->query($q);
                                for ($i = 0; $i < $nodes->length; $i ++ ) {
                                    $candidates2del[] = array("field" => $f2unlk, "node"  => $nodes->item($i));
                                }

                                echo p4string::MakeString(sprintf(_('thesaurus:: suppression du lien du champ %s'), $f2unlk));
                                print("<br/>\n");
                                $field = $meta_struct->get_element_by_name($f2unlk);
                                if ($field)
                                    $field->set_tbranch('')->save();
                            }
                            foreach ($candidates2del as $candidate2del) {
                                echo p4string::MakeString(sprintf(_('thesaurus:: suppression de la branche de mot candidats pour le champ %s'), $candidate2del["field"]));
                                print("<br/>\n");
                                $candidate2del["node"]->parentNode->removeChild($candidate2del["node"]);
                                $ctchanged = true;
                            }

                            foreach ($parm["fbranch"] as $fbranch) {
                                $p = strpos($fbranch, "<");
                                if ($p > 1) {
                                    $fieldname = substr($fbranch, 0, $p);
                                    $tbranch = substr($fbranch, $p + 1);
                                    $field = $meta_struct->get_element_by_name($fieldname);
                                    if ($field)
                                        $field->set_tbranch($tbranch)->save();
                                    echo p4string::MakeString(sprintf(_('thesaurus:: suppression de la branche de mot candidats pour le champ %s'), $fieldname));
                                    print("<br/>\n");
                                }
                            }

                            if ($ctchanged) {
                                if ($ctchanged) {
                                    $databox->saveCterms($domct);
                                    print(p4string::MakeString(_('thesaurus:: enregistrement de la liste modifiee des mots candidats.')));
                                    print("<br/>\n");
                                }
                            }
                        }


                        $sql = "DELETE FROM thit WHERE name = :name";
                        $stmt = $connbas->prepare($sql);
                        foreach ($parm["f2unlk"] as $f2unlk) {
                            $stmt->execute(array(':name' => $f2unlk));
                            echo p4string::MakeString(_('thesaurus:: suppression des indexes vers le thesaurus pour le champ') . " <b>" . $f2unlk . "</b>");
                            print("<br/>\n");
                        }
                        $stmt->closeCursor();

                        if ($parm["reindex"]) {
                            $sql = "UPDATE record SET status=status & ~2";
                            $stmt = $connbas->prepare($sql);
                            $stmt->execute();
                            $stmt->closeCursor();
                            echo p4string::MakeString(_('thesaurus:: reindexer tous les enregistrements'));
                            print("<br/>\n");
                        }
                    } catch (Exception $e) {

                    }
                }
                ?>
            </div>
            <br/>
            <input type="button" value="<?php echo p4string::MakeString(_('boutton::fermer')) ?>" onclick="self.close();">
        </form>
    </center>
</body>
</html>
