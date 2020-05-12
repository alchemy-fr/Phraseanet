<?php

namespace Alchemy\Phrasea\Model\Repositories;

use Alchemy\Phrasea\Core\PhraseaTokens;
use Doctrine\ORM\EntityRepository;

class WorkerRunningJobRepository extends EntityRepository
{
    /**
     *  return true if we can create subdef
     * @param $subdefName
     * @param $recordId
     * @param $databoxId
     * @return bool
     */
    public function canCreateSubdef($subdefName, $recordId, $databoxId)
    {
        $rsm = $this->createResultSetMappingBuilder('w');
        $rsm->addScalarResult('work_on','work_on');

        $sql = 'SELECT work_on
            FROM WorkerRunningJob
            WHERE ((work & :write_meta) > 0 OR ((work & :make_subdef) > 0 AND work_on = :work_on) ) 
            AND record_id = :record_id 
            AND databox_id = :databox_id';

        $query = $this->_em->createNativeQuery($sql, $rsm);
        $query->setParameters([
            'write_meta' => PhraseaTokens::WRITE_META,
            'make_subdef'=> PhraseaTokens::MAKE_SUBDEF,
            'work_on'    => $subdefName,
            'record_id'  => $recordId,
            'databox_id' => $databoxId
            ]
        );

        return count($query->getResult()) == 0;
    }

    /**
     * return true if we can write meta
     *
     * @param $subdefName
     * @param $recordId
     * @param $databoxId
     * @return bool
     */
    public function canWriteMetadata($subdefName, $recordId, $databoxId)
    {
        $rsm = $this->createResultSetMappingBuilder('w');
        $rsm->addScalarResult('work_on','work_on');

        $sql = 'SELECT work_on
            FROM WorkerRunningJob
            WHERE ((work & :make_subdef) > 0 OR ((work & :write_meta) > 0 AND work_on = :work_on) ) 
            AND record_id = :record_id 
            AND databox_id = :databox_id';

        $query = $this->_em->createNativeQuery($sql, $rsm);
        $query->setParameters([
                'make_subdef'=> PhraseaTokens::MAKE_SUBDEF,
                'write_meta' => PhraseaTokens::WRITE_META,
                'work_on'    => $subdefName,
                'record_id'  => $recordId,
                'databox_id' => $databoxId
            ]
        );

        return count($query->getResult()) == 0;
    }
}
