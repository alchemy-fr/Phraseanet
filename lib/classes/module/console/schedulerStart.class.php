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
use Monolog\Handler;
use Monolog\Logger;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class module_console_schedulerStart extends Command
{

    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->setDescription('Start the scheduler');

        return $this;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ( ! setup::is_installed()) {
            $output->writeln('Phraseanet is not set up');

            return 1;
        }

        $logger = new Logger('Task logger');

        $handler = new Handler\StreamHandler(fopen('php://stdout'), $input->getOption('verbose') ? Logger::DEBUG : Logger::WARNING);
        $logger->pushHandler($handler);

        $logfile = __DIR__ . '/../../../../scheduler.log';
        $handler = new Handler\RotatingFileHandler($logfile, 10, $level = Logger::WARNING);
        $logger->pushHandler($handler);

        try {
            $scheduler = new task_Scheduler();
            $scheduler->run($logger);
        } catch (\Exception $e) {
            switch ($e->getCode()) {
                case task_Scheduler::ERR_ALREADY_RUNNING:   // 114 : aka EALREADY (Operation already in progress)
                    $exitCode = ERR_ALREADY_RUNNING;
                    break;
                default:
                    $exitCode = 1;   // default exit code (error)
                    break;
            }

            return $exitCode;
        }
    }
}
