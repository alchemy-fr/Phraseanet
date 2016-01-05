<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\TaskManager;


interface NotifierInterface
{
    /** Alerts the task manager a new Task has been created */
    const MESSAGE_CREATE = 'create';
    /** Alerts the task manager a Task has been updated */
    const MESSAGE_UPDATE = 'update';
    /** Alerts the task manager a Task has been deleted */
    const MESSAGE_DELETE = 'delete';
    /** Alerts the task manager to send its information data */
    const MESSAGE_INFORMATION = 'information';

    /**
     * Notifies the task manager given a message constant, see MESSAGE_* constants.
     *
     * @param string $message
     *
     * @return mixed|null The return value of the task manager.
     */
    public function notify($message);
}
