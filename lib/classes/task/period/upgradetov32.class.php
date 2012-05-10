<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class task_period_upgradetov32 extends task_abstract
{
    protected $sbas_id;

    // ==========================================================================
    // ===== les interfaces de settings (task2.php) pour ce type de tache
    // ==========================================================================
    // ====================================================================
    // getName() : must return the name of this kind of task (utf8), MANDATORY
    // ====================================================================
    public function getName()
    {
        return(_("upgrade to v3.2"));
    }

    public static function interfaceAvailable()
    {
        return false;
    }

    // ==========================================================================
    // help() : text displayed if --help
    // ==========================================================================
    public function help()
    {
        return(utf8_encode("Upgrade some database values"));
    }

    // ==========================================================================
    // run() : the real code executed by each task, MANDATORY
    // ==========================================================================

    protected function loadSettings(SimpleXMLElement $sx_task_settings)
    {
        $this->sbas_id = (int) $sx_task_settings->sbas_id;
        parent::loadSettings($sx_task_settings);
    }

    protected function run2()
    {
        printf("taskid %s starting." . PHP_EOL, $this->getID());

        $registry = registry::get_instance();
//    $registry->set('GV_cache_server_type', 'nocache', \registry::TYPE_STRING);
        $registry->set('GV_sphinx', false, \registry::TYPE_BOOLEAN);

        if ( ! $this->sbas_id) {
            printf("sbas_id '" . $this->sbas_id . "' invalide\n");
            $this->return_value = self::RETURNSTATUS_STOPPED;

            return;
        }

        try {
            $databox = databox::get_instance($this->sbas_id);
            $connbas = connection::getPDOConnection($this->sbas_id);
        } catch (Exception $e) {
            $this->return_value = self::RETURNSTATUS_STOPPED;

            return;
        }

        try {
            foreach ($databox->get_meta_structure()->get_elements() as $struct_el) {
                if ($struct_el instanceof databox_fieldUnknown) {
                    throw new Exception('Bad field');
                }
            }
        } catch (Exception $e) {
            printf("Please verify all your databox meta fields before migrating, It seems somes are wrong\n");
            $this->return_value = self::RETURNSTATUS_STOPPED;

            return 'stopped';
        }

        $this->running = true;

        try {
            $sql = 'ALTER TABLE `record` ADD `migrated` TINYINT( 1 ) UNSIGNED NOT NULL , ADD INDEX ( `migrated` ) ';
            $stmt = $connbas->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();
        } catch (Exception $e) {

        }

        $n_done = 0;

        while ($this->running) {
            try {

                $sql = 'SELECT COUNT(record_id) as total FROM record';
                $stmt = $connbas->prepare($sql);
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $stmt->closeCursor();

                $total = 0;
                if ($row)
                    $total = $row['total'];

                $sql = 'SELECT COUNT(record_id) as total FROM record WHERE migrated = 1';
                $stmt = $connbas->prepare($sql);
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $stmt->closeCursor();

                $done = 0;
                if ($row)
                    $done = $row['total'];

                $this->setProgress($done, $total);

                $this->running = false;
                $sql = 'select record_id, coll_id, xml, BIN(status) as status
          FROM record
          WHERE originalname IS NULL
          LIMIT 0, 500';

                $stmt = $connbas->prepare($sql);
                $stmt->execute();
                $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $stmt->closeCursor();

                if (count($rs) > 0) {
                    $this->running = true;
                }

                $sql = 'UPDATE record SET originalname = :originalname WHERE record_id = :record_id';
                $stmt_original = $connbas->prepare($sql);
                $connbas->beginTransaction();
                foreach ($rs as $row) {
                    $original = '';
                    $sxe = simplexml_load_string($row['xml']);
                    if ($sxe) {
                        foreach ($sxe->doc->attributes() as $key => $value) {
                            $key = trim($key);
                            $value = trim($value);
                            if ($key != 'originalname') {
                                continue;
                            }
                            $original = basename($value);
                            break;
                        }
                    }
                    try {
                        $stmt_original->execute(array(':originalname' => $value, ':record_id' => $row['record_id']));
                    } catch (Exception $e) {

                    }
                }
                $connbas->commit();




                $sql = 'select record_id, coll_id, xml, BIN(status) as status
          FROM record
          WHERE migrated="0" AND record_id NOT IN (select distinct record_id from technical_datas)
          LIMIT 0, 500';

                $stmt = $connbas->prepare($sql);
                $stmt->execute();
                $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $stmt->closeCursor();

                if (count($rs) > 0) {
                    $this->running = true;
                }

                $sql = 'REPLACE INTO technical_datas (id, record_id, name, value)
            VALUES (null, :record_id, :name, :value)';
                $stmt = $connbas->prepare($sql);
                $connbas->beginTransaction();

                foreach ($rs as $row) {
                    try {
                        $record = new record_adapter($this->sbas_id, $row['record_id']);
                        $document = $record->get_subdef('document');

                        foreach ($document->readTechnicalDatas() as $name => $value) {
                            if (is_null($value))
                                continue;

                            $stmt->execute(array(
                                ':record_id' => $record->get_record_id()
                                , ':name' => $name
                                , ':value' => $value
                            ));
                        }
                    } catch (Exception $e) {

                    }
                }

                $connbas->commit();
                $stmt->closeCursor();

                $sql = 'select record_id, coll_id, xml, BIN(status) as status
          FROM record
          WHERE migrated=0
          LIMIT 0, 500';

                $stmt = $connbas->prepare($sql);
                $stmt->execute();
                $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $stmt->closeCursor();


                if (count($rs) > 0) {
                    $this->running = true;
                }
                $connbas->beginTransaction();

                $sql = 'UPDATE record SET migrated=1 WHERE record_id = :record_id';
                $stmt = $connbas->prepare($sql);

                foreach ($rs as $row) {
                    try {
                        $record = new record_adapter($this->sbas_id, $row['record_id']);

                        $metas = $databox->get_meta_structure();

                        $metadatas = array();

                        if ($sxe = simplexml_load_string($row['xml'])) {
                            $z = $sxe->xpath('/record/description');
                            if ($z && is_array($z)) {
                                foreach ($z[0] as $ki => $vi) {
                                    $databox_field = $metas->get_element_by_name((string) $ki);
                                    if ( ! $databox_field) {
                                        continue;
                                    }

                                    $value = (string) $vi;

                                    if (trim($value) === '')
                                        continue;

                                    if ($databox_field->is_multi()) {
                                        $new_value = caption_field::get_multi_values($value, $databox_field->get_separator());
                                        if (isset($metadatas[$databox_field->get_id()])) {
                                            $value = array_unique(array_merge($metadatas[$databox_field->get_id()]['value'], $new_value));
                                        } else {
                                            $value = $new_value;
                                        }
                                    } else {
                                        $new_value = array($value);
                                        if (isset($metadatas[$databox_field->get_id()])) {
                                            $value = array(array_shift($metadatas[$databox_field->get_id()]['value']) . ' ' . array_shift($new_value));
                                        } else {
                                            $value = $new_value;
                                        }
                                    }

                                    $metadatas[$databox_field->get_id()] = array(
                                        'meta_struct_id' => $databox_field->get_id()
                                        , 'meta_id' => null
                                        , 'value' => $value
                                    );
                                }
                            }
                        }
                        $record->set_metadatas($metadatas, true);
                        unset($record);
                    } catch (Exception $e) {

                    }
                    try {
                        $record = new record_adapter($this->sbas_id, $row['record_id']);
                        $record->set_binary_status($row['status']);
                        unset($record);
                    } catch (Exception $e) {

                    }
                    $stmt->execute(array(':record_id' => $row['record_id']));
                }

                $stmt->closeCursor();
                unset($stmt);
                $connbas->commit();

                $n_done += 500;

                $memory = memory_get_usage() >> 20;

                if ($n_done >= 5000) {
                    $this->return_value = task_abstract::RETURNSTATUS_TORESTART;

                    return;
                }
                if ($memory > 100) {
                    $this->return_value = task_abstract::RETURNSTATUS_TORESTART;

                    return;
                }
            } catch (Exception $e) {

            }
            usleep(500000);
        }

        $sql = 'ALTER TABLE `record`  DROP `migrated`';
        $stmt = $connbas->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();


        $sql = "DELETE from technical_datas WHERE name='DONE'";
        $stmt = $connbas->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $conn = connection::getPDOConnection();

        printf("taskid %s ending." . PHP_EOL, $this->getID());
        sleep(1);
        printf("good bye world I was task upgrade to version 3.2" . PHP_EOL);

        $sql = 'UPDATE task2 SET status="tostop" WHERE  task_id = :task_id';

        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':task_id' => $this->getID()));
        $stmt->closeCursor();

        $this->setProgress(0, 0);

        $this->return_value = self::RETURNSTATUS_TODELETE;

        flush();

        return;
    }
}

