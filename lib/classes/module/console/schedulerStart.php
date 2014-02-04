<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package     KonsoleKomander
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */

use Alchemy\Phrasea\Command\Command;
use Monolog\Handler;
use Monolog\Logger;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class module_console_schedulerStart extends Command
{

    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->setDescription('Starts Phraseanet scheduler');

        return $this;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $logger = $this->container['task-manager.logger'];

        $streamHandler = new Handler\StreamHandler('php://stdout', $input->getOption('verbose') ? Logger::DEBUG : Logger::WARNING);
        $logger->pushHandler($streamHandler);

        $taskManagerConf = isset($this->container['phraseanet.configuration']['main']['task-manager']) ? $this->container['phraseanet.configuration']['main']['task-manager'] : array();
        $taskManagerConf = array_replace_recursive(array(
            'logger' => array(
                'enabled'   => true,
                'level'     => 'INFO',
                'max-files' => 10,
            )
        ), $taskManagerConf);

        if ($taskManagerConf['logger']['enabled']) {
            $level = defined('Monolog\\Logger::'.$taskManagerConf['logger']['level']) ? constant('Monolog\\Logger::'.$taskManagerConf['logger']['level']) : Logger::INFO;
            $logfile = __DIR__ . '/../../../../logs/scheduler.log';
            $rotateHandler = new Handler\RotatingFileHandler($logfile, $taskManagerConf['logger']['max-files'], $level);
            $logger->pushHandler($rotateHandler);
        }

        try {
            $scheduler = new task_Scheduler($this->container, $logger);
            $scheduler->run();
        } catch (\Exception $e) {
            switch ($e->getCode()) {
                // 114 : aka EALREADY (Operation already in progress)
                case task_Scheduler::ERR_ALREADY_RUNNING:
                    $exitCode = task_Scheduler::ERR_ALREADY_RUNNING;
                    break;
                default:
                    $exitCode = 1;   // default exit code (error)
                    break;
            }

            return $exitCode;
        }
    }
}
