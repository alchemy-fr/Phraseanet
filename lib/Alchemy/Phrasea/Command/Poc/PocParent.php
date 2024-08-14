<?php

/*
 * This file is part of phraseanet-plugins.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Poc;


use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Model\Manipulator\WebhookEventManipulator;
use appbox;
use collection;
use databox;
use Doctrine\DBAL\DBALException;
use PDO;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use function igorw\get_in;


class PocParent extends Command
{
    const SLEEP = 7;

    /** @var InputInterface $input */
    private $input;
    /** @var OutputInterface $output */
    private $output;

    public function configure()
    {
        $this->setName("poc:p")
            ->setDescription('poc parent')
            // ->setHelp('')
        ;
    }

    private function log($s = '')
    {
        static $t0 = null;
        $t = time();
        if($t0 === null) {
            $t0 = $t;
        }
        $s = sprintf("%-11d\t%-2d\tparent %-5d\t%s\n", $t, $t-$t0, getmypid(), var_export($s, true));
        file_put_contents("/var/alchemy/Phraseanet/logs/trace_poc.txt", $s . "\n", FILE_APPEND);
        $this->output->writeln($s);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        file_put_contents("/var/alchemy/Phraseanet/logs/worker_service.log", "");
        file_put_contents("/var/alchemy/Phraseanet/logs/trace_sh.txt", "");
        file_put_contents("/var/alchemy/Phraseanet/logs/trace_wec.txt", "");
        file_put_contents("/var/alchemy/Phraseanet/logs/trace_messagehandler.txt", "");
        file_put_contents("/var/alchemy/Phraseanet/logs/trace_workerinvoker.txt", "");
        file_put_contents("/var/alchemy/Phraseanet/logs/trace_wrsc.txt", "");
        file_put_contents("/var/alchemy/Phraseanet/logs/trace_scw.txt", "");

        return 0;

        $this->input = $input;
        $this->output = $output;

        file_put_contents("/var/alchemy/Phraseanet/logs/trace_poc.txt", "");

        $xxx = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

//        $_s = [
//            SIGTERM,
//            SIGHUP,
//            SIGINT,     // 2
//            SIGQUIT,
//            SIGILL,
//            SIGABRT,
//            SIGPIPE,
//            SIGCHLD,    // 17
////        SIGSTOP,
//            SIGTSTP,
//            SIGTTIN,
//            SIGTTOU,
//        ];
//        foreach ($_s as $s) {
//            pcntl_signal($s, function (int $signo, $siginfo = null) {
//                $this->log(sprintf("received signal %d", $signo));
//            });
//        }

//        pcntl_sigprocmask(SIG_UNBLOCK, $_s);


        $this->log(sprintf("I'm parent process"));
        $p = null;
        $p = new Process(
  //          "/var/alchemy/Phraseanet/bin/console poc:c",
            "/bin/bash /var/alchemy/Phraseanet/poc.sh",
            null,
            null,
            null,
            60,
            []
        );
        if($p) {
//        $p->setEnhanceSigchildCompatibility(true);
//        $p->setOptions(['bypass_shell' => true]);
            $p->start(
//            function($type, $buffer) {
//                $this->log(sprintf("received %s: %s", $type, var_export($buffer, true)));
//            }
            );
            $childPid = $p->getPid();
            $this->log(sprintf("child process started with pid=%d", $childPid));
        }


        $ps = [];
        exec("exec pwd", $ps);
        $this->log(sprintf("ps:\n%s", join("\n", $ps)));
        exec("exec ps -faux", $ps);
        $this->log(sprintf("ps:\n%s", join("\n", $ps)));
//        $output->writeln(sprintf("parent %d doing exec(\"/var/alchemy/Phraseanet/bin/console poc:c\")", getmypid()));
//        exec("/var/alchemy/Phraseanet/bin/console poc:c");
//
//        $output->writeln(sprintf("parent %d doing passthru(\"/var/alchemy/Phraseanet/bin/console poc:c\")", getmypid()));
//        passthru("/var/alchemy/Phraseanet/bin/console poc:c");
//

        $this->log(sprintf("sleeping for %d seconds", 4));
        pcntl_signal_dispatch();

        sleep(1);
        pcntl_signal_dispatch();
        sleep(1);
        pcntl_signal_dispatch();
        sleep(1);
        pcntl_signal_dispatch();
        sleep(1);
        pcntl_signal_dispatch();
        if($p) {
//        $this->log(sprintf("sending SIGTERM to process %d", $childPid));
//        posix_kill($childPid, SIGTERM);

//        $this->log(sprintf("signal SIGTERM sent to process %d", $childPid));
//        $p->signal(SIGTERM);
        }
        $this->log(sprintf("sleeping for %d seconds", 1));
        pcntl_signal_dispatch();
        sleep(1);

        $ps = [];
        exec("exec ps -faux", $ps);
        $this->log(sprintf("ps:\n%s", join("\n", $ps)));


        $this->log(sprintf("sleeping for %d seconds", 3));
        pcntl_signal_dispatch();
        sleep(1);
        pcntl_signal_dispatch();
        sleep(1);
        pcntl_signal_dispatch();
        sleep(1);
        pcntl_signal_dispatch();

        $this->log(sprintf("end"));
        return 0;
    }
}
