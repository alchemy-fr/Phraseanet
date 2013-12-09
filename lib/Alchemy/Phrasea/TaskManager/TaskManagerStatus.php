<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\TaskManager;

use Alchemy\Phrasea\Core\Configuration\ConfigurationInterface;

/**
 * Gets and Sets the Task Manager status
 * This is configuration only, not real activity.
 */
class TaskManagerStatus
{
    private $conf;

    const STATUS_STARTED = 'started';
    const STATUS_STOPPED = 'stopped';

    public function __construct(ConfigurationInterface $conf)
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

        return $this->conf['task-manager']['status'];
    }

    private function setStatus($status)
    {
        $this->ensureConfigurationSchema();
        $managerConf = $this->conf['task-manager'];
        $managerConf['status'] = $status;
        $this->conf['task-manager'] = $managerConf;
    }

    private function ensureConfigurationSchema()
    {
        if (!isset($this->conf['task-manager'])) {
            $this->conf['task-manager'] = ['status' => static::STATUS_STARTED];

            return;
        }
        if (!isset($this->conf['task-manager']['status'])) {
            $conf = $this->conf['task-manager'];
            $conf['status'] = static::STATUS_STARTED;
            $this->conf['task-manager'] = $conf;
        } elseif (!$this->isValidStatus($this->conf['task-manager']['status'])) {
            $conf = $this->conf['task-manager'];
            $conf['status'] = static::STATUS_STARTED;
            $this->conf['task-manager'] = $conf;
        }
    }

    private function isValidStatus($status)
    {
        return in_array($status, [static::STATUS_STARTED, static::STATUS_STOPPED]);
    }
}
