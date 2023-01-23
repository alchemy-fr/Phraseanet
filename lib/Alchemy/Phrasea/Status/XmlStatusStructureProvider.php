<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Status;

// use Symfony\Component\Filesystem\Filesystem;
use Alchemy\Phrasea\Filesystem\PhraseanetFilesystem as Filesystem;


/**
 * Provides status structure definition from XML
 */
class XmlStatusStructureProvider implements StatusStructureProviderInterface
{
    protected $rootPath;
    protected $locales;

    public function __construct($rootPath, $locales, $fs = null)
    {
        $this->rootPath = $rootPath;
        $this->locales = $locales;
        $this->fs = $fs ?: new Filesystem();
    }

    public function getStructure(\databox $databox)
    {
        $statusStructure = new StatusStructure($databox);

        $xmlPref = $databox->get_structure();
        $sxe = simplexml_load_string($xmlPref);

        if ($sxe === false) {
            throw new \Exception('Failed to load database XML structure');
        }

        foreach ($sxe->statbits->bit as $sb) {
            $bit = (int) ($sb['n']);
            if ($bit < 4 && $bit > 31) {
                continue;
            }

            $status = [];

            $status['bit'] = $bit;

            $status['labeloff'] = (string) $sb['labelOff'];
            $status['labelon'] = (string) $sb['labelOn'];

            foreach ($this->locales as $code => $language) {
                $status['labels_on'][$code] = null;
                $status['labels_off'][$code] = null;
            }

            foreach ($sb->label as $label) {
                $status['labels_'.$label['switch']][(string) $label['code']] = (string) $label;
            }

            foreach ($this->locales as $code => $language) {
                $status['labels_on_i18n'][$code] = '' !== trim($status['labels_on'][$code]) ? $status['labels_on'][$code] : $status['labelon'];
                $status['labels_off_i18n'][$code] = '' !== trim($status['labels_off'][$code]) ? $status['labels_off'][$code] : $status['labeloff'];
            }

            $status['img_off'] = null;
            $status['img_on'] = null;

            if (is_file($statusStructure->getPath() . '-stat_' . $bit . '_0.gif')) {
                $status['img_off'] = $statusStructure->getUrl() . '-stat_' . $bit . '_0.gif?etag='.md5_file($statusStructure->getPath() . '-stat_' . $bit . '_0.gif');
                $status['path_off'] = $statusStructure->getPath() . '-stat_' . $bit . '_0.gif';
            }
            if (is_file($statusStructure->getPath() . '-stat_' . $bit . '_1.gif')) {
                $status['img_on'] = $statusStructure->getUrl() . '-stat_' . $bit . '_1.gif?etag='.md5_file($statusStructure->getPath() . '-stat_' . $bit . '_1.gif');
                $status['path_on'] = $statusStructure->getPath() . '-stat_' . $bit . '_1.gif';
            }

            $status['searchable'] = isset($sb['searchable']) ? (int) $sb['searchable'] : 0;
            $status['printable'] = isset($sb['printable']) ? (int) $sb['printable'] : 0;

            $statusStructure->setStatus($bit, $status);
        }

        return $statusStructure;
    }

    public function deleteStatus(StatusStructure $statusStructure, $bit)
    {
        $databox = $statusStructure->getDatabox();

        if (false === $statusStructure->hasStatus($bit)) {
            return false;
        }

        $doc = $databox->get_dom_structure();

        if (!$doc) {
            return false;
        }

        $xpath = $databox->get_xpath_structure();
        $entries = $xpath->query('/record/statbits/bit[@n=' . $bit . ']');

        foreach ($entries as $sbit) {
            if ($p = $sbit->previousSibling) {
                if ($p->nodeType == XML_TEXT_NODE && $p->nodeValue == '\n\t\t')
                    $p->parentNode->removeChild($p);
            }
            if ($sbit->parentNode->removeChild($sbit)) {
                $sql = 'UPDATE record SET status = status&(~(1<<' . $bit . '))';
                $stmt = $databox->get_connection()->prepare($sql);
                $stmt->execute();
                $stmt->closeCursor();
            }
        }

        $databox->saveStructure($doc);

        $status = $statusStructure->getStatus($bit);

        if (null !== $status['img_off']) {
            $this->fs->remove($status['path_off']);
        }

        if (null !== $status['img_on']) {
            $this->fs->remove($status['path_on']);
        }

        $statusStructure->removeStatus($bit);

        return $statusStructure;
    }

    public function updateStatus(StatusStructure $statusStructure, $bit, array $properties)
    {
        $databox = $statusStructure->getDatabox();

        if (false === $statusStructure->hasStatus($bit)) {
            $statusStructure->setStatus($bit, []);
        }

        $doc = $databox->get_dom_structure();

        if (!$doc) {
            return false;
        }

        $xpath = $databox->get_xpath_structure();
        $entries = $xpath->query('/record/statbits');

        if ($entries->length == 0) {
            $statbits = $doc->documentElement->appendChild($doc->createElement('statbits'));
        } else {
            $statbits = $entries->item(0);
        }

        if ($statbits) {
            $entries = $xpath->query('/record/statbits/bit[@n=' . $bit . ']');

            if ($entries->length >= 1) {
                foreach ($entries as $k => $sbit) {
                    if ($p = $sbit->previousSibling) {
                        if ($p->nodeType == XML_TEXT_NODE && $p->nodeValue == '\n\t\t')
                            $p->parentNode->removeChild($p);
                    }
                    $sbit->parentNode->removeChild($sbit);
                }
            }

            $sbit = $statbits->appendChild($doc->createElement('bit'));

            if ($n = $sbit->appendChild($doc->createAttribute('n'))) {
                $n->value = $bit;
            }

            if ($labOn = $sbit->appendChild($doc->createAttribute('labelOn'))) {
                $labOn->value = $properties['labelon'];
            }

            if ($searchable = $sbit->appendChild($doc->createAttribute('searchable'))) {
                $searchable->value = $properties['searchable'];
            }

            if ($printable = $sbit->appendChild($doc->createAttribute('printable'))) {
                $printable->value = $properties['printable'];
            }

            if ($labOff = $sbit->appendChild($doc->createAttribute('labelOff'))) {
                $labOff->value = $properties['labeloff'];
            }

            foreach ($properties['labels_off'] as $code => $label) {
                $labelTag = $sbit->appendChild($doc->createElement('label'));
                $switch = $labelTag->appendChild($doc->createAttribute('switch'));
                $switch->value = 'off';
                $codeTag = $labelTag->appendChild($doc->createAttribute('code'));
                $codeTag->value = $code;
                $labelTag->appendChild($doc->createTextNode($label));
            }

            foreach ($properties['labels_on'] as $code => $label) {
                $labelTag = $sbit->appendChild($doc->createElement('label'));
                $switch = $labelTag->appendChild($doc->createAttribute('switch'));
                $switch->value = 'on';
                $codeTag = $labelTag->appendChild($doc->createAttribute('code'));
                $codeTag->value = $code;
                $labelTag->appendChild($doc->createTextNode($label));
            }
        }

        $databox->saveStructure($doc);

        $status = $statusStructure->getStatus($bit);

        $status['labelon'] = $properties['labelon'];
        $status['labeloff'] = $properties['labeloff'];
        $status['searchable'] = (Boolean) $properties['searchable'];
        $status['printable'] = (Boolean) $properties['printable'];

//        if (!isset($properties['img_on'])) {
//            $status['img_on'] = null;
//        }
//
//        if (!isset($properties['img_off'])) {
//            $status['img_off'] = null;
//        }

        $statusStructure->setStatus($bit, $status);

        return $statusStructure;
    }
}
