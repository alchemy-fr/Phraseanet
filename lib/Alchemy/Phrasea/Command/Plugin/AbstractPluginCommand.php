<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Plugin;

use Alchemy\Phrasea\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

function normalizePath($path) {
    return array_reduce(explode('/', $path), function ($a, $b) {
			if($a === 0)
				$a = '/';

			if($b === '' || $b === '.')
				return $a;

			if($b === '..')
				return dirname($a);

			return preg_replace('/\/+/', '/', "$a/$b");
    }, 0);
}

abstract class AbstractPluginCommand extends Command
{
    protected function validatePlugins(InputInterface $input, OutputInterface $output)
    {
        $manifests = [];

        $output->write("Validating plugins...");
        foreach ($this->container['plugins.explorer'] as $directory) {
            $manifests[] = $this->container['plugins.plugins-validator']->validatePlugin($directory);
        }
        $output->writeln(" <comment>OK</comment>");

        return $manifests;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        if (basename($_SERVER['PHP_SELF']) === 'console') {
            $output->writeln("");
            $output->writeln(sprintf('<error> /!\ </error> <comment>Warning</comment>, this command is deprecated and will be removed as of Phraseanet 3.9, please use <info>bin/setup %s</info> instead <error> /!\ </error>', $this->getName()));
            $output->writeln("");
        }

        return $this->doExecutePluginAction($input, $output);
    }

    abstract protected function doExecutePluginAction(InputInterface $input, OutputInterface $output);

    protected function updateConfigFiles(InputInterface $input, OutputInterface $output)
    {
        $manifests = $this->validatePlugins($input, $output);

        $output->write("Updating config files...");
        $this->container['plugins.autoloader-generator']->write($manifests);
        $output->writeln(" <comment>OK</comment>");
    }

    protected function doInstallPlugin($source, InputInterface $input, OutputInterface $output)
    {
        $output->write("Validating plugin...");
        $manifest = $this->container['plugins.plugins-validator']->validatePlugin($source);
        $output->writeln(" <comment>OK</comment> found <info>".$manifest->getName()."</info>");

        $targetDir  = $this->container['plugin.path'] . DIRECTORY_SEPARATOR . $manifest->getName();
        if (normalizePath($targetDir) !== normalizePath($source)) {
            $temporaryDir = $this->container['temporary-filesystem']->createTemporaryDirectory();
            $output->write("Importing <info>$source</info>...");
            $this->container['plugins.importer']->import($source, $temporaryDir);
            $output->writeln(" <comment>OK</comment>");
            $workingDir = $temporaryDir;
        } else {
            $workingDir = $targetDir;
        }

        if (!is_dir($workingDir.'/vendor')) {
            $output->write("Setting up composer...");
            $this->container['plugins.composer-installer']->install($workingDir);
            $output->writeln(" <comment>OK</comment>");
        }

        $output->write("Installing plugin <info>".$manifest->getName()."</info>...");
        if (isset($temporaryDir)) {
            $this->container['filesystem']->mirror($temporaryDir, $targetDir);
        }
        $output->writeln(" <comment>OK</comment>");

        $output->write("Copying public files <info>".$manifest->getName()."</info>...");
        $this->container['plugins.assets-manager']->update($manifest);
        $output->writeln(" <comment>OK</comment>");

        if (isset($temporaryDir)) {
            $output->write("Removing temporary directory...");
            $this->container['filesystem']->remove($temporaryDir);
            $output->writeln(" <comment>OK</comment>");
        }

        $output->write("Activating plugin...");
        $this->container['conf']->set(['plugins', $manifest->getName(), 'enabled'], true);
        $output->writeln(" <comment>OK</comment>");

        $this->updateConfigFiles($input, $output);
    }
}
