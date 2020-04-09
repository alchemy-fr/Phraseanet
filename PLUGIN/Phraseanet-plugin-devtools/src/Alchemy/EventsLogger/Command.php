<?php

namespace Alchemy\DevToolsPlugin\EventsLogger;


use Alchemy\Phrasea\Command\Command as phrCommand;
use Silex\Application;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * list custom reports
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Command extends phrCommand
{
    /** @var InputInterface $input */
    private $input;
    /** @var OutputInterface $output */
    private $output;

    private $run = true;            // set to false by Ctrl-C handler

    public function configure()
    {
        $this->setName("devtools:eventslogger")
            ->setDescription('trace events')
            ->addArgument('action', InputArgument::REQUIRED, 'action : [list|trace]')
            // ->setHelp('')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $this->input  = $input;
        $this->output = $output;

        // add cool styles
        $style = new OutputFormatterStyle('black', 'yellow'); // , array('bold'));
        $output->getFormatter()->setStyle('warning', $style);

        $ret = 0;
        switch($action = $input->getArgument('action')) {
            case 'list':
                $this->actionList();
                break;
            case 'trace':
                $this->actionTrace();
                break;
            default:
                $output->writeln(sprintf('<error>action "%s" unknown</error>', $action));
                $ret = -1;
                break;
        }

        return $ret;
    }

    /**
     * list available events to be traced
     */
    private function actionList()
    {
        $table = $this->getHelperSet()->get('table');
        $table->setHeaders(['class', 'constant', 'handler']);

        $rows = [];
        foreach(EventsSources::getSources() as $k => $v) {
            $handler = 'on' . $k;
            if(!method_exists("Alchemy\\EventsLoggerPlugin\\EventsLoggerSubscriber", $handler)) {
                $handler = 'onEvents';
            }
            foreach($v['constants'] as $c) {
                $rows[] = [$v['class'], $c, $handler];
            }
        }
        $table->setRows($rows);

        $table->render($this->output);
    }

    /**
     * display messages from socket
     */
    private function actionTrace()
    {
        /** @var Application $app */
        $app = $this->container;

        // allow to terminate cleanly, closing / deleting socket
        pcntl_signal(SIGINT, [$this, 'doInterrupt']);
        pcntl_signal(SIGTERM, [$this, 'doInterrupt']);
        if(function_exists('pcntl_async_signals')) {
            pcntl_async_signals(true);      // >= PHP 7.1
        }

        /** PropertyAccess $config */
        $config = $app['conf'];
        $socketpath = '/tmp/phrEvts_' . $config->get(['main', 'key']);  // the socket name is related to each phraseanet instance

        @unlink($socketpath);       // just in case : delete a forgotten socket

        if(($socket = stream_socket_server('unix://' . $socketpath)) === false) {
            $this->output->writeln(sprintf('<error>Failed to open socket "%s"</error>', $socketpath));
            exit(-1);
        }
        chmod($socketpath, 0777);  // so www-data can write to it

        $this->output->writeln(sprintf('<info>Tracing messages from socket "%s"... (Type ^C to quit)</info>', $socketpath));

        $cnx = null;
        while($this->run) {
            pcntl_signal_dispatch();
            while ($this->run && ($cnx = @stream_socket_accept($socket, 1))) {
                pcntl_signal_dispatch();

                while ($this->run && ($msg = fread($cnx, 1000)) ) {
                    pcntl_signal_dispatch();
                    $this->output->write($msg);
                }

                fclose($cnx);
                $cnx = null;
            }
        }

        if($cnx) {
            fclose($cnx);
        }
        if($socket) {
            stream_socket_shutdown($socket, STREAM_SHUT_RDWR);
        }

        $this->output->writeln('');
        $this->output->writeln(sprintf('<info>Socket "%s" closed.</info>', $socketpath));
        @unlink($socketpath);
    }

    /**
     * interrupt handler Ctrl-C or kill
     */
    private function doInterrupt()
    {
        $this->run = false;
    }
}