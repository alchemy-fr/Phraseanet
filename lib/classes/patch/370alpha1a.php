<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

class patch_370alpha1a extends patchAbstract
{
    /** @var string */
    private $release = '3.7.0-alpha.1';

    /**
     * {@inheritdoc}
     */
    public function get_release()
    {
        return $this->release;
    }

    /**
     * {@inheritdoc}
     */
    public function getDoctrineMigrations()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function require_all_upgrades()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(\appbox $appbox, Application $app)
    {
        $conn = $appbox->get_connection();

        foreach ($appbox->get_databoxes() as $databox) {
            $sql = 'SELECT value FROM pref WHERE prop = "structure" AND sbas_id = :sbas_id';
            $stmt = $conn->prepare($sql);
            $stmt->execute([':sbas_id' => $databox->get_sbas_id()]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            foreach ($results as $result) {
                $DOMDocument = new DOMDocument();
                $DOMDocument->loadXML($result['value']);

                $XPath = new DOMXPath($DOMDocument);

                foreach ($XPath->query('/record/subdefs/subdefgroup/subdef/delay') as $delay) {
                    $delay->nodeValue = min(500, max(50, (int) $delay->nodeValue * 400));
                }

                foreach ($XPath->query('/record/subdefs/subdefgroup/subdef/acodc') as $acodec) {
                    if ($acodec->nodeValue == 'faac') {
                        $acodec->nodeValue = 'libvo_aacenc';
                    }
                }

                $sql = 'UPDATE pref SET value = :structure WHERE prop = "structure" AND sbas_id = :sbas_id';
                $stmt = $conn->prepare($sql);
                $stmt->execute([':structure' => $DOMDocument->saveXML(), ':sbas_id' => $databox->get_sbas_id()]);
                $stmt->closeCursor();
            }
        }

        return true;
    }
}
