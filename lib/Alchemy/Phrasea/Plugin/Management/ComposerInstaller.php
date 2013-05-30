<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Plugin\Management;

use Alchemy\Phrasea\Plugin\Exception\ComposerInstallException;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Process\Exception\ExceptionInterface as ProcessException;
use Guzzle\Common\Exception\GuzzleException;
use Guzzle\Http\Client as Guzzle;

class ComposerInstaller
{
    private $composer;
    private $guzzle;
    private $pluginsDirectory;
    private $phpExecutable;

    public function __construct($pluginsDirectory, Guzzle $guzzle, $phpExecutable)
    {
        if (!is_executable($phpExecutable)) {
            throw new ComposerInstallException(sprintf('`%s` is not a valid PHP executable', $phpExecutable));
        }

        $this->guzzle = $guzzle;
        $this->pluginsDirectory = $pluginsDirectory;
        $this->phpExecutable = $phpExecutable;
        $this->composer = $this->pluginsDirectory . DIRECTORY_SEPARATOR . 'composer.phar';
    }

    public function install($directory)
    {
        $process = $this->createProcessBuilder()
            ->add('install')
            ->add('--working-dir')
            ->add($directory)
            ->add('--no-dev')
            ->add('--optimize-autoloader')
            ->getProcess();

        try {
            $process->run();
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
            $this->installComposer();
        } else {
            $process = ProcessBuilder::create(array(
                $this->phpExecutable, $this->composer, 'self-update'
            ))->getProcess();
            $process->run();
        }

        return ProcessBuilder::create(array($this->phpExecutable, $this->composer));
    }

    private function installComposer()
    {
        $installer = $this->pluginsDirectory . DIRECTORY_SEPARATOR . 'installer';
        $handle = fopen($installer, 'w+');

        $request = $this->guzzle->get('https://getcomposer.org/installer', null, $handle);

        try {
            $response = $request->send();
            fclose($handle);
        } catch (GuzzleException $e) {
            fclose($handle);
            throw new ComposerInstallException('Unable to download composer install script.');
        }

        if (200 !== $response->getStatusCode()) {
            @unlink($installer);
            throw new ComposerInstallException('Unable to download composer install script.');
        }

        $dir = getcwd();
        if (!@chdir($this->pluginsDirectory)) {
            throw new ComposerInstallException('Unable to move to plugins directory for composer install.');
        }

        $process = ProcessBuilder::create(array($this->phpExecutable, $installer))->getProcess();

        try {
            $process->run();
            @unlink($installer);
        } catch (ProcessException $e) {
            @unlink($installer);
            throw new ComposerInstallException('Unable run composer install script.');
        }

        if (!@chdir($dir)) {
            throw new ComposerInstallException('Unable to move to plugins directory for composer install.');
        }

        if (!$process->isSuccessful()) {
            throw new ComposerInstallException('Composer install failed.');
        }

        if (!file_exists($this->composer)) {
            throw new ComposerInstallException('Composer install failed.');
        }
    }
}
