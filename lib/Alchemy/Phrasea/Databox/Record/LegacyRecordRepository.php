<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Databox\Record;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Cache\Exception;
use Alchemy\Phrasea\Model\Entities\User;
use databox;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use record_adapter;
use set_selection;

class LegacyRecordRepository implements RecordRepository
{
    /**
     * @var Application
     */
    private $app;

    /**
     * @var databox
     */
    private $databox;

    /**
     * @var string
     */
    private $site;

    public function __construct(Application $app, databox $databox, $site)
    {
        $this->app = $app;
        $this->databox = $databox;
        $this->site = $site;
    }

    public function find($record_id, $number = null)
    {
        $record = new record_adapter($this->app, $this->databox->get_sbas_id(), $record_id, $number, false);
        try {
            $data = $record->get_data_from_cache();
        } catch (Exception $exception) {
            $data = false;
        }

        if (false === $data) {
            static $sql;

            if (!$sql) {
                $sql = $this->createSelectBuilder()->where('record_id = :record_id')->getSQL();
            }

            $data = $this->databox->get_connection()->fetchAssoc($sql, ['record_id' => $record_id]);
        }

        if (false === $data) {
            return null;
        }

        return $this->mapRecordFromResultRow($data, $record);
    }

    /**
     * @param string $sha256
     * @return record_adapter[]
     */
    public function findBySha256($sha256)
    {
        static $sql;

        if (!$sql) {
            $sql = $this->createSelectBuilder()->where('sha256 = :sha256')->getSQL();
        }

        $result = $this->databox->get_connection()->fetchAll($sql, ['sha256' => $sha256]);

        return $this->mapRecordsFromResultSet($result);
    }

    public function findBySha256WithExcludedCollIds($sha256, $excludedCollIds = [])
    {
        static $sql;

        if (!$sql) {
            $qb = $this->createSelectBuilder()
                ->where('sha256 = :sha256');

            if (!empty($excludedCollIds)) {
                $qb->andWhere($qb->expr()->notIn('coll_id', ':coll_id'));
            }

            $sql = $qb->getSQL();
        }

        $result = $this->databox->get_connection()->fetchAll($sql,
            [
                'sha256'  => $sha256,
                'coll_id' => $excludedCollIds
            ],
            [
                ':coll_id' => Connection::PARAM_INT_ARRAY
            ]
        );

        return $this->mapRecordsFromResultSet($result);
    }

    /**
     * @param string $uuid
     * @return record_adapter[]
     */
    public function findByUuid($uuid)
    {
        static $sql;

        if (!$sql) {
            $sql = $this->createSelectBuilder()->where('uuid = :uuid')->getSQL();
        }

        $result = $this->databox->get_connection()->fetchAll($sql, ['uuid' => $uuid]);

        return $this->mapRecordsFromResultSet($result);
    }

    /**
     * @param string $uuid
     * @param array $excludedCollIds
     * @return record_adapter[]
     */
    public function findByUuidWithExcludedCollIds($uuid, $excludedCollIds = [])
    {
        static $sql;

        if (!$sql) {
            $qb = $this->createSelectBuilder()
                ->where('uuid = :uuid')
            ;

            if (!empty($excludedCollIds)) {
                $qb->andWhere($qb->expr()->notIn('coll_id', ':coll_id'));
            }

            $sql = $qb->getSQL();
        }

        $result = $this->databox->get_connection()->fetchAll($sql,
            [
                'uuid'      => $uuid,
                'coll_id'   => $excludedCollIds
            ],
            [
                ':coll_id' => Connection::PARAM_INT_ARRAY
            ]
        );

        return $this->mapRecordsFromResultSet($result);
    }

    public function findByRecordIds(array $recordIds)
    {
        static $sql;

        if (empty($recordIds)) {
            return [];
        }

        if (!$sql) {
            $sql = $this->createSelectBuilder()->where('record_id IN (:recordIds)')->getSQL();
        }

        $result = $this->databox->get_connection()->fetchAll(
            $sql,
            ['recordIds' => $recordIds],
            ['recordIds' => Connection::PARAM_INT_ARRAY]
        );

        return $this->mapRecordsFromResultSet($result);
    }

    /**
     * return the number of VISIBLE children of ONE story, for a specific user
     *        if user is null -> count all children
     *
     * @param int $storyId
     * @param User|int|null $user       // can pass a User, or a user_id
     *
     * @return int                      // -1 if story not found
     */
    public function getChildrenCount($storyId, $user = null)
    {
        $r = $this->getChildrenCounts([$storyId], $user);

        return $r[$storyId];
    }


    /**
     * return the number of VISIBLE children of MANY stories, for a specific user
     *        if user is null -> count all children
     *
     * @param int[] $storyIds
     * @param User|int|null $user       // can pass a User, or a user_id
     *
     * @return int[]                    // story_id => n_children (-1 if story not found)
     */
    public function getChildrenCounts(array $storyIds, $user = null)
    {
        $connection = $this->databox->get_connection();

        $parmValues = [
            ':storyIds' => $storyIds,
        ];
        $parmTypes  = [
            ':storyIds' => Connection::PARAM_INT_ARRAY,
        ];

        // if there is a user, we must join collusr to filter results depending on coll/masks
        //
        $userFilter = "";
        if(!is_null($user)) {
            $userFilter = "         INNER JOIN collusr c ON c.site = :site AND c.usr_id = :userId AND c.coll_id=r.coll_id AND ((r.status ^ c.mask_xor) & c.mask_and) = 0\n";
            $parmValues[':site'] = $this->site;
            $parmValues[':userId'] = $user instanceof User ? $user->getId() : (int)$user;
        }

        $sql = "SELECT g.rid_parent AS story_id, COUNT(*) AS n_children\n"
            . "    FROM regroup g\n"
            . "         INNER JOIN record r ON r.record_id=g.rid_child\n"
            . $userFilter
            . "    WHERE g.rid_parent IN( :storyIds )\n"
            . "    GROUP BY g.rid_parent\n"
        ;

        $r = array_fill_keys($storyIds, 0);
        foreach($connection->fetchAll($sql, $parmValues, $parmTypes) as $row) {
            $r[$row['story_id']] = (int)$row['n_children'];
        }

        return $r;
    }


    public function findChildren(array $storyIds, $user = null, $offset = 0, $max_items = null)
    {
        if (!$storyIds) {
            return [];
        }

        $connection = $this->databox->get_connection();

        // the columns we want from the record
        //
        $selects = $this->getRecordSelects();
        array_unshift($selects, 'r.rid_parent AS story_id');    // add this to default

        // sql parameters will be completed depending of (paginated / not paginated) and/or (user / no user)
        //
        $parmValues = [
            ':storyIds' => $storyIds,
        ];
        $parmTypes  = [
            ':storyIds' => Connection::PARAM_INT_ARRAY,
        ];

        // if there is a user, we must join collusr to filter results depending on coll/masks
        //
        $userFilter = "";
        if(!is_null($user)) {
            $userFilter = "         INNER JOIN collusr c ON c.site = :site AND c.usr_id = :userId AND c.coll_id=r.coll_id AND ((r.status ^ c.mask_xor) & c.mask_and) = 0\n";
            $parmValues[':site'] = $this->site;
            $parmValues[':userId'] = $user instanceof User ? $user->getId() : (int)$user;
        }

        if ($max_items !== null) {
            //
            // we want paginated results AFTER applying all filters, we build a dynamic cptr
            // WARNING : due to bugs (?) in mysql optimizer, do NOT try to optimize this sql (e.g. removing a sub-q, or moving cpt to anothe sub-q)
            //
            $sql = "SELECT " . join(', ', $selects) . "\n"
                . "FROM (\n"
                . "  SELECT t.*,\n"
                . "         IF(@old_rid_parent != t.rid_parent, @cpt := 1, @cpt := @cpt+1) AS CPT,\n"
                . "         IF(@old_rid_parent != t.rid_parent, IF(@old_rid_parent:=t.rid_parent,'NEW PARENT',0), '----------') AS Y\n"
                . "  FROM (\n"
                . "    SELECT g.ord, g.rid_parent, r.coll_id, r.record_id, r.credate, r.status, r.uuid, r.moddate, r.parent_record_id, r.cover_record_id, r.type, r.originalname, r.sha256, r.mime\n"
                . "    FROM regroup g\n"
                . "         INNER JOIN record r ON r.record_id=g.rid_child\n"
                . $userFilter
                . "    WHERE g.rid_parent IN ( :storyIds )\n"
                . "    ORDER BY g.rid_parent, g.ord ASC\n"
                . "  ) t\n"
                . ") r\n"
                . "WHERE CPT BETWEEN :cptmin AND :cptmax"
            ;

            $parmValues[':cptmin'] = $offset + 1;
            $parmValues[':cptmax'] = $offset + $max_items;

            $connection->executeQuery('SET @cpt = 1');
            $connection->executeQuery('SET @old_rid_parent = -1');
        }
        else {
            //
            // se want all children, easy
            //
            $sql = "SELECT " . join(', ', $selects) . "\n"
                . "FROM (\n"
                . "    SELECT g.ord, g.rid_parent, r.coll_id, r.record_id, r.credate, r.status, r.uuid, r.moddate, r.parent_record_id, r.cover_record_id, r.type, r.originalname, r.sha256, r.mime\n"
                . "    FROM regroup g\n"
                . "         INNER JOIN record r ON r.record_id=g.rid_child\n"
                . $userFilter
                . "    WHERE g.rid_parent IN ( :storyIds )\n"
                . "    ORDER BY g.rid_parent, g.ord ASC\n"
                . ") r \n"
                . "ORDER BY r.rid_parent, r.ord ASC\n"
            ;
        }

        $data = $connection->fetchAll($sql, $parmValues, $parmTypes);

        $records = $this->mapRecordsFromResultSet($data);

        // todo : refacto to remove over-usage of array_map, array_combine, array_flip etc.
        //
        $selections = array_map(
            function () {
                return new set_selection($this->app);
            },
            array_flip($storyIds)
        );

        foreach ($records as $index => $child) {
            /** @var set_selection $selection */
            $selection = $selections[$data[$index]['story_id']];

            $child->setNumber($selection->get_count() + 1);

            $selection->add_element($child);
        }

        return array_map(function ($storyId) use ($selections) {
            return $selections[$storyId];
        }, $storyIds);
    }

    public function findParents(array $recordIds, $user = null)
    {
        if (!$recordIds) {
            return [];
        }

        $connection = $this->databox->get_connection();

        $selects = $this->getRecordSelects();
        array_unshift($selects, 's.rid_child as child_id');

        $builder = $connection->createQueryBuilder();
        $builder
            ->select($selects)
            ->from('regroup', 's')
            ->innerJoin('s', 'record', 'r', 'r.record_id = s.rid_parent')
            ->where(
                's.rid_child IN (:recordIds)',
                'r.parent_record_id = 1'
            )
            ->setParameter('recordIds', $recordIds, Connection::PARAM_INT_ARRAY)
        ;

        if (null !== $user) {
            $this->addUserFilter($builder, $user);
        }

        $data = $connection->fetchAll($builder->getSQL(), $builder->getParameters(), $builder->getParameterTypes());
        $stories = $this->mapRecordsFromResultSet($data);

        $selections = array_map(function () {
            return new set_selection($this->app);
        }, array_flip($recordIds));


        foreach ($stories as $index => $child) {
            /** @var set_selection $selection */
            $selection = $selections[$data[$index]['child_id']];

            $selection->add_element($child);
        }

        return array_map(function ($recordId) use ($selections) {
            return $selections[$recordId];
        }, $recordIds);
    }

    /**
     * @return QueryBuilder
     */
    private function createSelectBuilder()
    {
        return $this->databox->get_connection()->createQueryBuilder()
            ->select($this->getRecordSelects())
            ->from('record', 'r');
    }

    private function getRecordSelects()
    {
        return [
            'r.coll_id AS collection_id',
            'r.record_id',
            'r.credate AS created',
            'r.uuid',
            'r.moddate AS updated',
            'r.parent_record_id AS isStory',
            'r.cover_record_id',
            'r.type',
            'r.originalname AS originalName',
            'r.sha256',
            'r.mime',
            'LPAD(BIN(r.status), 32, \'0\') as status',
        ];
    }

    /**
     * @param array $result
     * @return record_adapter[]
     */
    private function mapRecordsFromResultSet(array $result)
    {
        $records = [];

        foreach ($result as $index => $row) {
            $records[$index] = $this->mapRecordFromResultRow($row);
        }

        return $records;
    }

    /**
     * @param array                $row
     * @param record_adapter|null $record
     * @return record_adapter
     */
    private function mapRecordFromResultRow(array $row, record_adapter $record = null)
    {
        if (null === $record) {
            $record = new record_adapter($this->app, $this->databox->get_sbas_id(), $row['record_id'], null, false);
        }

        $record->mapFromData($row);
        $record->putInCache();

        return $record;
    }

    /**
     * @param QueryBuilder $builder
     * @param int|User $user
     * @return void
     */
    private function addUserFilter(QueryBuilder $builder, $user)
    {
        $subBuilder = $builder->getConnection()->createQueryBuilder();

        $subBuilder
            ->select('1')
            ->from('collusr', 'c')
            ->where(
                'c.usr_id = ' . ($user instanceof User ? $user->getId() : (int)$user),
                'c.site = \'' . $this->site . '\'',
                '((r.status ^ c.mask_xor) & c.mask_and) = 0',
                'c.coll_id = r.coll_id'
            );

        $builder
            ->andWhere(sprintf('EXISTS(%s)', $subBuilder->getSQL()));
    }
}
