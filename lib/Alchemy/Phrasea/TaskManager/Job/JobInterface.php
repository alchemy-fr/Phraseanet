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

use Alchemy\Phrasea\TaskManager\Editor\EditorInterface;
use Alchemy\TaskManager\Job\JobInterface as JobTMInterface;

interface JobInterface extends JobTMInterface
{
    /**
     * Returns an Id for the Job.
     *
     * @return string
     */
    public function getJobId();

    /**
     * Returns the editor related to this Job.
     *
     * @return EditorInterface
     */
    public function getEditor();

    /**
     * Returns a name related to this Job.
     *
     * @return string
     */
    public function getName();

    /**
     * Returns a description related to this Job.
     *
     * @return string
     */
    public function getDescription();

    /**
     * Sets the pause duration between two run of the job.
     *
     * @param type $period
     *
     * @return JobInterface
     */
    public function setPauseDuration($period);
}
