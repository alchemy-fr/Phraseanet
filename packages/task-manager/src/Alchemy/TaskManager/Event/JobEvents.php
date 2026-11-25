<?php

/*
 * This file is part of Alchemy Task Manager
 *
 * (c) 2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\TaskManager\Event;

class JobEvents
{
    /**
     * This event is triggered when the job starts running.
     */
    const START = 'job-start';

    /**
     * This event is triggered on PHP tick.
     */
    const TICK = 'job-tick';

    /**
     * This event is triggered when the the stop method is called and the job is running.
     */
    const STOP_REQUEST = 'job-stop-request';

    /**
     * This event is triggered when the job stops running.
     */
    const STOP = 'job-stop';

    /**
     * This event is triggered whenever an exception is triggered during the job run.
     */
    const EXCEPTION = 'job-exception';
}
