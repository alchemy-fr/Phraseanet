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
phrasea::headers(200, true);
$appbox = appbox::get_instance($Core);
$session = $appbox->get_session();

$request = http_request::getInstance();
$parm = $request->get_parms(
    "bid"
    , "piv"
    , "dlg"
    , "dct"  // delete candidates terms
    , "drt"  // delete rejected terms
);

if ($parm["dlg"]) {
    $opener = "window.dialogArguments.win";
} else {
    $opener = "opener";
}
?>
<html lang="<?php echo $session->get_I18n(); ?>">
    <head>
        <title>Relire les candidats</title>

        <link REL="stylesheet" TYPE="text/css" HREF="./thesaurus.css?u=<?php echo mt_rand() ?>" />
    </head>

    <body onload="loaded();" class="dialog">
<?php
if ($parm["bid"] !== null) {
    $loaded = false;
    try {
        $databox = databox::get_instance((int) $parm['bid']);
        $connbas = connection::getPDOConnection($parm['bid']);

        $domct = $databox->get_dom_cterms();

        if ($domct) {
            $nodestodel = array();
            removeCandidates($domct->documentElement, $nodestodel);

            foreach ($nodestodel as $nodetodel) {
                $nodetodel->parentNode->removeChild($nodetodel);
            }
            if ($parm["dct"]) {
                $sql = "DELETE FROM thit WHERE value LIKE 'C%'";
                $stmt = $connbas->prepare($sql);
                $stmt->execute();
                $stmt->closeCursor();
            }
            if ($parm["drt"]) {
                $sql = "DELETE FROM thit WHERE value LIKE 'R%'";
                $stmt = $connbas->prepare($sql);
                $stmt->execute();
                $stmt->closeCursor();
            }

            $databox->saveCterms($domct);

            $sql = "UPDATE record SET status=status & ~2";
            $stmt = $connbas->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();
            ?>
                    <form onsubmit="return(false);">
                        <div style="padding:50px; text-align:center">
                    <?php echo utf8_encode("Termine") ?>
                            <br/>
                            <br/>
                            <input type="button" style="width:120px;" id="submit_button" value="<?php echo utf8_encode("Fermer la fenetre") ?>" onclick="refreshCterms();self.close();">
                        </div>
                    </form>
                    <?php
                }
            } catch (Exception $e) {

            }
        }

        function removeCandidates(&$node, &$nodestodel)
        {
            global $parm;
            if ($node->nodeType == XML_ELEMENT_NODE && $node->nodeName == "te" && $node->getAttribute("field") == "") {
                $id0 = substr($node->getAttribute("id"), 0, 1);
                if (($parm["dct"] && $id0 == "C") || ($parm["drt"] && $id0 == "R"))
                    $nodestodel[] = $node;
            } else {
                for ($n = $node->firstChild; $n; $n = $n->nextSibling)
                    removeCandidates($n, $nodestodel);
            }
        }
        ?>
    </body>
    <script type="text/javascript">
        function refreshCterms()
        {
            if( (thb = <?php echo $opener ?>.document.getElementById("THB_C")) )
            thb.className = thb.className.replace(/OB/, "ob");
            if( (thp = <?php echo $opener ?>.document.getElementById("THP_C")) )
            thp.innerHTML = "+";
        }

    </script>
</html>
