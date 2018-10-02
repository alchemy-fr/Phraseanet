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

use Alchemy\Phrasea\TaskManager\Editor\IndexerEditor;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer;
use Alchemy\Phrasea\Core\Version;
use Silex\Application;
use Psr\Log\LoggerInterface;


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
        /** @var Indexer $indexer */
        $indexer = $app['elasticsearch.indexer'];

        foreach($app->getDataboxes() as $databox) {
            if($app->getApplicationBox()->is_databox_indexable($databox)) {
                $indexer->indexScheduledRecords($databox);
            }
        }
    }
}

