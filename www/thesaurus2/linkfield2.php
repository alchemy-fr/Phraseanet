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
 * @package
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
    , "tid"
    , "field"
);
?>
<html lang="<?php echo $session->get_I18n(); ?>">
    <head>
        <title><?php echo p4string::MakeString(_('thesaurus:: Lier la branche de thesaurus')) ?></title>

        <link REL="stylesheet" TYPE="text/css" HREF="./thesaurus.css?u=<?php echo mt_rand() ?>" />

        <script type="text/javascript">
            function clkBut(button)
            {
                switch(button)
                {
                    case "submit":
                        document.forms[0].submit();
                        break;
                    case "cancel":
                        self.close();
                        break;
                }
            }
            function loaded()
            {
                window.name="LINKFIELD";
            }
        </script>
    </head>
    <body onload="loaded();" class="dialog">
    <center>
        <?php
        if ($parm["field"] == NULL)
            $parm["field"] = array();
        $parm["field"] = array_flip($parm["field"]);

        if ($parm["bid"] !== null) {
            $loaded = false;
            try {
                $databox = databox::get_instance((int) $parm['bid']);
                $domstruct = $databox->get_dom_structure();
                $domth = $databox->get_dom_thesaurus();

                if ($domstruct && $domth) {
                    $xpathth = new DOMXPath($domth);
                    $xpathstruct = new DOMXPath($domstruct);
                    ?>
                    <form action="linkfield3.php" method="post" target="LINKFIELD">
                        <input type="hidden" name="piv" value="<?php echo $parm["piv"] ?>">
                        <input type="hidden" name="bid" value="<?php echo $parm["bid"] ?>">
                        <input type="hidden" name="tid" value="<?php echo $parm["tid"] ?>">
                        <br/>
                        <br/>
                        <br/>
                        <div style="width:70%; height:200px; overflow:scroll;" class="x3Dbox">
            <?php
            $needreindex = false;
            $nodes = $xpathstruct->query("/record/description/*");
            for ($i = 0; $i < $nodes->length; $i ++ ) {
                $fieldname = $nodes->item($i)->nodeName;
                $tbranch = $nodes->item($i)->getAttribute("tbranch");
                $ck = false;
                $tids = array(); // les ids de branches liees e ce champ
                if ($tbranch) {
                    // ce champ a deje un tbranch, on balaye les branches auxquelles il est lie
                    $thnodes = $xpathth->query($tbranch);
                    for ($j = 0; $j < $thnodes->length; $j ++ ) {
                        if ($thnodes->item($j)->getAttribute("id") == $parm["tid"]) {
                            // il etait deje lie e la branche selectionnee
                            $tids[$thnodes->item($j)->getAttribute("id")] = $thnodes->item($j);
                            $ck = true;
                        } else {
                            // il etait lie e une autre branche
                            $tids[$thnodes->item($j)->getAttribute("id")] = $thnodes->item($j);
                        }
                    }
                }
                //  printf("'%s' avant:%s apres:%s<br/>\n", $fieldname, $ck, array_key_exists($fieldname, $parm["field"]));
                if (array_key_exists($fieldname, $parm["field"]) != $ck) {
                    print("\t\t<hr/>");
                    echo "<b>" . $fieldname . "</b>" . p4string::MakeString(sprintf(_('thesaurus:: Ce champ a ete modifie ; ancienne branche : %s '), $tbranch));
                    print("<br/>\n");
                    if ($ck) {
                        // print("il etait lie a la branche, il ne l'est plus<br/>\n");
                        unset($tids[$parm["tid"]]);
                    } else {
                        // print("il n'etait pas lie a la branche, il l'est maintenant<br/>\n");
                        $tids[$parm["tid"]] = $xpathth->query("/thesaurus//te[@id='" . thesaurus::xquery_escape($parm["tid"]) . "']")->item(0);
                    }
                    $newtbranch = "";
                    foreach ($tids as $kitd => $node) {
                        if ($kitd === "")
                            $newtbranch .= ( $newtbranch ? " | " : "") . "/thesaurus";
                        else {
                            // $newtbranch .= ($newtbranch?" | ":"") . "/thesaurus//te[@id='" . $kitd . "']";
                            $neb = "";
                            while ($node && $node->nodeName == "te") {
                                $neb = "/te[@id='" . $node->getAttribute("id") . "']" . $neb;
                                $node = $node->parentNode;
                            }
                            $newtbranch .= ( $newtbranch ? " | " : "") . "/thesaurus" . $neb;
                        }
                    }
                    echo p4string::MakeString(_('thesaurus:: nouvelle branche')) . $newtbranch;
                    print("<br/>\n");

                    if ($tbranch != "" && $newtbranch == "") {
                        echo "<b>" . $fieldname . "</b>" . p4string::MakeString(_('thesaurus:: ce champ n\'est plus lie au thesaurus, les termes indexes et candidats seront supprimes'));
                        print("<br/>\n");
                        printf("\t\t<input type=\"hidden\" name=\"f2unlk[]\" value=\"%s\">\n", $fieldname);
                    }
                    if ($newtbranch != "") {
                        if ($tbranch == "") {
                            echo "<b>" . $fieldname . "</b>" . p4string::MakeString(_('thesaurus:: ce champ doit etre lie au thesaurus. La reindexation de la base est necessaire'));
                        } else {
                            echo "<b>" . $fieldname . "</b>" . p4string::MakeString(_('thesaurus:: le lien au thesaurus doit etre modifie, la reindexation de la base est necessaire'));
                        }
                        print("<br/>\n");

                        $needreindex = true;
                        printf("\t\t<input type=\"hidden\" name=\"fbranch[]\" value=\"%s\">\n", $fieldname . "<" . $newtbranch);
                    }
                }
            }
            ?>
                        </div>
                            <?php
                            if ($needreindex) {
                                print("\t\t<input type=\"hidden\" name=\"reindex\" value=\"1\">\n");
                                print("<div style='position:absolute; top:5px; left:0px; width:100%; text-align:center; color:red'>" . p4string::MakeString(_('thesaurus:: reindexation necessaire')) /* Reindexation necessaire ! */ . "</div>");
                            } else {
                                print("<div style='position:absolute; top:5px; left:0px; width:100%; text-align:center; color:green'>" . p4string::MakeString(_('thesaurus:: pas de reindexation')) /* Pas de reindexation necessaire ! */ . "</div>");
                            }
                            ?>
                        <br/>
                        <input type="button" id="submit_button" value="<?php echo p4string::MakeString(_('boutton::valider')) ?>" onclick="clkBut('submit');">
                        &nbsp;&nbsp;&nbsp;
                        <input type="button" id="cancel_button" value="<?php echo p4string::MakeString(_('boutton::annuler')) ?>" onclick="clkBut('cancel');">
                    </form>
                            <?php
                        }
                    } catch (Exception $e) {

                    }
                }
                ?>
    </center>
</body>
</html>
