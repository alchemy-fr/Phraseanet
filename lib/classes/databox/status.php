<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\SearchEngine\Elastic\RecordHelper;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use MediaAlchemyst\Specification\Image as ImageSpecification;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Alchemy\Phrasea\Model\RecordInterface;

class databox_status
{
    public static function getSearchStatus(Application $app)
    {
        $structures = $stats = [];
        foreach ($app->getAclForUser($app->getAuthenticatedUser())->get_granted_sbas() as $databox) {
            $see_all = false;
            foreach ($databox->get_collections() as $collection) {
                if ($app->getAclForUser($app->getAuthenticatedUser())->has_right_on_base($collection->get_base_id(), 'chgstatus')) {
                    $see_all = true;
                    break;
                }
            }
            $status = $databox->getStatusStructure()->toArray();
            if (!$see_all) {
                $status = array_filter($status, function ($statusbit) {
                    return (bool)$statusbit['searchable'];
                });
            }
            ksort($status);

            $structures[$databox->get_sbas_id()] = array(
                'name' => $databox->get_label($app['locale']),
                'status'=>$status
            );
        }
        ksort($structures);
file_put_contents("/tmp/phraseanet-log.txt", sprintf("%s (%d) %s\n", __FILE__, __LINE__, var_export($structures, true)), FILE_APPEND);

        return $structures;

        foreach ($structures as $databox_id => $structure) {
            foreach($structure as $bit => $status) {
                $key = RecordHelper::normalizeFlagKey($status['labelon']);

                if (isset($stats[$key])) {
                    $status = $stats[$key];
                }

                $status['sbas'][] = $databox_id;
                $status['bit'] = $bit;

                $stats[$key] = $status;
            }
        }
file_put_contents("/tmp/phraseanet-log.txt", sprintf("%s (%d) %s\n", __FILE__, __LINE__, var_export($stats, true)), FILE_APPEND);
        return $stats;
    }

    public static function no_getSearchStatus(Application $app)
    {
        $see_all = $structures = $stats = [];
        foreach ($app->getAclForUser($app->getAuthenticatedUser())->get_granted_sbas() as $databox) {
            $see_all[$databox->get_sbas_id()] = false;
            foreach ($databox->get_collections() as $collection) {
                if ($app->getAclForUser($app->getAuthenticatedUser())->has_right_on_base($collection->get_base_id(), 'chgstatus')) {
                    $see_all[$databox->get_sbas_id()] = true;
                    break;
                }
            }
            $structures[$databox->get_sbas_id()] = $databox->getStatusStructure();
        }

        // order the result by bit number, ...
        $tbits = [];
        foreach ($structures as $databox_id => $structure) {
            if (false === $see_all[$databox_id]) {
                $structure = array_filter($structure->toArray(), function ($status) {
                    return (bool)$status['searchable'];
                });
            }
            foreach($structure as $bit => $status) {
                if(!array_key_exists($bit, $tbits)) {
                    $tbits[$bit] = [];
                }
                $tbits[$bit][$databox_id] = $status;
            }
        }
        ksort($tbits);
// file_put_contents("/tmp/phraseanet-log.txt", sprintf("%s (%d) %s\n", __FILE__, __LINE__, var_export($tbits, true)), FILE_APPEND);

        // ... but group the status with same name
        foreach($tbits as $bit=>$tbas) {
            foreach($tbas as $sbas_id=>$status) {
                // the group-key is the "labelon"
                $key = RecordHelper::normalizeFlagKey($status['labelon']);
                if(!array_key_exists($key, $stats)) {
                    $stats[$key] = $status;
                    $stats[$key]['sbas'] = [];
                    // $stats[$key]['tbas'] = [];
                }
                else {
                    /*
                    if($stats[$key]['bit'] != $bit) {
                        // error : in es, 2 sb with same name MUST be at the same position
                        throw new UnexpectedValueException(
                            sprintf("statusbit '%s' found at position %d and position %d"
                                , $key, $stats[$key]['bit'], $bit
                            )
                        );
                    }
                    */
                }
                $stats[$key]['sbas'][] = $sbas_id;
                // $stats[$key]['tbas'][$sbas_id] = $status;
            }
        }
        file_put_contents("/tmp/phraseanet-log.txt", sprintf("%s (%d) %s\n", __FILE__, __LINE__, var_export($stats, true)), FILE_APPEND);
        return $stats;



        foreach ($structures as $databox_id => $structure) {
            file_put_contents("/tmp/phraseanet-log.txt", sprintf("%s (%d) %s\n%s\n", __FILE__, __LINE__, var_export($databox_id, true), var_export($structure->toArray(), true)), FILE_APPEND);
            if (false === $see_all[$databox_id]) {
                $structure = array_filter($structure->toArray(), function ($status) {
                    return (bool) $status['searchable'];
                });
            }

            foreach($structure as $bit => $status) {
                $key = RecordHelper::normalizeFlagKey($status['labelon']);
                file_put_contents("/tmp/phraseanet-log.txt", sprintf("%s (%d) %d:%s\n", __FILE__, __LINE__, $bit, $key), FILE_APPEND);

                if (isset($stats[$key])) {
                    $status = $stats[$key];
                }

                $status['sbas'][] = $databox_id;
                $status['bit'] = $bit;

                $stats[$key] = $status;
            }
        }
        file_put_contents("/tmp/phraseanet-log.txt", sprintf("%s (%d) %s\n", __FILE__, __LINE__, var_export($stats, true)), FILE_APPEND);
        return $stats;
    }

    public static function deleteIcon(Application $app, $databox_id, $bit, $switch)
    {
        $databox = $app->findDataboxById($databox_id);

        $statusStructure = $app['factory.status-structure']->getStructure($databox);

        if (!$statusStructure->hasStatus($bit)) {
            throw new InvalidArgumentException(sprintf('bit %s does not exists on database %s', $bit, $statusStructure->getDatabox()->get_dbname()));
        }

        $status = $statusStructure->getStatus($bit);

        $switch = in_array($switch, ['on', 'off']) ? $switch : false;

        if (!$switch) {
            return false;
        }

        if ($status['img_' . $switch]) {
            if (isset($status['path_' . $switch])) {
                $app['filesystem']->remove($status['path_' . $switch]);
            }

            $status['img_' . $switch] = false;
            unset($status['path_' . $switch]);
        }

        return true;
    }

    public static function updateIcon(Application $app, $databox_id, $bit, $switch, UploadedFile $file)
    {
        $databox = $app->findDataboxById($databox_id);

        $statusStructure = $app['factory.status-structure']->getStructure($databox);

        if (!$statusStructure->hasStatus($bit)) {
            throw new InvalidArgumentException(sprintf('bit %s does not exists', $bit));
        }

        $status = $statusStructure->getStatus($bit);

        $switch = in_array($switch, ['on', 'off']) ? $switch : false;

        if (!$switch) {
            throw new Exception_InvalidArgument();
        }

        $url = $statusStructure->getUrl();
        $path = $statusStructure->getPath();

        if ($file->getSize() >= 65535) {
            throw new Exception_Upload_FileTooBig();
        }

        if ( ! $file->isValid()) {
            throw new Exception_Upload_Error();
        }

        self::deleteIcon($app, $databox_id, $bit, $switch);

        $name = "-stat_" . $bit . "_" . ($switch == 'on' ? '1' : '0') . ".gif";

        try {
            $file = $file->move($app['root.path'] . "/config/status/", $path.$name);
        } catch (FileException $e) {
            throw new Exception_Upload_CannotWriteFile();
        }

        $custom_path = $app['root.path'] . '/www/custom/status/';

        $app['filesystem']->mkdir($custom_path, 0750);

        //resize status icon 16x16px
        $imageSpec = new ImageSpecification();
        $imageSpec->setResizeMode(ImageSpecification::RESIZE_MODE_OUTBOUND);
        $imageSpec->setDimensions(16, 16);

        $filePath = sprintf("%s%s", $path, $name);
        $destPath = sprintf("%s%s", $custom_path, basename($path . $name));

        try {
            $app['media-alchemyst']->turninto($filePath, $destPath, $imageSpec);
        } catch (\MediaAlchemyst\Exception $e) {

        }

        $status['img_' . $switch] = $url . $name;
        $status['path_' . $switch] = $filePath;

        return true;
    }

    public static function operation_and($stat1, $stat2)
    {
        if (substr($stat1, 0, 2) === '0x') {
            $stat1 = self::hex2bin(substr($stat1, 2));
        }
        if (substr($stat2, 0, 2) === '0x') {
            $stat2 = self::hex2bin(substr($stat2, 2));
        }

        return dechex(hexdec($stat1) & hexdec($stat2));
    }

    /**
     * compute ((0 M s1) M s2) where M is the "mask" operator
     * nb : s1,s2 are binary mask strings as "01x0xx1xx0x", no other format (hex) supported
     *
     * @param $stat1 a binary mask "010x1xx0.." STRING
     * @param $stat2 a binary mask "x100x1..." STRING
     *
     * @return string
     */
    public static function operation_mask($stat1, $stat2)
    {
        $stat1 = str_pad($stat1, 32, '0', STR_PAD_LEFT);
        $stat2 = str_pad($stat2, 32, '0', STR_PAD_LEFT);
        $stat1_or  = bindec(trim(str_replace("x", "0", $stat1)));
        $stat1_and = bindec(trim(str_replace("x", "1", $stat1)));
        $stat2_or  = bindec(trim(str_replace("x", "0", $stat2)));
        $stat2_and = bindec(trim(str_replace("x", "1", $stat2)));

        return decbin((((0 | $stat1_or) & $stat1_and) | $stat2_or) & $stat2_and);
    }

    public static function operation_and_not($stat1, $stat2)
    {
        if (substr($stat1, 0, 2) === '0x') {
            $stat1 = self::hex2bin(substr($stat1, 2));
        }
        if (substr($stat2, 0, 2) === '0x') {
            $stat2 = self::hex2bin(substr($stat2, 2));
        }
        $stat1 = str_pad($stat1, 32, '0', STR_PAD_LEFT);
        $stat2 = str_pad($stat2, 32, '0', STR_PAD_LEFT);

        return decbin(bindec($stat1) & ~bindec($stat2));
    }

    public static function operation_or($stat1, $stat2)
    {
        if (substr($stat1, 0, 2) === '0x') {
            $stat1 = self::hex2bin(substr($stat1, 2));
        }
        if (substr($stat2, 0, 2) === '0x') {
            $stat2 = self::hex2bin(substr($stat2, 2));
        }
        $stat1 = str_pad($stat1, 32, '0', STR_PAD_LEFT);
        $stat2 = str_pad($stat2, 32, '0', STR_PAD_LEFT);

        return decbin(bindec($stat1) | bindec($stat2));
    }

    public static function dec2bin($status)
    {
        $status = (string) $status;

        if ( ! ctype_digit($status)) {
            throw new \Exception(sprintf('`%s`is non-decimal value', $status));
        }

        return decbin($status);
    }

    public static function hex2bin($status)
    {
        $status = (string) $status;
        if (substr($status, 0, 2) === '0x') {
            $status = substr($status, 2);
        }

        if ( ! ctype_xdigit($status)) {
            throw new \Exception('Non-hexadecimal value');
        }

        return base_convert($status, 16, 2);
    }

    public static function bitIsSet($bitValue, $nthBit)
    {
        return (bool) ($bitValue & (1 << $nthBit));
    }
}
