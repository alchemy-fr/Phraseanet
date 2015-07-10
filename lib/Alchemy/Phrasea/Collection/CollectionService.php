<?php

namespace Alchemy\Phrasea\Collection;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Collection\Reference\CollectionReference;
use Alchemy\Phrasea\Databox\DataboxConnectionProvider;
use Alchemy\Phrasea\Model\Entities\User;
use Doctrine\DBAL\Connection;

class CollectionService
{
    /**
     * @var Application
     */
    private $app;

    private $connection;

    private $connectionProvider;

    public function __construct(Application $application, Connection $connection, DataboxConnectionProvider $connectionProvider)
    {
        $this->app = $application;
        $this->connection = $connection;
        $this->connectionProvider = $connectionProvider;
    }

    /**
     * @param Collection $collection
     * @return int|null
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getRecordCount(Collection $collection)
    {
        $connection = $this->connectionProvider->getConnection($collection->getDataboxId());

        $sql = "SELECT COALESCE(COUNT(record_id), 0) AS recordCount FROM record WHERE coll_id = :coll_id";
        $stmt = $connection->prepare($sql);
        $stmt->execute([':coll_id' => $collection->getCollectionId()]);
        $rowbas = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $amount = $rowbas ? (int) $rowbas["recordCount"] : null;

        return $amount;
    }

    /**
     * @param Collection $collection
     * @return array
     */
    public function getRecordDetails(Collection $collection)
    {
        $sql = "SELECT record.coll_id,name,COALESCE(asciiname, CONCAT('_',record.coll_id)) AS asciiname,
                    SUM(1) AS n, SUM(size) AS size
                  FROM record NATURAL JOIN subdef
                    INNER JOIN coll ON record.coll_id=coll.coll_id AND coll.coll_id = :coll_id
                  GROUP BY record.coll_id, subdef.name";

        $connection = $this->connectionProvider->getConnection($collection->getDataboxId());

        $stmt = $connection->prepare($sql);
        $stmt->execute([':coll_id' => $collection->getCollectionId()]);
        $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $ret = [];
        foreach ($rs as $row) {
            $ret[] = [
                "coll_id" => (int) $row["coll_id"],
                "name"    => $row["name"],
                "amount"  => (int) $row["n"],
                "size"    => (int) $row["size"]];
        }

        return $ret;
    }

    /**
     * @param Collection $collection
     * @return $this
     * @throws \Doctrine\DBAL\DBALException
     */
    public function resetWatermark(Collection $collection)
    {
        $sql = 'SELECT path, file FROM record r INNER JOIN subdef s USING(record_id)
            WHERE r.coll_id = :coll_id AND r.type="image" AND s.name="preview"';

        $connection = $this->connectionProvider->getConnection($collection->getDataboxId());

        $stmt = $connection->prepare($sql);
        $stmt->execute([':coll_id' => $collection->getCollectionId()]);

        while ($row2 = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            @unlink(\p4string::addEndSlash($row2['path']) . 'watermark_' . $row2['file']);
        }
        $stmt->closeCursor();

        return $this;
    }

    /**
     * @param Collection $collection
     * @param int|null $record_id
     * @return $this
     * @throws \Doctrine\DBAL\DBALException
     */
    public function resetStamp(Collection $collection, $record_id = null)
    {
        $sql = 'SELECT path, file FROM record r INNER JOIN subdef s USING(record_id)
            WHERE r.coll_id = :coll_id
              AND r.type="image" AND s.name IN ("preview", "document")';


        $params = [':coll_id' => $collection->getCollectionId()];

        if ($record_id) {
            $sql .= ' AND record_id = :record_id';
            $params[':record_id'] = $record_id;
        }

        $connection = $this->connectionProvider->getConnection($collection->getDataboxId());

        $stmt = $connection->prepare($sql);
        $stmt->execute($params);

        while ($row2 = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            @unlink(\p4string::addEndSlash($row2['path']) . 'stamp_' . $row2['file']);
        }
        $stmt->closeCursor();

        return $this;
    }

    /**
     * @param \databox $databox
     * @param Collection $collection
     * @param CollectionReference $reference
     * @throws \Doctrine\DBAL\DBALException
     */
    public function delete(\databox $databox, Collection $collection, CollectionReference $reference)
    {
        while ($this->getRecordCount($collection) > 0) {
            $this->emptyCollection($databox, $collection);
        }

        $connection = $this->connectionProvider->getConnection($collection->getDataboxId());

        $sql = "DELETE FROM coll WHERE coll_id = :coll_id";
        $stmt = $connection->prepare($sql);
        $stmt->execute([':coll_id' => $collection->getCollectionId()]);
        $stmt->closeCursor();

        $sql = "DELETE FROM bas WHERE base_id = :base_id";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([':base_id' => $reference->getBaseId()]);
        $stmt->closeCursor();

        $sql = "DELETE FROM basusr WHERE base_id = :base_id";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([':base_id' => $reference->getBaseId()]);
        $stmt->closeCursor();

        $this->app['manipulator.registration']->deleteRegistrationsOnCollection($this);

        return;
    }

    /**
     * @param \databox $databox
     * @param Collection $collection
     * @param int $pass_quantity
     * @return $this
     * @throws \Doctrine\DBAL\DBALException
     */
    public function emptyCollection(\databox $databox, Collection $collection, $pass_quantity = 100)
    {
        $pass_quantity = (int) $pass_quantity > 200 ? 200 : (int) $pass_quantity;
        $pass_quantity = (int) $pass_quantity < 10 ? 10 : (int) $pass_quantity;

        $sql = "SELECT record_id FROM record WHERE coll_id = :coll_id
            ORDER BY record_id DESC LIMIT 0, " . $pass_quantity;

        $connection = $this->connectionProvider->getConnection($collection->getDataboxId());

        $stmt = $connection->prepare($sql);
        $stmt->execute([':coll_id' => $collection->getCollectionId()]);
        $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row) {
            $record = $databox->get_record($row['record_id']);
            $record->delete();
            unset($record);
        }

        return $this;
    }

    /**
     * @param CollectionReference $reference
     * @return $this
     * @throws \Doctrine\DBAL\DBALException
     */
    public function unmountCollection(CollectionReference $reference)
    {
        $params = [':base_id' => $reference->getBaseId()];

        $query = $this->app['phraseanet.user-query'];
        $total = $query->on_base_ids([$reference->getBaseId()])
            ->include_phantoms(false)
            ->include_special_users(true)
            ->include_invite(true)
            ->include_templates(true)->get_total();
        $n = 0;

        while ($n < $total) {
            $results = $query->limit($n, 50)->execute()->get_results();

            foreach ($results as $user) {
                $this->app->getAclForUser($user)->delete_data_from_cache(\ACL::CACHE_RIGHTS_SBAS);
                $this->app->getAclForUser($user)->delete_data_from_cache(\ACL::CACHE_RIGHTS_BAS);
            }

            $n+=50;
        }

        $sql = "DELETE FROM basusr WHERE base_id = :base_id";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $sql = "DELETE FROM bas WHERE base_id = :base_id";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $this->app['manipulator.registration']->deleteRegistrationsOnCollection($this);
    }

    /**
     * @param CollectionReference $reference
     * @param User $user
     */
    public function grantAdminRights(CollectionReference $reference, User $user)
    {
        $rights = [
            "canputinalbum"   => "1",
            "candwnldhd"      => "1",
            "nowatermark"     => "1",
            "candwnldpreview" => "1",
            "cancmd"          => "1",
            "canadmin"        => "1",
            "actif"           => "1",
            "canreport"       => "1",
            "canpush"         => "1",
            "basusr_infousr"  => "",
            "canaddrecord"    => "1",
            "canmodifrecord"  => "1",
            "candeleterecord" => "1",
            "chgstatus"       => "1",
            "imgtools"        => "1",
            "manage"          => "1",
            "modify_struct"   => "1"
        ];

        $this->app->getAclForUser($user)->update_rights_to_base($reference->getBaseId(), $rights);
    }
}
