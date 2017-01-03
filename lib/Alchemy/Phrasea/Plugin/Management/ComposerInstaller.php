<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Plugin\Management;

use Alchemy\Phrasea\Plugin\Exception\ComposerInstallException;
use Alchemy\Phrasea\Utilities\ComposerSetup;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Process\Exception\ExceptionInterface as ProcessException;

class ComposerInstaller
{
    private $composer;
    private $phpExecutable;
    private $setup;

    public function __construct(ComposerSetup $setup, $pluginsDirectory, $phpExecutable)
    {
        if (!is_executable($phpExecutable)) {
            throw new ComposerInstallException(sprintf('`%s` is not a valid PHP executable', $phpExecutable));
        }

        $this->setup = $setup;
        $this->phpExecutable = $phpExecutable;
        $this->composer = $pluginsDirectory . DIRECTORY_SEPARATOR . 'composer.phar';
    }

    public function install($directory, $verbose = false)
    {
        $process = $this->createProcessBuilder()
            ->setTimeout(null)
            ->add('install')
            ->add('--working-dir')
            ->add($directory)
            ->add('--no-dev')
            ->add('--optimize-autoloader')
            ->getProcess();

        try {
            $prefix = PHP_EOL . ' >> ';

            $process->run(function ($type, $bytes) use ($verbose, & $prefix) {
                if ($verbose && $type == 'err') {
                    echo $prefix . str_replace(PHP_EOL, PHP_EOL . ' >> ', $bytes);

                    $prefix = '';
                }
            });

            echo PHP_EOL;
        } catch (ProcessException $e) {
            throw new ComposerInstallException(sprintf('Unable to composer install %s', $directory), $e->getCode(), $e);
        }

        if (!$process->isSuccessful()) {
            throw new ComposerInstallException(sprintf('Unable to composer install %s', $directory));
        }
    }

    /**
     * @return ProcessBuilder
     */
    private function createProcessBuilder()
    {
        if (!file_exists($this->composer)) {
            try {
                $this->setup->setup($this->composer);
            } catch (RuntimeException $e) {
                throw new ComposerInstallException('Unable to install composer.', $e->getCode(), $e);
            }
        } else {
            $process = ProcessBuilder::create([
                $this->phpExecutable, $this->composer, 'self-update'
            ])->getProcess();
            $process->run();
        }

        return ProcessBuilder::create([$this->phpExecutable, $this->composer]);
    }
}
