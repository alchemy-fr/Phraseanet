<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
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

        $this->setDescription('Start the scheduler');

        return $this;
    }

    public function requireSetup()
    {
        return true;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ( ! $this->checkSetup($output)) {
            return 1;
        }

        $logger = new Logger('Task logger');

        $streamHandler = new Handler\StreamHandler(fopen('php://stdout', 'a'), $input->getOption('verbose') ? Logger::DEBUG : Logger::WARNING);
        $logger->pushHandler($streamHandler);

        $logfile = __DIR__ . '/../../../../logs/scheduler.log';
        $rotateHandler = new Handler\RotatingFileHandler($logfile, 10);
        $logger->pushHandler($rotateHandler);

        try {
            $scheduler = new task_Scheduler($logger);
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
