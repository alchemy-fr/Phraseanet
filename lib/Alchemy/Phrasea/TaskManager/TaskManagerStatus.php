<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\TaskManager;

use Alchemy\Phrasea\Core\Configuration\PropertyAccess;

/**
 * Gets and Sets the Task Manager status
 * This is configuration only, not real activity.
 */
class TaskManagerStatus
{
    private $conf;

    const STATUS_STARTED = 'started';
    const STATUS_STOPPED = 'stopped';

    public function __construct(PropertyAccess $conf)
    {
        $this->conf = $conf;
    }

    /**
     * Sets Task Manager status to start
     */
    public function start()
    {
        $this->setStatus(static::STATUS_STARTED);
    }

    /**
     * Sets Task Manager status to stop
     */
    public function stop()
    {
        $this->setStatus(static::STATUS_STOPPED);
    }

    /**
     * Checks if the Task Manager status is set on "started"
     *
     * @return Boolean
     */
    public function isRunning()
    {
        return static::STATUS_STARTED === $this->getStatus();
    }

    /**
     * Returns the current status of the task manager
     *
     * @return string
     */
    public function getStatus()
    {
        $this->ensureConfigurationSchema();

        return $this->conf->get(['main', 'task-manager', 'status']);
    }

    private function setStatus($status)
    {
        $this->ensureConfigurationSchema();
        $this->conf->set(['main', 'task-manager', 'status'], $status);
    }

    private function ensureConfigurationSchema()
    {
        if (!$this->conf->has(['main', 'task-manager'])) {
            $this->conf->set(['main', 'task-manager'], ['status' => static::STATUS_STARTED]);

            return;
        }
        if (!$this->conf->has(['main', 'task-manager', 'status'])) {
            $this->conf->set(['main', 'task-manager', 'status'], static::STATUS_STARTED);
        } elseif (!$this->isValidStatus($this->conf->get(['main', 'task-manager', 'status']))) {
            $this->conf->set(['main', 'task-manager'], ['status' => static::STATUS_STARTED]);
        }
    }

    private function isValidStatus($status)
    {
        return in_array($status, [static::STATUS_STARTED, static::STATUS_STOPPED]);
    }
}
