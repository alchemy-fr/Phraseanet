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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class AddPlugin extends AbstractPluginCommand
{
    public function __construct()
    {
        parent::__construct('plugins:add');

        $this
            ->setDescription('Installs a plugin to Phraseanet')
            ->addArgument('source', InputArgument::REQUIRED, 'The source is a folder');
    }

    protected function doExecutePluginAction(InputInterface $input, OutputInterface $output)
    {
        $source = $input->getArgument('source');

        $temporaryDir = $this->container['temporary-filesystem']->createTemporaryDirectory();

        $output->write("Importing <info>$source</info>...");
        $this->container['plugins.importer']->import($source, $temporaryDir);
        $output->writeln(" <comment>OK</comment>");

        $output->write("Validating plugin...");
        $manifest = $this->container['plugins.plugins-validator']->validatePlugin($temporaryDir);
        $output->writeln(" <comment>OK</comment> found <info>".$manifest->getName()."</info>");

        $targetDir  = $this->container['plugin.path'] . DIRECTORY_SEPARATOR . $manifest->getName();

        $output->write("Setting up composer...");
        $this->container['plugins.composer-installer']->install($temporaryDir, $output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE);
        $output->writeln(" <comment>OK</comment>");

        $output->write("Installing plugin <info>".$manifest->getName()."</info>...");
        $this->container['filesystem']->mirror($temporaryDir, $targetDir);
        $output->writeln(" <comment>OK</comment>");

        $output->write("Copying public files <info>".$manifest->getName()."</info>...");
        $this->container['plugins.assets-manager']->update($manifest);
        $output->writeln(" <comment>OK</comment>");

        $output->write("Removing temporary directory...");
        $this->container['filesystem']->remove($temporaryDir);
        $output->writeln(" <comment>OK</comment>");

        $output->write("Activating plugin...");
        $this->container['conf']->set(['plugins', $manifest->getName(), 'enabled'], true);
        $output->writeln(" <comment>OK</comment>");

        $this->updateConfigFiles($input, $output);

        return 0;
    }
}
