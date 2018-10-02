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

use Alchemy\TaskManager\Job\AbstractJob as AbstractTMJob;
use Alchemy\TaskManager\Job\JobDataInterface;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\TranslatorInterface;

abstract class AbstractJob extends AbstractTMJob implements JobInterface
{
    /** @var float */
    protected $period = 0.05;
    protected $translator;

    public function __construct(
        TranslatorInterface $translator,
        EventDispatcherInterface $dispatcher = null,
        LoggerInterface $logger = null
    )
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
    final protected function doRun(JobDataInterface $data)
    {
        if (!$data instanceof JobData) {
            throw new InvalidArgumentException(sprintf('Phraseanet jobs require Phraseanet JobData, got %s.', get_class($data)));
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
