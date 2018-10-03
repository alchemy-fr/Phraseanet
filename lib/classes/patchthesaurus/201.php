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

class patchthesaurus_201 implements patchthesaurus_interface
{
    public function patch($version, \DOMDocument $domct, \DOMDocument $domth, Connection $connbas, \unicode $unicode)
    {
        if ($version == "2.0.1") {
            $th = $domth->documentElement;
            $ct = $domct->documentElement;

            $xp = new DOMXPath($domth);
            $te = $xp->query("/thesaurus//te");
            for ($i = 0; $i < $te->length; $i ++) {
                $id = $te->item($i)->getAttribute("id");
                $nid = (int) ($te->item($i)->getAttribute("nextid"));
                for ($n = $te->item($i)->firstChild; $n; $n = $n->nextSibling) {
                    if ($n->nodeName == "sy") {
                        $n->setAttribute("id", $id . "." . $nid);
                        $te->item($i)->setAttribute("nextid",  ++ $nid);
                    }
                }
            }

            $sql = "UPDATE record SET status=status & ~2";
            $stmt = $connbas->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();

            $sql = "DELETE FROM thit";
            $stmt = $connbas->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();

            $domct = new DOMDocument();
            $domct->load(__DIR__ . "/../../../lib/conf.d/blank_cterms.xml");
            $ct = $domct->documentElement;
            $ct->setAttribute("creation_date", $now = date("YmdHis"));
            $ct->setAttribute("modification_date", $now);

            $ct->setAttribute("version", $version = "2.0.2");
            $th->setAttribute("version", $version = "2.0.2");
            $th->setAttribute("modification_date", date("YmdHis"));
            $version = "2.0.2";
        }

        return($version);
    }
}
