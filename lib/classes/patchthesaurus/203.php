<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Doctrine\DBAL\Driver\Connection;

class patchthesaurus_203 implements patchthesaurus_interface
{
    public function patch($version, \DOMDocument $domct, \DOMDocument $domth, Connection $connbas, \unicode $unicode)
    {
        $needreindex = false;

        if ($version == "2.0.3") {
            $xp = new DOMXPath($domth);
            $sy = $xp->query("//sy");
            for ($i = 0; $i < $sy->length; $i ++) {
                if (($k = $sy->item($i)->getAttribute("k"))) {
                    $v = $sy->item($i)->getAttribute("v");
                    if (strpos($v, "(") === false) {
                        $sy->item($i)->setAttribute("v", $v . " (" . $k . ")");
                        printf("//  context '($k)' pasted to value '$v'\n");
                        $needreindex = true;
                    } else {
                        printf("//  <font color=\"#ff8000\">warning</font> : &lt;sy id='%s' v='%s' ...&gt; already had context (left unchanged)\n", $sy->item($i)->getAttribute("id"), htmlentities($v));
                    }

                    $newk = trim($unicode->remove_indexer_chars($k));
                    if ($newk != $k) {
                        $sy->item($i)->setAttribute("k", $newk);
                        $needreindex = true;
                    }
                }
            }
            $domth->documentElement->setAttribute("version", "2.0.4");
            $domth->documentElement->setAttribute("modification_date", date("YmdHis"));

            if ($needreindex) {
                print("//   need to reindex, deleting cterms (keeping rejected)\n");

                $xp = new DOMXPath($domct);

                $nodes = $xp->query("//te[not(starts-with(@id, 'R')) and count(te[starts-with(@id, 'R')])=0]");
                $nodestodel = [];
                for ($i = 0; $i < $nodes->length; $i ++ )
                    $nodestodel[] = $nodes->item($i);
                $ctdel = 0;
                foreach ($nodestodel as $node) {
                    $sql2 = "DELETE FROM thit WHERE value LIKE :like";

                    $stmt = $connbas->prepare($sql2);
                    $stmt->execute([':like' => str_replace(".", "d", $node->getAttribute("id")) . "d%"]);
                    $stmt->closeCursor();

                    $node->parentNode->removeChild($node);
                    $ctdel ++;
                }
                print("//     $ctdel nodes removed\n");

                $sql2 = "UPDATE record SET status=((status | 15) & ~2)";
                $stmt = $connbas->prepare($sql2);
                $stmt->execute();
                $stmt->closeCursor();
            }

            $sy = $xp->query("//sy");
            for ($i = 0; $i < $sy->length; $i ++) {
                if (($k = $sy->item($i)->getAttribute("k"))) {
                    if (strpos($v = $sy->item($i)->getAttribute("v"), "(") === false)
                        $sy->item($i)->setAttribute("v", $v . " (" . $k . ")");
                    else
                        printf("//   <font color=\"#ff8000\">warning</font> : &lt;sy id='%s' v='%s' ...&gt already had context (left unchanged)\n", $sy->item($i)->getAttribute("id"), htmlentities($v));
                    $sy->item($i)->setAttribute("k", $unicode->remove_indexer_chars($k));
                }
            }

            $domct->documentElement->removeAttribute("id");

            $this->fixRejected($connbas, $domct->documentElement, false);

            $this->fixIds($connbas, $domct->documentElement);

            $domct->documentElement->setAttribute("version", "2.0.4");
            $domct->documentElement->setAttribute("modification_date", date("YmdHis"));

            $version = "2.0.4";
        }

        return($version);
    }

    public function fixRejected(Connection $connbas, &$node, $rejected)
    {
        if ($node->nodeType != XML_ELEMENT_NODE) {
            return;
        }

        $id = $node->getAttribute("id");

        if (substr($id, 0, 1) == "R") {
            $rejected = true;
        }

        if ($rejected) {
            $newid = "R" . substr($id, 1);
            if ($newid != $id) {
                print("// \tid '$id' (child of '" . $node->parentNode->getAttribute("id") . "') fixed to '$newid'\n");
                $node->setAttribute("id", $newid);
                $id = str_replace(".", "d", $id) . "d";
                $newid = str_replace(".", "d", $newid) . "d";
                $sql = "UPDATE thit SET value = :newid WHERE value = :oldid";

                $stmt = $connbas->prepare($sql);
                $stmt->execute([':newid' => $newid, ':oldid' => $id]);
                $stmt->closeCursor();
            }
        }
        for ($n = $node->firstChild; $n; $n = $n->nextSibling)
            $this->fixRejected($connbas, $n, $rejected);
    }

    public function fixIds(Connection $connbas, &$node)
    {
        if ($node->nodeType != XML_ELEMENT_NODE) {
            return;
        }

        if ($node->parentNode && $node->parentNode->nodeType == XML_ELEMENT_NODE) {
            $pid = $node->parentNode->getAttribute("id");
            if ($pid != "") {
                $id = $node->getAttribute("id");
                if (substr($id, 1, strlen($pid)) != substr($pid, "1") . ".") {
                    $nid = $node->parentNode->getAttribute("nextid");
                    $node->parentNode->setAttribute("nextid", $nid + 1);
                    $node->setAttribute("id", $newid = ($pid . "." . $nid));
                    printf("// \tid '%s' (child of '%s') fixed to '%s'\n", $id, $pid, $newid);
                    $id = str_replace(".", "d", $id) . "d";
                    $newid = str_replace(".", "d", $newid) . "d";
                    $sql = "UPDATE thit SET value = :newid WHERE value = :oldid";

                    $stmt = $connbas->prepare($sql);
                    $stmt->execute([':newid' => $newid, ':oldid' => $id]);
                    $stmt->closeCursor();
                }
            }
        }
        for ($n = $node->firstChild; $n; $n = $n->nextSibling)
            $this->fixIds($connbas, $n);
    }
}
