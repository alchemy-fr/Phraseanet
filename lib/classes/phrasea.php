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

class phrasea
{
    private static $_bas2sbas = false;
    private static $_sbas_names = false;
    private static $_sbas_labels = false;
    private static $_coll2bas = false;
    private static $_bas2coll = false;
    private static $_bas_labels = false;
    private static $_sbas_params = false;

    const CACHE_BAS_2_SBAS = 'bas_2_sbas';
    const CACHE_COLL_2_BAS = 'coll_2_bas';
    const CACHE_BAS_2_COLL = 'bas_2_coll';
    const CACHE_BAS_LABELS = 'bas_labels';
    const CACHE_SBAS_NAMES = 'sbas_names';
    const CACHE_SBAS_LABELS = 'sbas_labels';
    const CACHE_SBAS_FROM_BAS = 'sbas_from_bas';
    const CACHE_SBAS_PARAMS = 'sbas_params';

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

        self::$_sbas_params = [];

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
        $array = [];

        $modules = [
            1 => _('admin::monitor: module production'),
            2 => _('admin::monitor: module client'),
            3 => _('admin::monitor: module admin'),
            4 => _('admin::monitor: module report'),
            5 => _('admin::monitor: module thesaurus'),
            6 => _('admin::monitor: module comparateur'),
            7 => _('admin::monitor: module validation'),
            8 => _('admin::monitor: module upload')
        ];

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
                    self::$_coll2bas[$row['sbas_id']] = [];
                self::$_coll2bas[$row['sbas_id']][$row['server_coll_id']] = (int) $row['base_id'];
            }
        }

        return isset(self::$_coll2bas[$sbas_id][$coll_id]) ? self::$_coll2bas[$sbas_id][$coll_id] : false;
    }

    public static function reset_baseDatas(appbox $appbox)
    {
        self::$_coll2bas = self::$_bas2coll = self::$_bas_labels = self::$_bas2sbas = null;
        $appbox->delete_data_from_cache(
            [
                self::CACHE_BAS_2_COLL
                , self::CACHE_BAS_2_COLL
                , self::CACHE_BAS_LABELS
                , self::CACHE_SBAS_FROM_BAS
            ]
        );

        return;
    }

    public static function reset_sbasDatas(appbox $appbox)
    {
        self::$_sbas_names = self::$_sbas_labels = self::$_sbas_params = self::$_bas2sbas = null;
        $appbox->delete_data_from_cache(
            [
                self::CACHE_SBAS_NAMES,
                self::CACHE_SBAS_LABELS,
                self::CACHE_SBAS_FROM_BAS,
                self::CACHE_SBAS_PARAMS,
            ]
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
                foreach ($app['phraseanet.appbox']->get_databoxes() as $databox) {
                    self::$_sbas_names[$databox->get_sbas_id()] = $databox->get_viewname();
                }
                $app['phraseanet.appbox']->set_data_to_cache(self::$_sbas_names, self::CACHE_SBAS_NAMES);
            }
        }

        return isset(self::$_sbas_names[$sbas_id]) ? self::$_sbas_names[$sbas_id] : 'Unknown base';
    }

    public static function sbas_labels($sbas_id, Application $app)
    {
        if (!self::$_sbas_labels) {
            try {
                self::$_sbas_labels = $app['phraseanet.appbox']->get_data_from_cache(self::CACHE_SBAS_LABELS);
            } catch (Exception $e) {
                foreach ($app['phraseanet.appbox']->get_databoxes() as $databox) {
                    self::$_sbas_labels[$databox->get_sbas_id()] = [
                        'fr' => $databox->get_label('fr'),
                        'en' => $databox->get_label('en'),
                        'de' => $databox->get_label('de'),
                        'nl' => $databox->get_label('nl'),
                    ];
                }
                $app['phraseanet.appbox']->set_data_to_cache(self::$_sbas_labels, self::CACHE_SBAS_LABELS);
            }
        }

        if (isset(self::$_sbas_labels[$sbas_id]) && isset(self::$_sbas_labels[$sbas_id][$app['locale.I18n']])) {
            return self::$_sbas_labels[$sbas_id][$app['locale.I18n']];
        }

        return 'Unknown database';
    }

    public static function bas_labels($base_id, Application $app)
    {
        if (!self::$_bas_labels) {
            try {
                self::$_bas_labels = $app['phraseanet.appbox']->get_data_from_cache(self::CACHE_BAS_LABELS);
            } catch (Exception $e) {
                foreach ($app['phraseanet.appbox']->get_databoxes() as $databox) {
                    foreach ($databox->get_collections() as $collection) {
                        self::$_bas_labels[$collection->get_base_id()] = [
                            'fr' => $collection->get_label('fr'),
                            'en' => $collection->get_label('en'),
                            'de' => $collection->get_label('de'),
                            'nl' => $collection->get_label('nl'),
                        ];
                    }
                }

                $app['phraseanet.appbox']->set_data_to_cache(self::$_bas_labels, self::CACHE_BAS_LABELS);
            }
        }

        if (isset(self::$_bas_labels[$base_id]) && isset(self::$_bas_labels[$base_id][$app['locale.I18n']])) {
            return self::$_bas_labels[$base_id][$app['locale.I18n']];
        }

        return 'Unknown collection';
    }
}
