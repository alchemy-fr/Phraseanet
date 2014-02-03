<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Plugin;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class RemovePlugin extends AbstractPluginCommand
{
    public function __construct()
    {
        parent::__construct('plugins:remove');

        $this
            ->setDescription('Removes a plugin given its name')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the plugin')
            ->addOption('keep-config', 'k', InputOption::VALUE_NONE, 'Use this flag to keep configuration');
    }

    protected function doExecutePluginAction(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');

        if (!$this->container['plugins.manager']->hasPlugin($name)) {
            $output->writeln(sprintf('There is no plugin named <comment>%s</comment>, aborting', $name));

            return 0;
        }

        $output->write("Removing public assets...");
        $this->container['plugins.assets-manager']->remove($name);
        $output->writeln(" <comment>OK</comment>");

        $path = $this->container['plugins.directory'] . DIRECTORY_SEPARATOR . $name;

        $output->write("Removing <info>$name</info>...");
        $this->container['filesystem']->remove($path);
        $output->writeln(" <comment>OK</comment>");

        $this->updateConfigFiles($input, $output);

        if (!$input->getOption('keep-config')) {
            $conf = $this->container['phraseanet.configuration']->getConfig();
            unset($conf['plugins'][$name]);
            $this->container['phraseanet.configuration']->setConfig($conf);
        }

        return 0;
    }
}
