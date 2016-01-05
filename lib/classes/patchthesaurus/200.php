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

class patchthesaurus_200 implements patchthesaurus_interface
{
    public function patch($version, \DOMDocument $domct, \DOMDocument $domth, Connection $connbas, \unicode $unicode)
    {
        if ($version == "2.0.0") {
            $th = $domth->documentElement;
            $ct = $domct->documentElement;

            $xp = new DOMXPath($domth);

            $te = $xp->query("/thesaurus//te");
            for ($i = 0; $i < $te->length; $i ++) {
                $id = $te->item($i)->getAttribute("id");
                if ($id[0] >= "0" && $id[0] <= "9")
                    $te->item($i)->setAttribute("id", "T" . $id);
            }
            $ct->setAttribute("version", $version = "2.0.1");
            $th->setAttribute("version", $version = "2.0.1");
            $th->setAttribute("modification_date", date("YmdHis"));
            $sql = "UPDATE thit SET value=CONCAT('T',value) WHERE LEFT(value,1)>='0' AND LEFT(value,1)<='9'";
            $stmt = $connbas->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();
            $version = "2.0.1";
        }

        return($version);
    }
}
