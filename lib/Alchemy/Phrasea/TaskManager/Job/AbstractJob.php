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

use Alchemy\TaskManager\AbstractJob as AbstractTMJob;
use Alchemy\TaskManager\JobDataInterface;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\TranslatorInterface;

abstract class AbstractJob extends AbstractTMJob implements JobInterface
{
    /** @var float */
    protected $period = 0.05;
    protected $translator;

    public function __construct(EventDispatcherInterface $dispatcher = null, LoggerInterface $logger = null, TranslatorInterface $translator)
    {
        parent::__construct($dispatcher, $logger);
        $this->translator = $translator;
    }

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
