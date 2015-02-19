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
        $app['elasticsearch.indexer']->indexScheduledRecords();
    }
}
