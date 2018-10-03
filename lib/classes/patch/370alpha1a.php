<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

class patch_370alpha1a extends patchAbstract
{
    /** @var string */
    private $release = '3.7.0-alpha.1';

    /** @var array */
    private $concern = [base::DATA_BOX];

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
    public function require_all_upgrades()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function concern()
    {
        return $this->concern;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(base $databox, Application $app)
    {
        $conn = $databox->get_connection();

        $sql = 'SELECT value FROM pref WHERE prop = "structure"';
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if (! $result) {
            throw new \RuntimeException('Unable to find structure');
        }

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

        $sql = 'UPDATE pref SET value = :structure WHERE prop = "structure"';
        $stmt = $conn->prepare($sql);
        $stmt->execute([':structure' => $DOMDocument->saveXML()]);
        $stmt->closeCursor();

        return true;
    }
}
