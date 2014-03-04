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

class patch_380alpha9a extends patchAbstract
{
    /** @var string */
    private $release = '3.8.0-alpha.9';

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
        return true;
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
    public function apply(\appbox $appbox, Application $app)
    {
        foreach ($appbox->get_databoxes() as $databox) {
            $sxe = $databox->get_sxml_structure();

            if ($sxe !== false) {
                foreach ($sxe->statbits->bit as $sb) {
                    $bit = (int) ($sb["n"]);
                    if ($bit < 4 && $bit > 31) {
                        continue;
                    }

                    $name = (string) $sb;
                    $labelOff = (string) $sb['labelOff'];
                    $labelOn = (string) $sb['labelOn'];

                    $this->status[$bit]["labeloff"] =  $labelOff ? : 'no-' . $name;
                    $this->status[$bit]["labelon"] = $labelOn ? : 'ok-' . $name;
                }
            }

            $dom = new \DOMDocument();
            $dom->loadXML($sxe->asXML());

            $databox->saveStructure($dom);
        }

        return true;
    }
}
