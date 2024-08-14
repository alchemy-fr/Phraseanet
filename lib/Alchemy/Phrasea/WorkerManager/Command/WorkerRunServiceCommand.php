<?php
declare(ticks = 5);

namespace Alchemy\Phrasea\WorkerManager\Command;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\WorkerManager\Worker\Resolver\WorkerResolverInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WorkerRunServiceCommand extends Command
{
    private $wec_upid = '-';
    private $wrsc_upid = '-';

    private $z_upid = '-';

    public function __construct()
    {
        $this->z_upid = Uuid::uuid4()->toString();

        parent::__construct('worker:run-service');

        register_shutdown_function(function() {
            $this->log(sprintf("shutdown"));
        });

//        $_s = [
//            // SIGHUP,     // 1
//            // SIGINT,     // 2
//            SIGQUIT,    // 3
//            // SIGILL,     // 4
//            // SIGABRT,    // 6
//            SIGUSR1,    // 10
//            // SIGPIPE,    // 13
//            SIGTERM,    // 15
//            //            SIGCHLD,    // 17
//            // !!!        SIGSTOP,  // 19
//            // SIGTSTP,    // 20
//            // SIGTTIN,    // 21
//            // SIGTTOU,    // 22
//        ];
//        foreach ($_s as $s) {
//            pcntl_signal($s, function (int $signo, $siginfo = null) {
//                $this->log(sprintf("signal %d", $signo), 1);
//                $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
//                $this->log(sprintf("backtrace:\n%s", var_export($bt, true)), 1);
//            });
//        }


        $this->setDescription('Execute a service')
            ->addArgument('type', InputArgument::REQUIRED)
            ->addArgument('body', InputArgument::OPTIONAL)
            ->addOption('preserve-payload', 'p', InputOption::VALUE_NONE, 'Preserve temporary payload file')
            ->addOption('wec_upid', null, InputOption::VALUE_OPTIONAL, 'unique pseudo-pid of the WorkerExecuteCommand parent', '')
            ->addOption('wrsc_upid', null, InputOption::VALUE_OPTIONAL, 'unique pseudo-pid of this command', '')
        ;
        $this->log("construct");

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
        $s = sprintf("\"%s\" , \"%s\" , \"%s\" , %d , %d , %d , %d , \"%s\"\n", $this->z_upid, $this->wec_upid, $this->wrsc_upid, posix_getppid(), getmypid(), $dt, $line, var_export($s, true));
        $f = fopen("/var/alchemy/Phraseanet/logs/trace_wrsc.txt", "a");
        fwrite($f, $s);
        fflush($f);
        fclose($f);

        pcntl_signal_dispatch();

    }


    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $this->wec_upid = $input->getOption('wec_upid');
        $this->wrsc_upid = $input->getOption('wrsc_upid');
        $this->log("doExecute");

        pcntl_signal_dispatch();

        /** @var WorkerResolverInterface $workerResolver */
        $workerResolver = $this->container['alchemy_worker.type_based_worker_resolver'];

        $type = $input->getArgument('type');

        pcntl_signal_dispatch();
        $body = [];
        if($input->getArgument('body')) {
            $body = @file_get_contents($input->getArgument('body'));

            if ($body === false) {
                $output->writeln(sprintf('<error>Unable to read payload file %s</error>', $input->getArgument('body')));

                return;
            }

            $body = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $output->writeln('<error>Invalid message body</error>');

                return;
            }
        }
        $body['wec_upid'] = $this->wec_upid;
        $body['wrsc_upid'] = $this->wrsc_upid;

        pcntl_signal_dispatch();
        $worker = $workerResolver->getWorker($type);

        pcntl_signal_dispatch();


        $worker->process($body);

        pcntl_signal_dispatch();
        if (! $input->getOption('preserve-payload')) {
            @unlink($input->getArgument('body'));
        }
        pcntl_signal_dispatch();

    }
}
