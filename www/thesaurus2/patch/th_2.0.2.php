<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class patch_th_2_0_2
{

    function patch($version, &$domct, &$domth, connection_pdo &$connbas)
    {
        if ($version == "2.0.2") {
            $th = $domth->documentElement;
            $ct = $domct->documentElement;

            $sql = "ALTER TABLE `pref` ADD `cterms_moddate` DATETIME";
            $stmt = $connbas->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();

            $sql = "ALTER TABLE `pref` ADD `thesaurus_moddate` DATETIME";
            $stmt = $connbas->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();

            $sql = "UPDATE pref SET thesaurus_moddate = :date1, cterms_moddate = :date2";
            $stmt = $connbas->prepare($sql);
            $stmt->execute(array(':date1' => $th->getAttribute("modification_date"), ':date2' => $ct->getAttribute("modification_date")));
            $stmt->closeCursor();

            $ct->setAttribute("version", $version = "2.0.3");
            $th->setAttribute("version", $version = "2.0.3");
            $th->setAttribute("modification_date", date("YmdHis"));

            $version = "2.0.3";
        }

        return($version);
    }
}
