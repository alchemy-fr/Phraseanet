<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration;
use Alchemy\Phrasea\Exception\RuntimeException;

class phrasea
{
    private static $_bas2sbas = false;
    private static $_sbas_names = false;
    private static $_coll2bas = false;
    private static $_bas2coll = false;
    private static $_bas_names = false;
    private static $_sbas_params = false;

    const CACHE_BAS_2_SBAS = 'bas_2_sbas';
    const CACHE_COLL_2_BAS = 'coll_2_bas';
    const CACHE_BAS_2_COLL = 'bas_2_coll';
    const CACHE_BAS_NAMES = 'bas_names';
    const CACHE_SBAS_NAMES = 'sbas_names';
    const CACHE_SBAS_FROM_BAS = 'sbas_from_bas';
    const CACHE_SBAS_PARAMS = 'sbas_params';

    public static function is_scheduler_started(Application $app)
    {
        $retval = false;
        $conn = connection::getPDOConnection($app);
        $sql = 'SELECT schedstatus FROM sitepreff';

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ($row && $row['schedstatus'] != 'stopped') {
            $retval = true;
        }

        return $retval;
    }

    public static function clear_sbas_params(Application $app)
    {
        self::$_sbas_params = null;
        $app['phraseanet.appbox']->delete_data_from_cache(self::CACHE_SBAS_PARAMS);

        return true;
    }

    public static function sbas_params(Application $app)
    {
        if (self::$_sbas_params) {
            return self::$_sbas_params;
        }

        try {
            self::$_sbas_params = $app['phraseanet.appbox']->get_data_from_cache(self::CACHE_SBAS_PARAMS);

            return self::$_sbas_params;
        } catch (Exception $e) {

        }

        self::$_sbas_params = array();

        $sql = 'SELECT sbas_id, host, port, user, pwd, dbname FROM sbas';
        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row) {
            self::$_sbas_params[$row['sbas_id']] = $row;
        }

        $app['phraseanet.appbox']->set_data_to_cache(self::$_sbas_params, self::CACHE_SBAS_PARAMS);

        return self::$_sbas_params;
    }

    public static function guest_allowed(Application $app)
    {
        $usr_id = User_Adapter::get_usr_id_from_login($app, 'invite');
        if (!$usr_id) {
            return false;
        }
        $user = User_Adapter::getInstance($usr_id, $app);

        return count($user->ACL()->get_granted_base()) > 0;
    }

    public static function use_i18n($locale, $textdomain = 'phraseanet')
    {
        $codeset = "UTF-8";

        putenv('LANG=' . $locale . '.' . $codeset);
        putenv('LANGUAGE=' . $locale . '.' . $codeset);
        bind_textdomain_codeset($textdomain, 'UTF-8');

        bindtextdomain($textdomain, __DIR__ . '/../../locale/');
        setlocale(LC_ALL
            , $locale . '.UTF-8'
            , $locale . '.UTF8'
            , $locale . '.utf-8'
            , $locale . '.utf8');
        textdomain($textdomain);
    }

    public static function modulesName($array_modules)
    {
        $array = array();

        $modules = array(
            1 => _('admin::monitor: module production'),
            2 => _('admin::monitor: module client'),
            3 => _('admin::monitor: module admin'),
            4 => _('admin::monitor: module report'),
            5 => _('admin::monitor: module thesaurus'),
            6 => _('admin::monitor: module comparateur'),
            7 => _('admin::monitor: module validation'),
            8 => _('admin::monitor: module upload')
        );

        foreach ($array_modules as $a) {
            if (isset($modules[$a]))
                $array[] = $modules[$a];
        }

        return $array;
    }

    public static function sbasFromBas(Application $app, $base_id)
    {
        if (!self::$_bas2sbas) {
            try {
                self::$_bas2sbas = $app['phraseanet.appbox']->get_data_from_cache(self::CACHE_SBAS_FROM_BAS);
            } catch (Exception $e) {
                $sql = 'SELECT base_id, sbas_id FROM bas';
                $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
                $stmt->execute();
                $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $stmt->closeCursor();

                foreach ($rs as $row) {
                    self::$_bas2sbas[$row['base_id']] = (int) $row['sbas_id'];
                }

                $app['phraseanet.appbox']->set_data_to_cache(self::$_bas2sbas, self::CACHE_SBAS_FROM_BAS);
            }
        }

        return isset(self::$_bas2sbas[$base_id]) ? self::$_bas2sbas[$base_id] : false;
    }

    public static function baseFromColl($sbas_id, $coll_id, Application $app)
    {
        if (!self::$_coll2bas) {
            $conn = connection::getPDOConnection($app);
            $sql = 'SELECT base_id, server_coll_id, sbas_id FROM bas';
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            foreach ($rs as $row) {
                if (!isset(self::$_coll2bas[$row['sbas_id']]))
                    self::$_coll2bas[$row['sbas_id']] = array();
                self::$_coll2bas[$row['sbas_id']][$row['server_coll_id']] = (int) $row['base_id'];
            }
        }

        return isset(self::$_coll2bas[$sbas_id][$coll_id]) ? self::$_coll2bas[$sbas_id][$coll_id] : false;
    }

    public static function reset_baseDatas(appbox $appbox)
    {
        self::$_coll2bas = self::$_bas2coll = self::$_bas_names = self::$_bas2sbas = null;
        $appbox->delete_data_from_cache(
            array(
                self::CACHE_BAS_2_COLL
                , self::CACHE_BAS_2_COLL
                , self::CACHE_BAS_NAMES
                , self::CACHE_SBAS_FROM_BAS
            )
        );

        return;
    }

    public static function reset_sbasDatas(appbox $appbox)
    {
        self::$_sbas_names = self::$_sbas_params = self::$_bas2sbas = null;
        $appbox->delete_data_from_cache(
            array(
                self::CACHE_SBAS_NAMES
                , self::CACHE_SBAS_FROM_BAS
                , self::CACHE_SBAS_PARAMS
            )
        );

        return;
    }

    public static function collFromBas(Application $app, $base_id)
    {
        if (!self::$_bas2coll) {
            $conn = connection::getPDOConnection($app);
            $sql = 'SELECT base_id, server_coll_id FROM bas';
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            foreach ($rs as $row) {
                self::$_bas2coll[$row['base_id']] = (int) $row['server_coll_id'];
            }
        }

        return isset(self::$_bas2coll[$base_id]) ? self::$_bas2coll[$base_id] : false;
    }

    public static function sbas_names($sbas_id, Application $app)
    {
        if (!self::$_sbas_names) {
            try {
                self::$_sbas_names = $app['phraseanet.appbox']->get_data_from_cache(self::CACHE_SBAS_NAMES);
            } catch (Exception $e) {
                $sql = 'SELECT sbas_id, viewname, dbname FROM sbas';
                $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
                $stmt->execute();
                $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $stmt->closeCursor();

                foreach ($rs as $row) {
                    $row['viewname'] = trim($row['viewname']);
                    self::$_sbas_names[$row['sbas_id']] = $row['viewname'] ? $row['viewname'] : $row['dbname'];
                }
                $app['phraseanet.appbox']->set_data_to_cache(self::$_sbas_names, self::CACHE_SBAS_NAMES);
            }
        }

        return isset(self::$_sbas_names[$sbas_id]) ? self::$_sbas_names[$sbas_id] : 'Unknown base';
    }

    public static function bas_names($base_id, Application $app)
    {
        if (!self::$_bas_names) {
            try {
                self::$_bas_names = $app['phraseanet.appbox']->get_data_from_cache(self::CACHE_BAS_NAMES);
            } catch (Exception $e) {
                foreach ($app['phraseanet.appbox']->get_databoxes() as $databox) {
                    foreach ($databox->get_collections() as $collection) {
                        self::$_bas_names[$collection->get_base_id()] = $collection->get_name();
                    }
                }

                $app['phraseanet.appbox']->set_data_to_cache(self::$_bas_names, self::CACHE_BAS_NAMES);
            }
        }

        return isset(self::$_bas_names[$base_id]) ? self::$_bas_names[$base_id] : 'Unknown collection';
    }

    public static function scheduler_key(Application $app, $renew = false)
    {
        $conn = connection::getPDOConnection($app);

        $schedulerkey = false;

        $sql = 'SELECT schedulerkey FROM sitepreff';

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ($row) {
            $schedulerkey = trim($row['schedulerkey']);
        }

        if ($renew === true || $schedulerkey == '') {
            $schedulerkey = random::generatePassword(20);
            $sql = 'UPDATE sitepreff SET schedulerkey = :scheduler_key';
            $stmt = $conn->prepare($sql);
            $stmt->execute(array(':scheduler_key' => $schedulerkey));
            $stmt->closeCursor();
        }

        return $schedulerkey;
    }
}
