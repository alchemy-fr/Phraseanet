<?php

/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\TaskManager\Editor\DefaultEditor;
use Alchemy\Phrasea\TaskManager\Event\JobEvents;
use Alchemy\Phrasea\TaskManager\Event\JobFinishedEvent;

class EmptyCollectionJob extends AbstractJob
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->translator->trans("Vidage de collection");
    }

    /**
     * {@inheritdoc}
     */
    public function getJobId()
    {
        return 'EmptyCollection';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->translator->trans("Empty a collection");
    }

    /**
     * {@inheritdoc}
     */
    public function getEditor()
    {
        return new DefaultEditor($this->translator);
    }

    /**
     * {@inheritdoc}
     */
    protected function doJob(JobData $data)
    {
        $app = $data->getApplication();
        $task = $data->getTask();

        $settings = simplexml_load_string($task->getSettings());

        $baseId = (string) $settings->bas_id;

        $collection = \collection::getByBaseId($app, $baseId);
        $collection->empty_collection(200);

        if (0 === $collection->get_record_amount()) {
            $this->stop();
            $this->dispatcher->dispatch(JobEvents::FINISHED, new JobFinishedEvent($task));
        }
    }
}
