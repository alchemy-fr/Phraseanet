<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Upgrade;

use Alchemy\Phrasea\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Upgrade datas for version 3.1 : move metadatas from XML to relationnal tables
 */
class Step35 implements DatasUpgraderInterface
{
    const AVERAGE_PER_SECOND = 100;

    /**
     * Constructor
     *
     * @param Application $app The context application for execution
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->app['phraseanet.appbox']->get_databoxes() as $databox) {

            foreach ($databox->get_meta_structure()->get_elements() as $databox_field) {
                if ($databox_field->is_on_error()) {
                    throw new \Exception(sprintf("Databox description field %s is on error, please fix it before continue</error>", $databox_field->get_name()));
                }
            }

            $this->ensureMigrateColumn($databox);

            $sql = 'TRUNCATE metadatas';
            $stmt = $databox->get_connection()->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();

            do {
                $rs = $this->getEmptyOriginalNameRecords($databox);

                $databox->get_connection()->beginTransaction();

                foreach ($rs as $record) {
                    $this->setOriginalName($databox, $record);
                }

                $databox->get_connection()->commit();
            } while (count($rs) > 0);

            do {
                $rs = $this->getRecordsToMigrate($databox);

                $databox->get_connection()->beginTransaction();

                $sql = 'UPDATE record SET migrate35=1 WHERE record_id = :record_id';
                $stmt = $databox->get_connection()->prepare($sql);

                foreach ($rs as $row) {
                    $stmt->execute(array(':record_id' => $row['record_id']));

                    try {
                        $record = new \record_adapter($app, $databox->get_sbas_id(), $row['record_id']);
                    } catch (\Exception $e) {
                        $this->app['monolog']->addError(sprintf("Unable to load record %d on databox %d : %s", $record->get_record_id(), $record->get_sbas_id(), $record->get_sbas_id(), $e->getMessage()));
                        continue;
                    }

                    try {
                        $this->updateMetadatas($record, $row['xml']);
                    } catch (Exception $e) {
                        $this->app['monolog']->addError(sprintf("Error while upgrading metadatas for record %d on databox %d : %s", $record->get_record_id(), $record->get_sbas_id(), $e->getMessage()));
                    }

                    try {
                        $record->set_binary_status($row['status']);
                    } catch (Exception $e) {
                        $this->app['monolog']->addError(sprintf("Error while upgrading status for record %d on databox %d : %s", $record->get_record_id(), $record->get_sbas_id(), $e->getMessage()));
                    }
                    unset($record);
                }

                $stmt->closeCursor();
                $databox->get_connection()->commit();
            } while (count($rs) > 0);
        }

        foreach ($this->app['phraseanet.appbox']->get_databoxes() as $databox) {
            $this->ensureDropMigrateColumn($databox);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getTimeEstimation()
    {
        $time = 0;

        foreach ($this->app['phraseanet.appbox']->get_databoxes() as $databox) {
            $sql = 'select record_id
                            FROM record';

            $stmt = $databox->get_connection()->prepare($sql);
            $stmt->execute();
            $time += $stmt->rowCount();
            $stmt->closeCursor();
        }

        $time = $time / self::AVERAGE_PER_SECOND;

        return $time;
    }

    /**
     * Update the metadatas of a record
     *
     * @param \record_adapter $record
     * @param string          $xml
     */
    protected function updateMetadatas(\record_adapter $record, $xml)
    {
        $metas = $record->get_databox()->get_meta_structure();

        $datas = $metadatas = array();

        if (false !== $sxe = simplexml_load_string($xml)) {
            $fields = $sxe->xpath('/record/description');
            if ($fields && is_array($fields)) {
                foreach ($fields[0] as $fieldname => $value) {
                    $fieldname = trim($fieldname);
                    $value = trim($value);

                    if (null === $databox_field = $metas->get_element_by_name($fieldname)) {
                        continue;
                    }

                    if ($databox_field->is_multi()) {

                        $new_value = \caption_field::get_multi_values($value, $databox_field->get_separator());
                        if (isset($datas[$databox_field->get_id()])) {
                            $value = array_unique(array_merge($datas[$databox_field->get_id()], $new_value));
                        } else {
                            $value = $new_value;
                        }
                    } else {
                        $new_value = $value;
                        if (isset($datas[$databox_field->get_id()])) {
                            $value = $datas[$databox_field->get_id()] . ' ' . $new_value;
                        } else {
                            $value = $new_value;
                        }
                    }

                    $datas[$databox_field->get_id()] = $value;
                }
            }
        }

        foreach ($datas as $meta_struct_id => $values) {
            if (is_array($values)) {
                foreach ($values as $value) {
                    $metadatas[] = array(
                        'meta_struct_id' => $meta_struct_id
                        , 'meta_id'        => null
                        , 'value'          => $value
                    );
                }
            } else {
                $metadatas[] = array(
                    'meta_struct_id' => $meta_struct_id
                    , 'meta_id'        => null
                    , 'value'          => $values
                );
            }
        }

        $record->set_metadatas($metadatas, true);
    }

    /**
     * Update the original name of a record
     *
     * @staticvar \PDO_statement $stmt
     * @param \databox $databox
     * @param array    $record
     */
    protected function setOriginalName(\databox $databox, array $record)
    {
        static $stmt;

        if (!isset($stmt[$databox->get_sbas_id()])) {
            $sql = 'UPDATE record SET originalname = :originalname WHERE record_id = :record_id';
            $stmt[$databox->get_sbas_id()] = $databox->get_connection()->prepare($sql);
        }

        $original = '';

        if (false !== $sxe = simplexml_load_string($record['xml'])) {
            foreach ($sxe->doc->attributes() as $key => $value) {
                if (trim($key) != 'originalname') {
                    continue;
                }
                $original = basename(trim($value));
                break;
            }
        }

        $stmt[$databox->get_sbas_id()]->execute(array(':originalname' => $original, ':record_id'    => $record['record_id']));
    }

    /**
     * Returns an array of 500 records to update
     *
     * @return array
     */
    protected function getRecordsToMigrate(\databox $databox)
    {
        $sql = 'select record_id, coll_id, xml, BIN(status) as status
                        FROM record
                        WHERE migrate35=0
                        LIMIT 0, 500';

        $stmt = $databox->get_connection()->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        return $rs;
    }

    /**
     * Returns an array of 500 records without original name
     *
     * @return array
     */
    protected function getEmptyOriginalNameRecords(\databox $databox)
    {
        $sql = 'SELECT record_id, coll_id, xml, BIN(status) as status
                        FROM record
                        WHERE originalname IS NULL
                        LIMIT 0, 500';

        $stmt = $databox->get_connection()->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        return $rs;
    }

    /**
     * Removes the migration column
     *
     * @param \databox $databox
     */
    protected function ensureDropMigrateColumn(\databox $databox)
    {
        $sql = 'ALTER TABLE `record` DROP `migrate35` ';
        $stmt = $databox->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();
    }

    /**
     * Add a migration column to the table
     *
     * @param \databox $databox
     */
    protected function ensureMigrateColumn(\databox $databox)
    {
        try {
            $sql = 'ALTER TABLE `record`
                    ADD `migrate35` TINYINT( 1 ) UNSIGNED NOT NULL ,
                    ADD INDEX ( `migrate35` ) ';
            $stmt = $databox->get_connection()->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();
        } catch (\Exception $e) {

        }
    }
}
