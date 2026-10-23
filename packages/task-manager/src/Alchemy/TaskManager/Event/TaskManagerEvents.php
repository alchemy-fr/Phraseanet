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

class TaskManagerEvents
{
    /**
     * This event is triggered when the task manager starts running.
     */
    const MANAGER_START = 'manager-start';

    /**
     * This event is triggered when the task manager stops running.
     */
    const MANAGER_STOP = 'manager-stop';

    /**
     * This event is triggered when the task manager receives a stops request.
     */
    const STOP_REQUEST = 'stop-request';

    /**
     * This event is triggered when the task manager stops running.
     */
    const MANAGER_REQUEST = 'manager-request';

    /**
     * This event is triggered when the task manager stops running.
     */
    const MANAGER_TICK = 'manager-tick';
}
