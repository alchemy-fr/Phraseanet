<?php
declare(ticks = 5);

namespace Alchemy\Phrasea\WorkerManager\Command;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\WorkerManager\Queue\AMQPConnection;
use Alchemy\Phrasea\WorkerManager\Queue\MessageHandler;
use Alchemy\Phrasea\WorkerManager\Worker\WorkerInvoker;
use Doctrine\DBAL\Connection;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WorkerExecuteCommand extends Command
{
    private $wec_upid = '-';

    /** @var WorkerInvoker $workerInvoker */
    private $workerInvoker;

    /**
     * Constructor
     */
    public function __construct()
    {
        $_s = [
            // SIGHUP,     // 1
            // SIGINT,     // 2
            SIGQUIT,    // 3
            // SIGILL,     // 4
            // SIGABRT,    // 6
            SIGUSR1,    // 10
            // SIGPIPE,    // 13
            SIGTERM,    // 15
            //            SIGCHLD,    // 17
            // !!!        SIGSTOP,  // 19
            // SIGTSTP,    // 20
            // SIGTTIN,    // 21
            // SIGTTOU,    // 22
        ];
        foreach ($_s as $s) {
            pcntl_signal($s, function (int $signo, $siginfo = null) {
                $this->log(sprintf("received signal %d", $signo), 1);
                if($signo === SIGQUIT) {
                    $this->log(sprintf("this is SIGQUIT, I will send SIGTERM to my children"), 1);
                    $i = 0;
                    foreach ($this->workerInvoker->getProcesses() as $process) {
                        $this->log(sprintf("sending SIGTERM to child %d", ++$i), 1);
                        try {
                            $process->signal(SIGTERM);
                            $this->log(sprintf(" - SIGTERM sent to child %d", $i), 1);
                        } catch (\Exception $e) {
                            $this->log(sprintf(" - error sending SIGTERM to child %d: %s", $i, $e->getMessage()), 1);
                        }
                    }
                }
            });
        }

        register_shutdown_function(function() {
            $this->log("shutdown");
        });
        pcntl_signal_dispatch();

        $this->log("construct");
        parent::__construct('worker:execute');

        $this->setDescription('Listen queues defined on configuration, launch corresponding service for execution')
            ->addOption('preserve-payload', 'p', InputOption::VALUE_NONE, 'Preserve temporary payload file')
            ->addOption('queue-name', '', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'The name of queues to be consuming')
            ->addOption('max-processes', 'm', InputOption::VALUE_REQUIRED, 'The max number of process allow to run (default 1) ')
            ->addOption('wec_upid', null, InputOption::VALUE_OPTIONAL, 'unique pseudo-pid of the parent (based on date w. microseconds)', '')
//            ->addOption('MWG', '', InputOption::VALUE_NONE, 'Enable MWG metadata compatibility (use only for write metadata service)')
//            ->addOption('clear-metadatas', '', InputOption::VALUE_NONE, 'Remove metadatas from documents if not compliant with Database structure (use only for write metadata service)')
            ->setHelp('');

        pcntl_signal_dispatch();
        return $this;
    }

    private function log($s = '', $depth=0)
    {
        static $t0 = null;
        $t = microtime(true);
        if($t0 === null) {
            $t0 = $t;
        }
        $dt = (int)(1000000.0*($t - $t0));
        $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $depth+1);
        $line = array_key_exists($depth, $bt) && array_key_exists('line', $bt[$depth]) ? $bt[$depth]['line'] : -1;

        $s = sprintf("%s , %d , pid=%-5d ppid=%-5d line=%-4d , %s\n", $this->wec_upid, $dt, getmypid(), posix_getppid(), $line, var_export($s, true));
        $f = fopen("/var/alchemy/Phraseanet/logs/trace_wec.txt", "a");
        fwrite($f, $s);
        fflush($f);
        fclose($f);

        pcntl_signal_dispatch();

    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $this->wec_upid = $input->getOption('wec_upid');
        $this->log("doExecute");

        pcntl_signal_dispatch();


        // close all connection initialized in databox and appbox class for this worker command
        //  the consumer will launch the command bin/console worker:run-service with all needed DB connection
        foreach ($this->container['dbs.options'] as $name => $options) {
            pcntl_signal_dispatch();
            $this->container['dbs'][$name]->close();
        }
        pcntl_signal_dispatch();
        $this->container['connection.pool.manager']->closeAll();
        // close DB connection finished

        $argQueueName = $input->getOption('queue-name');
        $maxProcesses = intval($input->getOption('max-processes'));

        pcntl_signal_dispatch();
        /** @var AMQPConnection $serverConnection */
        $serverConnection = $this->container['alchemy_worker.amqp.connection'];

        pcntl_signal_dispatch();
        /** @var AMQPChannel $channel */
        $channel = $serverConnection->getChannel();

        if ($channel == null) {
            pcntl_signal_dispatch();
            $output->writeln("Can't connect to rabbit, check configuration!");

            return 1;
        }

        pcntl_signal_dispatch();
        $serverConnection->declareExchange();

        pcntl_signal_dispatch();

        $this->workerInvoker = $this->container['alchemy_worker.worker_invoker'];

        if ($input->getOption('max-processes') != null && $maxProcesses == 0) {
            $output->writeln('<error>Invalid max-processes option.Need an integer</error>');

            return 1;
        } elseif($maxProcesses) {
            $this->workerInvoker->setMaxProcessPoolValue($maxProcesses);
        }

        if ($input->getOption('preserve-payload')) {
            $this->workerInvoker->preservePayloads();
        }
        pcntl_signal_dispatch();

        /** @var MessageHandler $messageHandler */
        $messageHandler = $this->container['alchemy_worker.message.handler'];
        $this->log("consume");
        $messageHandler->consume($channel, $serverConnection, $this->workerInvoker, $argQueueName, $maxProcesses, $this->wec_upid);

        /** @var Connection $dbConnection */
        $dbConnection = $this->container['orm.em']->getConnection();

        pcntl_signal_dispatch();
        while (count($channel->callbacks)) {
            // check connection for DB before given message to consumer
            // otherwise return 1
            pcntl_signal_dispatch();
            if($dbConnection->ping() === false){
                $output->writeln("MySQL server is not available : retry to close and connect ....");

                try {
                    $dbConnection->close();
                    $dbConnection->connect();
                } catch (\Exception $e) {
                    // Mysql server can't be reconnected, so stop the worker
                    $serverConnection->connectionClose();

                    return 1;
                }
            }

            do {
                try {
                    $ex = null;
                    pcntl_signal_dispatch();

                    $channel->wait(null, false, 1);
                    pcntl_signal_dispatch();
                }
                catch (AMQPTimeoutException $ex) {
                    pcntl_signal_dispatch();
                    // no-op
                }
            }
            while($ex);

        }

        $serverConnection->connectionClose();

        $this->log();

        return 0;
    }
}

