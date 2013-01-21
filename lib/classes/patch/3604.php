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

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class patch_3604 implements patchInterface
{
    /**
     *
     * @var string
     */
    private $release = '3.6.0a2';

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
        return true;
    }

    /**
     *
     * @return Array
     */
    public function concern()
    {
        return $this->concern;
    }

    public function apply(base $databox, Application $app)
    {
        /**
         * Fail if upgrade has previously failed, no problem
         */
        try {
            $sql = "ALTER TABLE `metadatas`
                            ADD `updated` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '1',
                            ADD INDEX ( `updated` )";

            $stmt = $databox->get_connection()->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();

            $sql = 'UPDATE metadatas SET updated = "0"
                            WHERE meta_struct_id
                                IN (SELECT id FROM metadatas_structure WHERE multi = "1")';
            $stmt = $databox->get_connection()->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();
        } catch (\Exception $e) {

        }

        try {
            $sql = 'ALTER TABLE `metadatas` DROP INDEX `unique`';

            $stmt = $databox->get_connection()->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();
        } catch (\PDOException $e) {

        }

        $sql = 'SELECT m . *
            FROM metadatas_structure s, metadatas m
            WHERE m.meta_struct_id = s.id
            AND s.multi = "1" AND updated="0"';

        $stmt = $databox->get_connection()->prepare($sql);
        $stmt->execute();
        $rowCount = $stmt->rowCount();
        $stmt->closeCursor();

        $n = 0;
        $perPage = 1000;

        while ($n < $rowCount) {
            $sql = 'SELECT m . *
            FROM metadatas_structure s, metadatas m
            WHERE m.meta_struct_id = s.id
            AND s.multi = "1" LIMIT ' . $n . ', ' . $perPage;

            $stmt = $databox->get_connection()->prepare($sql);
            $stmt->execute();
            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            $databox->get_connection()->beginTransaction();

            $sql = 'INSERT INTO metadatas(id, record_id, meta_struct_id, value)
                                VALUES (null, :record_id, :meta_struct_id, :value)';
            $stmt = $databox->get_connection()->prepare($sql);

            $databox_fields = array();

            foreach ($rs as $row) {
                $meta_struct_id = $row['meta_struct_id'];

                if ( ! isset($databox_fields[$meta_struct_id])) {
                    $databox_fields[$meta_struct_id] = \databox_field::get_instance($app, $databox, $meta_struct_id);
                }

                $values = \caption_field::get_multi_values($row['value'], $databox_fields[$meta_struct_id]->get_separator());

                foreach ($values as $value) {
                    $params = array(
                        ':record_id'      => $row['record_id'],
                        ':meta_struct_id' => $row['meta_struct_id'],
                        ':value'          => $value,
                    );
                    $stmt->execute($params);
                }
            }

            $stmt->closeCursor();

            $sql = 'DELETE FROM metadatas WHERE id = :id';
            $stmt = $databox->get_connection()->prepare($sql);

            foreach ($rs as $row) {
                $params = array(':id' => $row['id']);
                $stmt->execute($params);
            }

            $stmt->closeCursor();

            $databox->get_connection()->commit();

            $n+= $perPage;
        }

        /**
         * Remove the extra column
         */
        try {
            $sql = "ALTER TABLE `metadatas` DROP `updated`";
            $stmt = $databox->get_connection()->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();
        } catch (\Exception $e) {

        }

        return true;
    }
}
