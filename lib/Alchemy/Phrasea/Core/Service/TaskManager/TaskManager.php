<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Service\TaskManager;

use Alchemy\Phrasea\Core\Service\ServiceAbstract;
use Symfony\Bridge\Monolog\Logger;
use Monolog\Handler\SyslogHandler;
use Monolog\Handler\NativeMailerHandler;
use Alchemy\Phrasea\Exception\RuntimeException;

class TaskManager extends ServiceAbstract
{
    /** `
     * `@var \task_manager
     */
    protected $taskManager;

    /**
     * {@inheritdoc}
     */
    protected function init()
    {
        $options = $this->getOptions();
        $logger = $this->extendsLogger(clone $this->app['monolog'], $options);

        $this->taskManager = new \task_manager($this->app, $logger);
    }

    private function extendsLogger(Logger $logger, $options)
    {
        $options = $this->getOptions();
        $registry = $this->app['phraseanet.registry'];

        if (null !== $syslogLevel = constant($options['syslog_level'])) {
            $handler = new SyslogHandler("Phraseanet-Task", "user", $syslogLevel);
            $logger->pushHandler($handler);
        }

        if (null !== $maillogLevel = constant($options['maillog_level'])) {
            if ('' === $adminMail = trim($registry->get('GV_adminMail'))) {
                throw new RuntimeException("Admininstrator mail must be set to get log by mail.");
            }
            $senderMail = $registry->get('GV_defaultmailsenderaddr');

            $handler = new NativeMailerHandler($adminMail, "Phraseanet-Task", $senderMail, $maillogLevel);
            $logger->pushHandler($handler);
        }

        return $logger;
    }

    /**
     * Set and return a new task_manager instance
     *
     * @return \task_manager
     */
    public function getDriver()
    {
        return $this->taskManager;
    }

    /**
     * Return the type of the service
     *
     * @return string
     */
    public function getType()
    {
        return 'task-manager';
    }

    /**
     * Define the mandatory option for the current services
     *
     * @return array
     */
    public function getMandatoryOptions()
    {
        return array();
    }

}
