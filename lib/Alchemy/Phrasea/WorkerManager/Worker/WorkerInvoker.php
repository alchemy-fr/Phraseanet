<?php

namespace Alchemy\Phrasea\WorkerManager\Worker;

use PhpAmqpLib\Channel\AMQPChannel;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Process\Exception\RuntimeException as ProcessRuntimeException;
use Symfony\Component\Process\Process;

class WorkerInvoker implements LoggerAwareInterface
{
    /**
     * @var string
     */
    private $environment;

    /**
     * @var string
     */
    private $binaryPath;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ProcessPool
     */
    private $processPool;

    /**
     * @var Process
     */
    private $process;

    /**
     * @var bool
     */
    private $preservePayloads = false;

    /**
     * payload file prefix
     *
     * @var string
     */
    private $prefix = 'alchemy_wk_';

    private $recordId = '-';
    private $subdefName = '-';
    private $wec_upid = '-';
    private $wrsc_upid = '-';

    private $processes = [];

    /**
     * WorkerInvoker constructor.
     *
     * @param ProcessPool $processPool
     * @param bool $environment
     */
    public function __construct(ProcessPool $processPool, $environment = false)
    {
        $this->binaryPath  = $_SERVER['SCRIPT_NAME'];
        $this->environment = $environment;
        $this->processPool = $processPool;
        $this->logger      = new NullLogger();
    }

    public function preservePayloads()
    {
        $this->preservePayloads = true;
    }

    /**
     * Sets a logger instance on the object
     *
     * @param LoggerInterface $logger
     * @return null
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
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
        $s = sprintf("%s , %s , %s , %s , %d , pid=%-5d ppid=%-5d line=%d , %s\n", $this->wec_upid, $this->wrsc_upid, $this->recordId, $this->subdefName, $dt, getmypid(), posix_getppid(), $line, var_export($s, true));
        file_put_contents("/var/alchemy/Phraseanet/logs/trace_workerinvoker.txt", $s . "\n", FILE_APPEND);
        pcntl_signal_dispatch();
    }


    /**
     * @param string $messageType
     * @param string $payload
     */
    public function invokeWorker(string $messageType, string $payload, AMQPChannel $channel, string $wec_upid = '', string $wrsc_upid = '')
    {
        pcntl_signal_dispatch();
        $this->wec_upid = $wec_upid;
        $this->wrsc_upid = $wrsc_upid;

        $this->recordId = 0;
        $this->subdefName = '-';
        $this->log("invokeWorker");
        try {
            pcntl_signal_dispatch();
            $jsp = @json_decode($payload, true);
            if($jsp) {
                $this->recordId = $jsp['recordId'] ?: 0;
                $this->subdefName = $jsp['subdefName'] ?: '-';
            }
            pcntl_signal_dispatch();
        }
        catch (\Exception $e) {
            pcntl_signal_dispatch();
            // nop
        }

        $args = [
            "exec",
            $this->binaryPath,
            'worker:run-service',
            '-vv',
            $messageType,
            $this->createPayloadFile($payload),
            '--wec_upid=' . $wec_upid,
            '--wrsc_upid=' . $wrsc_upid,
        ];

        if ($this->environment) {
            $args[] = sprintf('-e=%s', $this->environment);
        }

        if ($this->preservePayloads) {
            $args[] = '--preserve-payload';
        }
        pcntl_signal_dispatch();

        $this->process = $this->processPool->getWorkerProcess($args, $channel, getcwd());
        pcntl_signal_dispatch();
        $this->processes[] = $this->process;


        $this->log('Invoking shell command: ' . $this->process->getCommandLine());
        $this->logger->debug('Invoking shell command: ' . $this->process->getCommandLine());

        try {
            pcntl_signal_dispatch();
            $this->process->start([$this, 'logWorkerOutput']);
            pcntl_signal_dispatch();
        } catch (ProcessRuntimeException $e) {
            $this->process->stop();
            pcntl_signal_dispatch();

            throw new \RuntimeException(sprintf('Command "%s" failed: %s', $this->process->getCommandLine(),
                $e->getMessage()), 0, $e);
        }
    }

    public function logWorkerOutput($stream, $output)
    {
        if ($stream == 'err') {
            $this->logger->error($output);
        } else {
            $this->logger->info($output);
        }
    }

    public function setMaxProcessPoolValue($maxProcesses)
    {
        $this->processPool->setMaxProcesses($maxProcesses);
    }

    private function createPayloadFile($payload)
    {
        pcntl_signal_dispatch();
        $path = tempnam(sys_get_temp_dir(), $this->prefix);

        if (file_put_contents($path, $payload) === false) {
            throw new \RuntimeException('Cannot write payload file to path: ' . $path);
        }
        pcntl_signal_dispatch();

        return $path;
    }

    /**
     * @return Process[]
     */
    public function getProcesses(): array
    {
        return $this->processes;
    }
}
