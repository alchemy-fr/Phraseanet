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

use Alchemy\Phrasea\TaskManager\Editor\DefaultEditor;

class NullJob extends AbstractJob
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Null Job';
    }

    /**
     * {@inheritdoc}
     */
    public function getJobId()
    {
        return 'Null';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'This is a implementation example';
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
        $this->log('debug', 'this is the Null Job');
    }
}
