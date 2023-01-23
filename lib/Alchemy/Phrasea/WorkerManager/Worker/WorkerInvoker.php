<?php

namespace Alchemy\Phrasea\WorkerManager\Worker;

use PhpAmqpLib\Channel\AMQPChannel;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Process\Exception\RuntimeException as ProcessRuntimeException;

class WorkerInvoker implements LoggerAwareInterface
{
    /**
     * @var string
     */
    private $environment;

    /**
     * @var string
     */
    private $command = 'worker:run-service';

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
     * @var bool
     */
    private $preservePayloads = false;

    /**
     * payload file prefix
     *
     * @var string
     */
    private $prefix = 'alchemy_wk_';

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

    /**
     * @param string $messageType
     * @param string $payload
     */
    public function invokeWorker($messageType, $payload, AMQPChannel $channel)
    {
        $args = [
            $this->binaryPath,
            $this->command,
            '-vv',
            $messageType,
            $this->createPayloadFile($payload)
        ];

        if ($this->environment) {
            $args[] = sprintf('-e=%s', $this->environment);
        }

        if ($this->preservePayloads) {
            $args[] = '--preserve-payload';
        }

        $process = $this->processPool->getWorkerProcess($args, $channel, getcwd());

        $this->logger->debug('Invoking shell command: ' . $process->getCommandLine());

        try {
            $process->start([$this, 'logWorkerOutput']);
        } catch (ProcessRuntimeException $e) {
            $process->stop();

            throw new \RuntimeException(sprintf('Command "%s" failed: %s', $process->getCommandLine(),
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
        $path = tempnam(sys_get_temp_dir(), $this->prefix);

        if (file_put_contents($path, $payload) === false) {
            throw new \RuntimeException('Cannot write payload file to path: ' . $path);
        }

        return $path;
    }
}
