<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\TaskManager\Job;

use Alchemy\TaskManager\AbstractJob as AbstractTMJob;
use Alchemy\TaskManager\JobDataInterface;
use Alchemy\Phrasea\Exception\InvalidArgumentException;

abstract class AbstractJob extends AbstractTMJob implements JobInterface
{
    /** @var float */
    protected $period = 0.05;

    /**
     * {@inheritdoc}
     */
    public function setPauseDuration($period)
    {
        $this->period = $period;

        return $this;
    }

    /**
     * Returns the duration of pause between two runs of the job.
     *
     * @return float
     */
    public function getPauseDuration()
    {
        return $this->period;
    }

    /**
     * {@inheritdoc}
     */
    final protected function doRun(JobDataInterface $data = null)
    {
        if (!$data instanceof JobData) {
            throw new InvalidArgumentException('JobData must be passed to a JobInterface::Run command.');
        }

        $this->setPauseDuration($data->getTask()->getPeriod());
        $this->doJob($data);
    }

    /**
     * Does execute the job
     *
     * @param JobData $data The data
     */
    abstract protected function doJob(JobData $data);
}
