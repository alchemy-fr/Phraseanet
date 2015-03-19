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
use Guzzle\Common\Exception\GuzzleException;
use Guzzle\Http\Client as Guzzle;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Process\Exception\ExceptionInterface as ProcessException;
use Symfony\Component\Process\PhpExecutableFinder;

class ComposerSetup
{
    private $guzzle;
    private $phpExecutable;

    public function __construct(Guzzle $guzzle, $phpExecutable = null)
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
        $handle = fopen($installer, 'w+');

        $request = $this->guzzle->get('https://getcomposer.org/installer', null, $handle);

        try {
            $response = $request->send();
            fclose($handle);
        } catch (GuzzleException $e) {
            fclose($handle);
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

        $process = ProcessBuilder::create([$this->phpExecutable, $installer])
            ->setTimeout(300)
            ->getProcess()
        ;

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
