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

class patchthesaurus_100 implements patchthesaurus_interface
{
    public function patch($version, \DOMDocument $domct, \DOMDocument $domth, Connection $connbas, \unicode $unicode)
    {
        if ($version == "") {
            $th = $domth->documentElement;
            $ct = $domct->documentElement;

            $th->setAttribute("id", "0");

            $xp = new DOMXPath($domth);
            $te = $xp->query("/thesaurus/te");
            if ($te->length > 0) {
                $te0 = $te->item(0);
                $th->setAttribute("nextid", $te0->getAttribute("nextid"));
                $te = $xp->query("te", $te0);
                $te1 = [];
                for ($i = 0; $i < $te->length; $i ++) {
                    $te1[] = $te->item($i);
                }
                foreach ($te1 as $tei) {
                    $th->appendChild($tei);
                    $this->fixThesaurus2($domth, $tei, 0, $unicode);
                }
                $te0->parentNode->removeChild($te0);
            }
            $ct->setAttribute("version", $version = "2.0.0");
            $th->setAttribute("version", "2.0.0");
            $th->setAttribute("creation_date", $now = date("YmdHis"));
            $th->setAttribute("modification_date", $now);
            $version = "2.0.0";
        }

        return($version);
    }

    private function fixThesaurus2(&$domth, &$tenode, $depth, \unicode $unicode)
    {
        $sy = $tenode->appendChild($domth->createElement("sy"));
        $sy->setAttribute("lng", $v = $tenode->getAttribute("lng"));
        $sy->setAttribute("v", $v = $tenode->getAttribute("v"));
        $sy->setAttribute("w", $unicode->remove_indexer_chars($v));
        if (($k = $tenode->getAttribute("k")) != "")
            $sy->setAttribute("k", $k);
        $tenode->removeAttribute("lng");
        $tenode->removeAttribute("v");
        $tenode->removeAttribute("w");
        $tenode->removeAttribute("k");
        if ($tenode->getAttribute("nextid") == "") {
            $tenode->setAttribute("nextid", "0");
        }
        $todel = [];
        for ($n = $tenode->firstChild; $n; $n = $n->nextSibling) {
            if ($n->nodeName == "ta")
                $todel[] = $n;
            if ($n->nodeName == "te")
                $this->fixThesaurus2($domth, $n, $depth + 1, $unicode);
        }
        foreach ($todel as $n) {
            $n->parentNode->removeChild($n);
        }
    }
}
