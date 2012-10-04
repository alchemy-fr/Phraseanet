<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use MediaAlchemyst\Specification\Image as ImageSpecification;
use MediaAlchemyst\Alchemyst;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

/**
 *
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class databox_status
{
    /**
     *
     * @var Array
     */
    private static $_status = array();

    /**
     *
     * @var Array
     */
    protected static $_statuses;

    /**
     *
     * @var Array
     */
    private $status = array();

    /**
     *
     * @var string
     */
    private $path = '';

    /**
     *
     * @var string
     */
    private $url = '';

    /**
     *
     * @param  int    $sbas_id
     * @return status
     */
    private function __construct(Application $app, $sbas_id)
    {
        $this->status = array();

        $path = $url = false;

        $sbas_params = phrasea::sbas_params($app);

        if ( ! isset($sbas_params[$sbas_id])) {
            return;
        }

        $path = $this->path = $app['phraseanet.registry']->get('GV_RootPath') . "config/status/" . urlencode($sbas_params[$sbas_id]["host"]) . "-" . urlencode($sbas_params[$sbas_id]["port"]) . "-" . urlencode($sbas_params[$sbas_id]["dbname"]);
        $url = $this->url = "/custom/status/" . urlencode($sbas_params[$sbas_id]["host"]) . "-" . urlencode($sbas_params[$sbas_id]["port"]) . "-" . urlencode($sbas_params[$sbas_id]["dbname"]);

        $databox = $app['phraseanet.appbox']->get_databox((int) $sbas_id);
        $xmlpref = $databox->get_structure();
        $sxe = simplexml_load_string($xmlpref);

        if ($sxe) {

            foreach ($sxe->statbits->bit as $sb) {
                $bit = (int) ($sb["n"]);
                if ($bit < 4 && $bit > 63)
                    continue;

                $this->status[$bit]["name"] = (string) ($sb);
                $this->status[$bit]["labeloff"] = (string) $sb['labelOff'];
                $this->status[$bit]["labelon"] = (string) $sb['labelOn'];

                $this->status[$bit]["img_off"] = null;
                $this->status[$bit]["img_on"] = null;

                if (is_file($path . "-stat_" . $bit . "_0.gif")) {
                    $this->status[$bit]["img_off"] = $url . "-stat_" . $bit . "_0.gif";
                    $this->status[$bit]["path_off"] = $path . "-stat_" . $bit . "_0.gif";
                }
                if (is_file($path . "-stat_" . $bit . "_1.gif")) {
                    $this->status[$bit]["img_on"] = $url . "-stat_" . $bit . "_1.gif";
                    $this->status[$bit]["path_on"] = $path . "-stat_" . $bit . "_1.gif";
                }

                $this->status[$bit]["searchable"] = isset($sb['searchable']) ? (int) $sb['searchable'] : 0;
                $this->status[$bit]["printable"] = isset($sb['printable']) ? (int) $sb['printable'] : 0;
            }
        }
        ksort($this->status);

        return $this;
    }

    public static function getStatus(Application $app, $sbas_id)
    {

        if ( ! isset(self::$_status[$sbas_id]))
            self::$_status[$sbas_id] = new databox_status($app, $sbas_id);

        return self::$_status[$sbas_id]->status;
    }

    public static function getDisplayStatus(Application $app)
    {
        if (self::$_statuses) {
            return self::$_statuses;
        }

        $sbas_ids = $app['phraseanet.user']->ACL()->get_granted_sbas();

        $statuses = array();

        foreach ($sbas_ids as $databox) {
            try {
                $statuses[$databox->get_sbas_id()] = $databox->get_statusbits();
            } catch (Exception $e) {

            }
        }

        self::$_statuses = $statuses;

        return self::$_statuses;
    }

    public static function getSearchStatus(Application $app)
    {
        $statuses = array();

        $sbas_ids = $app['phraseanet.user']->ACL()->get_granted_sbas();

        $see_all = array();

        foreach ($sbas_ids as $databox) {
            $see_all[$databox->get_sbas_id()] = false;

            foreach ($databox->get_collections() as $collection) {
                if ($app['phraseanet.user']->ACL()->has_right_on_base($collection->get_base_id(), 'chgstatus')) {
                    $see_all[$databox->get_sbas_id()] = true;
                    break;
                }
            }
            try {
                $statuses[$databox->get_sbas_id()] = $databox->get_statusbits();
            } catch (Exception $e) {

            }
        }

        $stats = array();

        foreach ($statuses as $sbas_id => $status) {

            $see_this = isset($see_all[$sbas_id]) ? $see_all[$sbas_id] : false;

            if ($app['phraseanet.user']->ACL()->has_right_on_sbas($sbas_id, 'bas_modify_struct')) {
                $see_this = true;
            }

            foreach ($status as $bit => $props) {

                if ($props['searchable'] == 0 && ! $see_this)
                    continue;

                $set = false;
                if (isset($stats[$bit])) {
                    foreach ($stats[$bit] as $k => $s_desc) {
                        if (mb_strtolower($s_desc['labelon']) == mb_strtolower($props['labelon'])
                            && mb_strtolower($s_desc['labeloff']) == mb_strtolower($props['labeloff'])) {
                            $stats[$bit][$k]['sbas'][] = $sbas_id;
                            $set = true;
                        }
                    }
                    if ( ! $set) {
                        $stats[$bit][] = array(
                            'sbas' => array($sbas_id),
                            'labeloff' => $props['labeloff'],
                            'labelon'  => $props['labelon'],
                            'imgoff'   => $props['img_off'],
                            'imgon'    => $props['img_on']
                        );
                        $set = true;
                    }
                }

                if ( ! $set) {
                    $stats[$bit] = array(
                        array(
                            'sbas' => array($sbas_id),
                            'labeloff' => $props['labeloff'],
                            'labelon'  => $props['labelon'],
                            'imgoff'   => $props['img_off'],
                            'imgon'    => $props['img_on']
                        )
                    );
                }
            }
        }

        return $stats;
    }

    public static function getPath(Application $app, $sbas_id)
    {
        if ( ! isset(self::$_status[$sbas_id])) {
            self::$_status[$sbas_id] = new databox_status($app, $sbas_id);
        }

        return self::$_status[$sbas_id]->path;
    }

    public static function getUrl(Application $app, $sbas_id)
    {
        if ( ! isset(self::$_status[$sbas_id])) {
            self::$_status[$sbas_id] = new databox_status($app, $sbas_id);
        }

        return self::$_status[$sbas_id]->url;
    }

    public static function deleteStatus(Application $app, \databox $databox, $bit)
    {
        $status = self::getStatus($app, $sbas_id);

        if (isset($status[$bit])) {
            $connbas = connection::getPDOConnection($app, $sbas_id);

            $doc = $databox->get_dom_structure();
            if ($doc) {
                $xpath = $databox->get_xpath_structure();
                $entries = $xpath->query($q = "/record/statbits/bit[@n=" . $bit . "]");

                foreach ($entries as $sbit) {
                    if ($p = $sbit->previousSibling) {
                        if ($p->nodeType == XML_TEXT_NODE && $p->nodeValue == "\n\t\t")
                            $p->parentNode->removeChild($p);
                    }
                    if ($sbit->parentNode->removeChild($sbit)) {
                        $sql = 'UPDATE record SET status = status&(~(1<<' . $bit . '))';
                        $stmt = $connbas->prepare($sql);
                        $stmt->execute();
                        $stmt->closeCursor();
                    }
                }

                $databox->saveStructure($doc);

                if (null !== $status[$bit]['img_off']) {
                    $app['filesystem']->remove($status[$bit]['path_off']);
                }

                if (null !== $status[$bit]['img_on']) {
                    $app['filesystem']->remove($status[$bit]['path_on']);
                }

                unset(self::$_status[$sbas_id]->status[$bit]);

                return true;
            }
        }

        return false;
    }

    public static function updateStatus(Application $app, $sbas_id, $bit, $properties)
    {
         self::getStatus($app, $sbas_id);

        $databox = $app['phraseanet.appbox']->get_databox((int) $sbas_id);

        $doc = $databox->get_dom_structure($sbas_id);
        if ($doc) {
            $xpath = $databox->get_xpath_structure($sbas_id);
            $entries = $xpath->query("/record/statbits");
            if ($entries->length == 0) {
                $statbits = $doc->documentElement->appendChild($doc->createElement("statbits"));
            } else {
                $statbits = $entries->item(0);
            }

            if ($statbits) {
                $entries = $xpath->query("/record/statbits/bit[@n=" . $bit . "]");

                if ($entries->length >= 1) {
                    foreach ($entries as $k => $sbit) {
                        if ($p = $sbit->previousSibling) {
                            if ($p->nodeType == XML_TEXT_NODE && $p->nodeValue == "\n\t\t")
                                $p->parentNode->removeChild($p);
                        }
                        $sbit->parentNode->removeChild($sbit);
                    }
                }

                $sbit = $statbits->appendChild($doc->createElement("bit"));

                if ($n = $sbit->appendChild($doc->createAttribute("n"))) {
                    $n->value = $bit;
                    $sbit->appendChild($doc->createTextNode($properties['name']));
                }

                if ($labOn = $sbit->appendChild($doc->createAttribute("labelOn"))) {
                    $labOn->value = $properties['labelon'];
                }

                if ($searchable = $sbit->appendChild($doc->createAttribute("searchable"))) {
                    $searchable->value = $properties['searchable'];
                }

                if ($printable = $sbit->appendChild($doc->createAttribute("printable"))) {
                    $printable->value = $properties['printable'];
                }

                if ($labOff = $sbit->appendChild($doc->createAttribute("labelOff"))) {
                    $labOff->value = $properties['labeloff'];
                }
            }

            $databox->saveStructure($doc);

            self::$_status[$sbas_id]->status[$bit]["name"] = $properties['name'];
            self::$_status[$sbas_id]->status[$bit]["labelon"] = $properties['labelon'];
            self::$_status[$sbas_id]->status[$bit]["labeloff"] = $properties['labeloff'];
            self::$_status[$sbas_id]->status[$bit]["searchable"] = ! ! $properties['searchable'];
            self::$_status[$sbas_id]->status[$bit]["printable"] = ! ! $properties['printable'];

            if ( ! isset(self::$_status[$sbas_id]->status[$bit]['img_on'])) {
                self::$_status[$sbas_id]->status[$bit]['img_on'] = null;
            }

            if ( ! isset(self::$_status[$sbas_id]->status[$bit]['img_off'])) {
                self::$_status[$sbas_id]->status[$bit]['img_off'] = null;
            }
        }

        return false;
    }

    public static function deleteIcon(Application $app, $sbas_id, $bit, $switch)
    {
        $status = self::getStatus($app, $sbas_id);

        $switch = in_array($switch, array('on', 'off')) ? $switch : false;

        if ( ! $switch) {
            return false;
        }

        if ($status[$bit]['img_' . $switch]) {
            if (isset($status[$bit]['path_' . $switch])) {
                $app['filesystem']->remove($status[$bit]['path_' . $switch]);
            }

            $status[$bit]['img_' . $switch] = false;
            unset($status[$bit]['path_' . $switch]);
        }

        return true;
    }

    public static function updateIcon(Application $app, $sbas_id, $bit, $switch, UploadedFile $file)
    {
        $switch = in_array($switch, array('on', 'off')) ? $switch : false;

        if ( ! $switch) {
            throw new Exception_InvalidArgument();
        }

        $url = self::getUrl($app, $sbas_id);
        $path = self::getPath($app, $sbas_id);

        if ($file->getSize() >= 65535) {
            throw new Exception_Upload_FileTooBig();
        }

        if ( ! $file->isValid()) {
            throw new Exception_Upload_Error();
        }

        self::deleteIcon($app, $sbas_id, $bit, $switch);

        $name = "-stat_" . $bit . "_" . ($switch == 'on' ? '1' : '0') . ".gif";

        try {
            $file = $file->move($app['phraseanet.registry']->get('GV_RootPath') . "config/status/", $path.$name);
        } catch (FileException $e) {
            throw new Exception_Upload_CannotWriteFile();
        }

        $custom_path = $app['phraseanet.registry']->get('GV_RootPath') . 'www/custom/status/';

        $app['filesystem']->mkdir($custom_path, 0750);

        //resize status icon 16x16px
        $imageSpec = new ImageSpecification();
        $imageSpec->setResizeMode(ImageSpecification::RESIZE_MODE_OUTBOUND);
        $imageSpec->setDimensions(16, 16);

        $filePath = sprintf("%s%s", $path, $name);
        $destPath = sprintf("%s%s", $custom_path, basename($path . $name));

        try {
            $app['media-alchemyst']
                ->open($filePath)
                ->turninto($destPath, $imageSpec)
                ->close();
        } catch (\MediaAlchemyst\Exception $e) {

        }

        self::$_status[$sbas_id]->status[$bit]['img_' . $switch] = $url . $name;
        self::$_status[$sbas_id]->status[$bit]['path_' . $switch] = $filePath;

        return true;
    }

    public static function operation_and(Application $app, $stat1, $stat2)
    {
        $conn = connection::getPDOConnection($app);

        $status = '0';

        if (substr($stat1, 0, 2) === '0x') {
            $stat1 = self::hex2bin($app, substr($stat1, 2));
        }
        if (substr($stat2, 0, 2) === '0x') {
            $stat2 = self::hex2bin($app, substr($stat2, 2));
        }

        $sql = 'select bin(0b' . trim($stat1) . ' & 0b' . trim($stat2) . ') as result';

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ($row) {
            $status = $row['result'];
        }

        return $status;
    }

    public static function operation_and_not(Application $app, $stat1, $stat2)
    {
        $conn = connection::getPDOConnection($app);

        $status = '0';

        if (substr($stat1, 0, 2) === '0x') {
            $stat1 = self::hex2bin($app, substr($stat1, 2));
        }
        if (substr($stat2, 0, 2) === '0x') {
            $stat2 = self::hex2bin($app, substr($stat2, 2));
        }

        $sql = 'select bin(0b' . trim($stat1) . ' & ~0b' . trim($stat2) . ') as result';

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ($row) {
            $status = $row['result'];
        }

        return $status;
    }

    public static function operation_or(Application $app, $stat1, $stat2)
    {
        $conn = connection::getPDOConnection($app);

        $status = '0';

        if (substr($stat1, 0, 2) === '0x') {
            $stat1 = self::hex2bin($app, substr($stat1, 2));
        }
        if (substr($stat2, 0, 2) === '0x') {
            $stat2 = self::hex2bin($app, substr($stat2, 2));
        }

        $sql = 'select bin(0b' . trim($stat1) . ' | 0b' . trim($stat2) . ') as result';

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ($row) {
            $status = $row['result'];
        }

        return $status;
    }

    public static function dec2bin(Application $app, $status)
    {
        $status = (string) $status;

        if ( ! ctype_digit($status)) {
            throw new \Exception('Non-decimal value');
        }

        $conn = connection::getPDOConnection($app);

        $sql = 'select bin(' . $status . ') as result';

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $status = '0';

        if ($row) {
            $status = $row['result'];
        }

        return $status;
    }

    public static function hex2bin(Application $app, $status)
    {
        $status = (string) $status;
        if (substr($status, 0, 2) === '0x') {
            $status = substr($status, 2);
        }

        if ( ! ctype_xdigit($status)) {
            throw new \Exception('Non-hexadecimal value');
        }

        $conn = connection::getPDOConnection($app);

        $sql = 'select BIN( CAST( 0x' . trim($status) . ' AS UNSIGNED ) ) as result';

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $status = '0';

        if ($row) {
            $status = $row['result'];
        }

        return $status;
    }
}
