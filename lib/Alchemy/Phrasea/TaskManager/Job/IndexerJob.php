<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\Core\PhraseaTokens;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\BulkOperation;
use Alchemy\Phrasea\SearchEngine\Elastic\Fetcher\ScheduledIndexationRecordFetcher;
use Alchemy\Phrasea\SearchEngine\Elastic\RecordHelper;
use Alchemy\Phrasea\TaskManager\Editor\IndexerEditor;
use Alchemy\Phrasea\TaskManager\Editor\SubdefsEditor;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\SQLAnywhere11Platform;
use Doctrine\DBAL\SQLParserUtils;
use MediaAlchemyst\Transmuter\Image2Image;

class IndexerJob extends AbstractJob
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->translator->trans('Indexation task');
    }

    /**
     * {@inheritdoc}
     */
    public function getJobId()
    {
        return 'Indexer';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->translator->trans("Indexing Batch (collections/databox)");
    }

    /**
     * {@inheritdoc}
     */
    public function getEditor()
    {
        return new IndexerEditor($this->translator);
    }

    /**
     * {@inheritdoc}
     */
    protected function doJob(JobData $data)
    {
        $app = $data->getApplication();

        $recordHelper = new RecordHelper($app['phraseanet.appbox']);

        // set bulk
        $bulk = new BulkOperation($app['elasticsearch.client']);
        $bulk->setDefaultIndex($app['elasticsearch.options']['index']);
        $bulk->setAutoFlushLimit(1000);

        foreach ($app['phraseanet.appbox']->get_databoxes() as $databox) {
            if (!$this->isStarted()) {
                break;
            }

            $connection = $databox->get_connection();

            // fetch records with 'to_index' flag set and 'indexing' flag not set
            $fetcher = new ScheduledIndexationRecordFetcher($databox, $recordHelper);
            $fetcher->setBatchSize(200);

            // set 'indexing' flag, unset 'to_index' flag once
            // records have been fetched
            $fetcher->setPostFetch(function($records) use ($connection) {
                $sql = <<<SQL
                UPDATE record
                SET jeton = ((jeton | ?) & (jeton & ~ ?))
                WHERE record_id IN (?)
SQL;
                $records = array_map(function($record) {
                    return $record['record_id'];
                }, $records);

                $connection->executeQuery($sql, [PhraseaTokens::TOKEN_INDEXING, PhraseaTokens::TOKEN_INDEX, $records], [\PDO::PARAM_INT, \PDO::PARAM_INT, Connection::PARAM_INT_ARRAY]);
            });

            // update es index
            $app['elasticsearch.indexer.record_indexer']->update($bulk, $fetcher);

            // unset 'indexing' flag
            $sql = <<<SQL
                UPDATE record
                SET jeton = (jeton & ~ ?)
SQL;
            $connection->executeQuery($sql, [PhraseaTokens::TOKEN_INDEXING], [\PDO::PARAM_INT]);
        }
    }
}
