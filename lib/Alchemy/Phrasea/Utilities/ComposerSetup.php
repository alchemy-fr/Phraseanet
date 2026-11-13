<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Utilities;

use Alchemy\Phrasea\Exception\RuntimeException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Process\Exception\ExceptionInterface as ProcessException;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class ComposerSetup
{
    private $guzzle;
    private $phpExecutable;

    public function __construct(ClientInterface $guzzle, $phpExecutable = null)
    {
        if (null === $phpExecutable) {
            $finder = new PhpExecutableFinder();
            $phpExecutable = $finder->find();
        }

        if (!is_executable($phpExecutable)) {
            throw new RuntimeException(sprintf('`%s` is not a valid PHP executable', $phpExecutable));
        }

        $this->guzzle = $guzzle;
        $this->phpExecutable = $phpExecutable;
    }

    /**
     * Downloads composer installer and setups it to the given target.
     *
     * @param string $target
     *
     * @throws RuntimeException
     */
    public function setup($target)
    {
        $installer = tempnam(sys_get_temp_dir(), 'install');

        try {
            $response = $this->guzzle->request('GET', 'https://getcomposer.org/installer', ['sink' => $installer]);
        } catch (GuzzleException $e) {
            @unlink($installer);
            throw new RuntimeException('Unable to download composer install script.');
        }

        if (200 !== $response->getStatusCode()) {
            @unlink($installer);
            throw new RuntimeException('Unable to download composer install script.');
        }

        $dir = getcwd();
        if (!@chdir(dirname($target))) {
            throw new RuntimeException('Unable to move to target directory for composer install.');
        }

        $process = new Process([$this->phpExecutable, $installer]);
        $process->setTimeout(300);

        try {
            $process->run();
            @unlink($installer);
        } catch (ProcessException $e) {
            @unlink($installer);
            throw new RuntimeException('Unable run composer install script.');
        }

        if (!@rename(getcwd() . '/composer.phar', $target)) {
            throw new RuntimeException('Composer install failed.');
        }

        if (!@chdir($dir)) {
            throw new RuntimeException('Unable to move back to origin directory.');
        }

        if (!$process->isSuccessful()) {
            throw new RuntimeException('Composer install failed.');
        }

        if (!file_exists($target)) {
            throw new RuntimeException('Composer install failed.');
        }
    }
}
