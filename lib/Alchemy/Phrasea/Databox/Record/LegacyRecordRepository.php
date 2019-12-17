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
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

class LegacyRecordRepository implements RecordRepository
{
    /**
     * @var Application
     */
    private $app;

    /**
     * @var \databox
     */
    private $databox;

    /**
     * @var string
     */
    private $site;

    public function __construct(Application $app, \databox $databox, $site)
    {
        $this->app = $app;
        $this->databox = $databox;
        $this->site = $site;
    }

    public function find($record_id, $number = null)
    {
        $record = new \record_adapter($this->app, $this->databox->get_sbas_id(), $record_id, $number, false);
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
     * @return \record_adapter[]
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
     * @return \record_adapter[]
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
     * @return \record_adapter[]
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

    public function findChildren(array $storyIds, $user = null, $offset = 1, $max_items = null)
    {
        if (!$storyIds) {
            return [];
        }

        $connection = $this->databox->get_connection();

        $selects = $this->getRecordSelects();

        if ($max_items) {
            array_unshift($selects, 'sr.rid_parent as story_id');

            $subBuilder = $connection->createQueryBuilder();

            $subBuilder
                ->select('s.*,
                    IF(@old_rid_parent != s.rid_parent, @cpt := 1, @cpt := @cpt+1) AS CPT')
                ->addSelect("IF(@old_rid_parent != s.rid_parent, IF(@old_rid_parent:=s.rid_parent,'NEW PARENT',0), '----------') AS Y")
                ->from('regroup', 's')
                ->where('s.rid_parent IN (:storyIds)')
                ->setParameter('storyIds', $storyIds, Connection::PARAM_INT_ARRAY)
                ->orderBy('s.rid_parent, s.ord')
            ;

            $builder = $subBuilder->getConnection()->createQueryBuilder();

            $builder->select($selects)
                ->from(sprintf('( %s )', $subBuilder->getSQL()), 'sr')
                ->innerJoin('sr', 'record', 'r', 'r.record_id = sr.rid_child')
                ->where('sr.CPT BETWEEN :offset AND :maxresult')
                ->andWhere('r.parent_record_id = 0')
                ->setParameter('offset', $offset)
                ->setParameter('maxresult', ($offset + $max_items -1))
                ->orderBy('story_id, sr.CPT')
            ;

            if (null !== $user) {
                $this->addUserFilter($builder, $user);
            }

            $connection->executeQuery('SET @cpt = 1');

            $connection->executeQuery('SET @old_rid_parent = -1');


            $data = $connection->fetchAll(
                $builder->getSQL(),
                array_merge($subBuilder->getParameters(), $builder->getParameters()),
                array_merge($subBuilder->getParameterTypes(), $builder->getParameterTypes())
            );

        } else {
            array_unshift($selects, 's.rid_parent as story_id');

            $builder = $connection->createQueryBuilder();

            $builder
                ->select($selects)
                ->from('regroup', 's')
                ->innerJoin('s', 'record', 'r', 'r.record_id = s.rid_child')
                ->where(
                    's.rid_parent IN (:storyIds)',
                    'r.parent_record_id = 0'
                )
                ->orderBy('s.ord', 'ASC')
                ->setParameter('storyIds', $storyIds, Connection::PARAM_INT_ARRAY)
            ;

            if (null !== $user) {
                $this->addUserFilter($builder, $user);
            }

            $data = $connection->fetchAll($builder->getSQL(), $builder->getParameters(), $builder->getParameterTypes());
        }

        $records = $this->mapRecordsFromResultSet($data);

        $selections = array_map(
            function () {
                return new \set_selection($this->app);
            },
            array_flip($storyIds)
        );

        foreach ($records as $index => $child) {
            /** @var \set_selection $selection */
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
            return new \set_selection($this->app);
        }, array_flip($recordIds));


        foreach ($stories as $index => $child) {
            /** @var \set_selection $selection */
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
            'r.type',
            'r.originalname AS originalName',
            'r.sha256',
            'r.mime',
            'LPAD(BIN(r.status), 32, \'0\') as status',
        ];
    }

    /**
     * @param array $result
     * @return \record_adapter[]
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
     * @param \record_adapter|null $record
     * @return \record_adapter
     */
    private function mapRecordFromResultRow(array $row, \record_adapter $record = null)
    {
        if (null === $record) {
            $record = new \record_adapter($this->app, $this->databox->get_sbas_id(), $row['record_id'], null, false);
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
                'c.usr_id = :userId',
                'c.site = :site',
                '((r.status ^ c.mask_xor) & c.mask_and) = 0',
                'c.coll_id = r.coll_id'
            );

        $builder
            ->andWhere(sprintf('EXISTS(%s)', $subBuilder->getSQL()))
            ->setParameter('userId', $user instanceof User ? $user->getId() : (int)$user)
            ->setParameter('site', $this->site);
    }
}
