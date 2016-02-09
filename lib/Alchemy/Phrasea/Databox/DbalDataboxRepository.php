<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Databox;

use Doctrine\DBAL\Connection;

final class DbalDataboxRepository implements DataboxRepository
{
    /** @var Connection */
    private $connection;
    /** @var DataboxFactory */
    private $factory;

    public function __construct(Connection $connection, DataboxFactory $factory)
    {
        $this->connection = $connection;
        $this->factory = $factory;
    }

    /**
     * @param int $id
     * @return \databox|null
     */
    public function find($id)
    {
        $row = $this->fetchRow($id);

        if (is_array($row)) {
            return $this->factory->create($id, $row);
        }

        return null;
    }

    /**
     * @return \databox[]
     */
    public function findAll()
    {
        return $this->factory->createMany($this->fetchRows());
    }

    /**
     * @param \databox $databox
     * @return bool
     */
    public function save(\databox $databox)
    {
        return true;
    }

    public function delete(\databox $databox)
    {
        return true;
    }

    public function unmount(\databox $databox)
    {
        return true;
    }

    /**
     * @param int $id
     * @return false|array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function fetchRow($id)
    {
        $query = 'SELECT ord, viewname, label_en, label_fr, label_de, label_nl FROM sbas WHERE sbas_id = :id';
        $statement = $this->connection->prepare($query);
        $statement->execute(['id' => $id]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);
        $statement->closeCursor();

        return $row;
    }

    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function fetchRows()
    {
        $query = 'SELECT sbas_id, ord, viewname, label_en, label_fr, label_de, label_nl FROM sbas';
        $statement = $this->connection->prepare($query);
        $statement->execute();
        $rows = [];
        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $id = $row['sbas_id'];
            unset($row['sbas_id']);
            $rows[$id] = $row;
        }
        $statement->closeCursor();

        return $rows;
    }

    /**
     * @param $host
     * @param $port
     * @param $user
     * @param $password
     * @param $dbname
     *
     * @return \databox
     */
    public function mount($host, $port, $user, $password, $dbname)
    {
        $query = 'INSERT INTO sbas (ord, host, port, dbname, sqlengine, user, pwd)
              SELECT COALESCE(MAX(ord), 0) + 1 AS ord, :host AS host, :port AS port, :dbname AS dbname,
                     "MYSQL" AS sqlengine, :user AS user, :password AS pwd FROM sbas';

        $statement = $this->connection->prepare($query);
        $statement->execute([
            ':host'     => $host,
            ':port'     => $port,
            ':dbname'   => $dbname,
            ':user'     => $user,
            ':password' => $password
        ]);

        $statement->closeCursor();

        return $this->find((int) $this->connection->lastInsertId());
    }

    /**
     * @param $host
     * @param $port
     * @param $user
     * @param $password
     * @param $dbname
     *
     * @return \databox
     */
    public function create($host, $port, $user, $password, $dbname)
    {
        $params = [
            ':host' => $host,
            ':port' => $port,
            ':user' => $user,
            ':password' => $password,
            ':dbname' => $dbname
        ];

        $query = 'SELECT sbas_id FROM sbas
                  WHERE host = :host AND port = :port AND `user` = :user AND pwd = :password AND dbname = :dbname';
        $statement = $this->connection->executeQuery($query, $params);

        if ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            return $this->find((int) $row['sbas_id']);
        }

        $query = 'INSERT INTO sbas (ord, host, port, dbname, sqlengine, user, pwd)
              SELECT COALESCE(MAX(ord), 0) + 1 AS ord, :host AS host, :port AS port, :dbname AS dbname,
                     "MYSQL" AS sqlengine, :user AS user, :password AS pwd FROM sbas';

        $stmt = $this->connection->prepare($query);
        $stmt->execute($params);

        $stmt->closeCursor();

        return $this->find((int) $this->connection->lastInsertId());

    }
}
