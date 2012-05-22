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
class patch_370 implements patchInterface
{
    /**
     *
     * @var string
     */
    private $release = '3.7.0.0.a2';

    /**
     *
     * @var Array
     */
    private $concern = array(base::DATA_BOX);

    /**
     *
     * @return string
     */
    public function get_release()
    {
        return $this->release;
    }

    public function require_all_upgrades()
    {
        return false;
    }

    /**
     *
     * @return Array
     */
    public function concern()
    {
        return $this->concern;
    }

    public function apply(base &$databox)
    {
        $conn = $databox->get_connection();

        $sql = 'SELECT value FROM pref WHERE prop = "structure"';
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ( ! $result) {
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
                $acodec->nodeValue = 'libfaac';
            }
        }

        $sql = 'UPDATE pref SET value = :structure WHERE prop = "structure"';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':structure' => $DOMDocument->saveXML()));
        $stmt->closeCursor();

        return true;
    }
}

