<?php

namespace Alchemy\Phrasea\Command\Upgrade;

use Alchemy\Phrasea\Border\File;
use Alchemy\Phrasea\Core;
use Monolog\Logger;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Upgrade datas for version 3.1 : read UUIDs
 */
class Step31 implements DatasUpgraderInterface
{
    const AVERAGE_PER_SECOND = 1.4;

    protected $core;

    /**
     * @var Monolog\Logger
     */
    protected $logger;

    /**
     * Constructor
     *
     * @param Core $core
     * @param Logger $logger
     */
    public function __construct(Core $core, Logger $logger)
    {
        $this->core = $core;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $appbox = \appbox::get_instance($this->core);

        foreach ($appbox->get_databoxes() as $databox) {
            do {
                $records = $this->getNullUUIDs($databox);

                foreach ($records as $record) {

                    $this->updateRecordUUID($databox, $record);
                }
            } while (count($records) > 0);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getTimeEstimation()
    {
        $appbox = \appbox::get_instance($this->core);

        $time = 0;

        foreach ($appbox->get_databoxes() as $databox) {
            $time += $this->getDataboxTimeEstimation($databox);
        }

        $time = $time / self::AVERAGE_PER_SECOND;

        return $time;
    }

    /**
     * Return the number of record which does not have a UUID
     *
     * @param \databox $databox
     */
    protected function getDataboxTimeEstimation(\databox $databox)
    {
        $sql = 'SELECT r.coll_id, r.type, r.record_id, s.path, s.file, r.xml
                FROM record r, subdef s
                        WHERE ISNULL(uuid)
                        AND s.record_id = r.record_id AND s.name="document"
                        AND parent_record_id = 0';

        $stmt = $databox->get_connection()->prepare($sql);
        $stmt->execute();
        $quantity = $stmt->rowCount();
        $stmt->closeCursor();

        return $quantity;
    }

    /**
     * Return a maximum of 100 recods without UUIDs
     *
     * @param \databox $databox
     * @return array
     */
    protected function getNullUUIDs(\databox $databox)
    {
        $sql = 'SELECT r.coll_id, r.type, r.record_id, s.path, s.file, r.xml
                FROM record r, subdef s
                        WHERE ISNULL(uuid)
                        AND s.record_id = r.record_id AND s.name="document"
                        AND parent_record_id = 0 LIMIT 100';

        $stmt = $databox->get_connection()->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        return $rs;
    }

    /**
     * Update a record with a UUID
     *
     * @param \databox $databox
     * @param array $record
     */
    protected function updateRecordUUID(\databox $databox, array $record)
    {
        $pathfile = \p4string::addEndSlash($record['path']) . $record['file'];

        $uuid = \uuid::generate_v4();
        try {
            $media = $this->core['mediavorus']->guess(new \SplFileInfo($pathfile));
            $collection = \collection::get_from_coll_id($databox, (int) $record['coll_id']);

            $file = new File($media, $collection);
            $uuid = $file->getUUID(true, true);
            $sha256 = $file->getSha256();

            $this->logger->addInfo(sprintf("Upgrading record %d with uuid %s", $record['record_id'], $uuid));
        } catch (\Exception $e) {
            $this->logger->addError(sprintf("Uuid upgrade for record %s failed", $record['record_id']));
        }

        $sql = 'UPDATE record SET uuid = :uuid, sha256 = :sha256 WHERE record_id = :record_id';

        $params = array(
            ':uuid'      => $uuid,
            'sha256'     => $sha256,
            ':record_id' => $record['record_id'],
        );
        $stmt = $databox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();
    }
}
