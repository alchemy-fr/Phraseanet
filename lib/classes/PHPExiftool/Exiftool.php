<?php

/**
 * This file is part of the PHPExiftool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool;

use PHPExiftool\Exception\RuntimeException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

class Exiftool implements LoggerAwareInterface
{
    private $logger;
    private $binaryPath;

    public function __construct(LoggerInterface $logger, $binaryPath = null)
    {
        $this->logger = $logger;
        $this->binaryPath = $binaryPath;
    }

    /**
     * {@inheritdoc}
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Execute a command and return the output
     *
     * @param  string     $command
     * @param  int        $timeout
     * @return string
     * @throws \Exception
     */
    public function executeCommand($command, $timeout = 60)
    {
        $command = ($this->binaryPath == null? self::getBinary(): $this->binaryPath) . ' ' . $command;
        $process = new Process($command);
       // $process->setTimeout($timeout);
        // $process->setTimeout(0.2);
        // ??? $process->setIdleTimeout()

        $this->logger->addInfo(sprintf('Exiftool executes command %s', $process->getCommandLine()));

        file_put_contents(dirname(__FILE__).'/../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf("Exiftool executes command \"%s\" with timeout of %s", $process->getCommandLine(), $process->getTimeout())
        ), FILE_APPEND | LOCK_EX);

        $process->run();

        if ( ! $process->isSuccessful()) {
            throw new RuntimeException(sprintf('Command %s failed : %s, exitcode %s', $command, $process->getErrorOutput(), $process->getExitCode()));
        }

        $output = $process->getOutput();

        unset($process);

        return $output;
    }

    /**
     *
     * @return string
     */
    protected static function getBinary()
    {
        static $binary = null;

        if ($binary) {
            return $binary;
        }

        $dev = __DIR__ . '/../../../vendor/exiftool/exiftool/exiftool';
        $packaged = __DIR__ . '/../../../../../exiftool/exiftool/exiftool';

        foreach (array($packaged, $dev) as $location) {

            if (defined('PHP_WINDOWS_VERSION_BUILD')) {
                $location .= '.exe';
            }

            if (is_executable($location)) {
                return $binary = realpath($location);
            }
        }

        throw new RuntimeException('Unable to get exiftool binary');
    }
}
