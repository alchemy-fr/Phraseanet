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

class cache_databox
{
    protected static $refreshing = false;

    /**
     * @param Application $app
     * @param int         $sbas_id
     *
     * @return cache_databox
     */
    public static function refresh(Application $app, $sbas_id)
    {
        if (self::$refreshing) {
            return;
        }

        self::$refreshing = true;

        $databox = $app->findDataboxById((int) $sbas_id);

        $date = new \DateTime('-3 seconds');

        $last_update = null;

        try {
            $last_update = $app->getApplicationBox()->get_data_from_cache('memcached_update_' . $sbas_id);
        } catch (\Exception $e) {

        }

        if ($last_update)
            $last_update = new \DateTime($last_update);
        else
            $last_update = new \DateTime('-10 years');

        if ($date <= $last_update) {
            self::$refreshing = false;

            return;
        }

        $connsbas = $databox->get_connection();

        $sql = 'SELECT type, value FROM memcached WHERE site_id = :site_id';
        $stmt = $connsbas->prepare($sql);
        $stmt->execute([':site_id' => $app['conf']->get('servername')]);
        $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row) {
            switch ($row['type']) {
                case 'record':
                    $key = 'record_' . $sbas_id . '_' . $row['value'];
                    $databox->delete_data_from_cache($key);
                    $key = 'record_' . $sbas_id . '_' . $row['value'] . '_' . \record_adapter::CACHE_SUBDEFS;
                    $databox->delete_data_from_cache($key);
                    $key = 'record_' . $sbas_id . '_' . $row['value'] . '_' . \record_adapter::CACHE_GROUPING;
                    $databox->delete_data_from_cache($key);
                    $key = 'record_' . $sbas_id . '_' . $row['value'] . '_' . \record_adapter::CACHE_MIME;
                    $databox->delete_data_from_cache($key);
                    $key = 'record_' . $sbas_id . '_' . $row['value'] . '_' . \record_adapter::CACHE_ORIGINAL_NAME;
                    $databox->delete_data_from_cache($key);
                    $key = 'record_' . $sbas_id . '_' . $row['value'] . '_' . \record_adapter::CACHE_SHA256;
                    $databox->delete_data_from_cache($key);
                    $key = 'record_' . $sbas_id . '_' . $row['value'] . '_' . \record_adapter::CACHE_TECHNICAL_DATA;
                    $databox->delete_data_from_cache($key);

                    $sql = 'DELETE FROM memcached WHERE site_id = :site_id AND type="record" AND value = :value';

                    $params = [
                        ':site_id' => $app['conf']->get('servername')
                        , ':value'   => $row['value']
                    ];

                    $stmt = $connsbas->prepare($sql);
                    $stmt->execute($params);
                    $stmt->closeCursor();

                    $record = new \record_adapter($app, $sbas_id, $row['value']);
                    $record->get_caption()->delete_data_from_cache();

                    foreach ($record->get_subdefs() as $subdef) {
                        $subdef->delete_data_from_cache();
                    }

                    break;
                case 'structure':
                    $app->getApplicationBox()->delete_data_from_cache(\appbox::CACHE_LIST_BASES);

                    $sql = 'DELETE FROM memcached WHERE site_id = :site_id AND type="structure" AND value = :value';

                    $params = [
                        ':site_id' => $app['conf']->get('servername')
                        , ':value'   => $row['value']
                    ];

                    $stmt = $connsbas->prepare($sql);
                    $stmt->execute($params);
                    $stmt->closeCursor();
                    break;
            }
        }

        $date = new \DateTime();
        $now = $date->format(DATE_ISO8601);

        $app->getApplicationBox()->set_data_to_cache($now, 'memcached_update_' . $sbas_id);

        $conn = $app->getApplicationBox()->get_connection();

        $sql = 'UPDATE sitepreff SET memcached_update = current_timestamp()';
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        self::$refreshing = false;

        return;
    }

    /**
     * @param Application   $app
     * @param int           $sbas_id
     * @param string        $type
     * @param mixed content $value
     */
    public static function update(Application $app, $sbas_id, $type, $value = '')
    {
        $databox = $app->findDataboxById($sbas_id);
        $connbas = $databox->get_connection();

        $sql = 'SELECT distinct site_id as site_id
            FROM clients
            WHERE site_id != :site_id';

        $stmt = $connbas->prepare($sql);
        $stmt->execute([':site_id' => $app['conf']->get('servername')]);
        $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $sql = 'REPLACE INTO memcached (site_id, type, value)
            VALUES (:site_id, :type, :value)';

        $stmt = $connbas->prepare($sql);

        foreach ($rs as $row) {
            $stmt->execute([':site_id' => $row['site_id'], ':type'    => $type, ':value'   => $value]);
        }

        $stmt->closeCursor();

        return;
    }

    public static function insertClient(Application $app, \databox $databox)
    {
        $connbas = $databox->get_connection();

        $sql = 'SELECT site_id FROM clients WHERE site_id = :site_id';
        $stmt = $connbas->prepare($sql);
        $stmt->execute([':site_id' => $app['conf']->get('servername')]);
        $rowCount = $stmt->rowCount();
        $stmt->closeCursor();

        if ($rowCount > 0) {
            return;
        }

        $sql = 'INSERT INTO clients (site_id) VALUES (:site_id)';
        $stmt = $connbas->prepare($sql);
        $stmt->execute([':site_id' => $app['conf']->get('servername')]);
        $stmt->closeCursor();

        return;
    }
}
