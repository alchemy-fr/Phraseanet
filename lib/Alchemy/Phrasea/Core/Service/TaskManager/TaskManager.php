<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Service\TaskManager;

use Alchemy\Phrasea\Core;
use Alchemy\Phrasea\Core\Service;
use Alchemy\Phrasea\Core\Service\ServiceAbstract;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Bridge\Monolog\Logger;
use Monolog\Handler\SyslogHandler;
use Monolog\Handler\NativeMailerHandler;
use Alchemy\Phrasea\Exception\RuntimeException;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
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

        // send log to syslog ?
        if (null !== ($syslogLevel = constant($options['syslog_level']))) {
            $handler = new SyslogHandler(
                    "Phraseanet-Task", // string added to each message
                    "user", // facility (type of program logging)
                    $syslogLevel, // level
                    true        // bubble
            );
            $logger->pushHandler($handler);
        }

        // send log by mail ?
        if (null !== ($maillogLevel = constant($options['maillog_level']))) {
            if (($adminMail = $registry->get('GV_adminMail')) == '') {
                throw(new Alchemy\Phrasea\Exception\RuntimeException(sprintf(
                        "Admininstrator mail must be set to get log by mail."))
                );
            }
            $senderMail = $registry->get('GV_defaultmailsenderaddr');

            $handler = new NativeMailerHandler(
                    $adminMail,
                    "Phraseanet-Task",
                    $senderMail,
                    $maillogLevel, // level
                    true
            );
            $logger->pushHandler($handler);
        }

        return $logger;
    }

    /**
     * Set and return a new \task_manager instance
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
