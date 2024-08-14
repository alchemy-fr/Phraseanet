<?php
declare(ticks = 5);
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
use function igorw\get_in;


class PocChild extends Command
{
    const SLEEP = 8;

    /** @var InputInterface $input */
    private $input;
    /** @var OutputInterface $output */
    private $output;

    private $ppid = null;

    public function configure()
    {
        $this->setName("poc:c")
            ->setDescription('poc child')
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
        $s = sprintf("%-11d\t%-2d\tchild  %-5d\t%s\n", $t, $t-$t0, getmypid(), var_export($s, true));
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
        $this->input = $input;
        $this->output = $output;
        $this->ppid = posix_getppid();

        $this->log(sprintf("I'm child started with ppid=%d", $this->ppid));

        $_s = [
            SIGTERM,
            SIGHUP,
            SIGINT,
            SIGQUIT,
            SIGILL,
            SIGABRT,
            SIGPIPE,
            SIGCHLD,
//        SIGSTOP,
            SIGTSTP,
            SIGTTIN,
            SIGTTOU,
        ];
        foreach ($_s as $s) {
            pcntl_signal($s, function (int $signo, $siginfo = null) {
                $this->log(sprintf("received signal %d", $signo));
            });
        }

//        pcntl_sigprocmask(SIG_UNBLOCK, $_s);



//        register_tick_function(
//            function () {
//                $ppid = posix_getppid();
//                if($ppid !== $this->ppid) {
//                    $this->log(sprintf("!!!!!!! ppid = %d", $ppid));
//                    $this->ppid = $ppid;
//                }
//            }
//        );


        pcntl_signal_dispatch();
        $this->log(sprintf("sleeping for %d seconds", self::SLEEP));
        pcntl_signal_dispatch();

        for($i=0; $i<self::SLEEP; $i++) {
            pcntl_signal_dispatch();
            sleep(1);
        }

//        sleep(self::SLEEP);

        pcntl_signal_dispatch();
        $this->log(sprintf("end"));
        pcntl_signal_dispatch();
        return 0;
    }
}
