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
use Alchemy\Phrasea\Collection\CollectionRepositoryRegistry;
use Alchemy\Phrasea\Collection\Reference\CollectionReferenceRepository;
use Symfony\Component\Translation\TranslatorInterface;

class phrasea
{
    private static $_sbas_names = false;
    private static $_sbas_labels = false;
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
        $app->getApplicationBox()->delete_data_from_cache(self::CACHE_SBAS_PARAMS);

        return true;
    }

    public static function sbas_params(Application $app)
    {
        if (self::$_sbas_params) {
            return self::$_sbas_params;
        }

        try {
            $params = $app->getApplicationBox()->get_data_from_cache(self::CACHE_SBAS_PARAMS);
            if (is_array($params)) {
                self::$_sbas_params = $params;

                return $params;
            }
        } catch (\Exception $e) {

        }

        self::$_sbas_params = [];

        $sql = 'SELECT sbas_id, host, port, user, pwd as password, dbname FROM sbas';
        $stmt = $app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row) {
            self::$_sbas_params[$row['sbas_id']] = $row;
        }

        $app->getApplicationBox()->set_data_to_cache(self::$_sbas_params, self::CACHE_SBAS_PARAMS);

        return self::$_sbas_params;
    }

    public static function modulesName(TranslatorInterface $translator, $array_modules)
    {
        $array = [];

        $modules = [
            1 => $translator->trans('admin::monitor: module production'),
            2 => $translator->trans('admin::monitor: module client'),
            3 => $translator->trans('admin::monitor: module admin'),
            4 => $translator->trans('admin::monitor: module report'),
            5 => $translator->trans('admin::monitor: module thesaurus'),
            6 => $translator->trans('admin::monitor: module comparateur'),
            7 => $translator->trans('admin::monitor: module validation'),
            8 => $translator->trans('admin::monitor: module upload')
        ];

        foreach ($array_modules as $a) {
            if (isset($modules[$a]))
                $array[] = $modules[$a];
        }

        return $array;
    }

    public static function sbasFromBas(Application $app, $base_id)
    {
        $reference = self::getCollectionReferenceRepository($app)->find($base_id);

        if ($reference) {
            return $reference->getDataboxId();
        }

        return false;
    }

    public static function baseFromColl($sbas_id, $coll_id, Application $app)
    {
        $reference = self::getCollectionReferenceRepository($app)->findByCollectionId($sbas_id, $coll_id);

        if ($reference) {
            return $reference->getBaseId();
        }

        return false;
    }

    public static function reset_baseDatas(appbox $appbox)
    {
        $appbox->delete_data_from_cache([
            self::CACHE_BAS_2_COLL,
            self::CACHE_BAS_2_COLL,
            self::CACHE_BAS_LABELS,
            self::CACHE_SBAS_FROM_BAS,
        ]);

        return;
    }

    public static function reset_sbasDatas(appbox $appbox)
    {
        self::$_sbas_names = self::$_sbas_labels = self::$_sbas_params = null;
        $appbox->delete_data_from_cache([
            self::CACHE_SBAS_NAMES,
            self::CACHE_SBAS_LABELS,
            self::CACHE_SBAS_FROM_BAS,
            self::CACHE_SBAS_PARAMS,
        ]);

        return;
    }

    public static function collFromBas(Application $app, $base_id)
    {
        $reference = self::getCollectionReferenceRepository($app)->find($base_id);

        if ($reference) {
            return $reference->getCollectionId();
        }

        return false;
    }

    public static function sbas_names($sbas_id, Application $app)
    {
        if (!self::$_sbas_names) {
            try {
                self::$_sbas_names = $app->getApplicationBox()->get_data_from_cache(self::CACHE_SBAS_NAMES);
            } catch (\Exception $e) {
                foreach ($app->getDataboxes() as $databox) {
                    self::$_sbas_names[$databox->get_sbas_id()] = $databox->get_viewname();
                }
                $app->getApplicationBox()->set_data_to_cache(self::$_sbas_names, self::CACHE_SBAS_NAMES);
            }
        }

        return isset(self::$_sbas_names[$sbas_id]) ? self::$_sbas_names[$sbas_id] : 'Unknown base';
    }

    public static function sbas_labels($sbas_id, Application $app)
    {
        if (!self::$_sbas_labels) {
            try {
                self::$_sbas_labels = $app->getApplicationBox()->get_data_from_cache(self::CACHE_SBAS_LABELS);
                if (!is_array(self::$_sbas_labels)) {
                    throw new \Exception('Invalid data retrieved from cache');
                }
            } catch (\Exception $e) {
                foreach ($app->getDataboxes() as $databox) {
                    self::$_sbas_labels[$databox->get_sbas_id()] = [
                        'fr' => $databox->get_label('fr'),
                        'en' => $databox->get_label('en'),
                        'de' => $databox->get_label('de'),
                        'nl' => $databox->get_label('nl'),
                    ];
                }
                $app->getApplicationBox()->set_data_to_cache(self::$_sbas_labels, self::CACHE_SBAS_LABELS);
            }
        }

        if (isset(self::$_sbas_labels[$sbas_id]) && isset(self::$_sbas_labels[$sbas_id][$app['locale']])) {
            return self::$_sbas_labels[$sbas_id][$app['locale']];
        }

        return 'Unknown database';
    }

    public static function bas_labels($base_id, Application $app)
    {
        $reference = self::getCollectionReferenceRepository($app)->find($base_id);

        if (! $reference) {
            return $app->trans('collection.label.unknown');
        }

        $collectionRepository = self::getCollectionRepositoryRegistry($app)
            ->getRepositoryByDatabox($reference->getDataboxId());

        $collection = $collectionRepository->find($reference->getCollectionId());

        if (! $collection) {
            throw new \RuntimeException('Missing collection ' . $base_id . '.');
        }

        $labels = $collection->getCollection()->getLabels();

        if (isset($labels[$app['locale']])) {
            return $labels[$app['locale']];
        }

        return $collection->getCollection()->getName();
    }

    /**
     * @param Application $app
     * @return CollectionReferenceRepository
     */
    private static function getCollectionReferenceRepository(Application $app)
    {
        return $app['repo.collection-references'];
    }

    /**
     * @param Application $app
     * @return CollectionRepositoryRegistry
     */
    private static function getCollectionRepositoryRegistry(Application $app)
    {
        return $app['repo.collections-registry'];
    }
}
