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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DisablePlugin extends AbstractPluginCommand
{
    public function __construct()
    {
        parent::__construct('plugins:disable');

        $this->setDescription('Disables a plugin')
             ->addArgument('name', InputArgument::REQUIRED, 'The name of the plugin');
    }

    protected function doExecutePluginAction(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');

        if (!$this->container['plugins.manager']->hasPlugin($name)) {
            $output->writeln(sprintf('There is no plugin named <comment>%s</comment>, aborting', $name));

            return 0;
        }

        if (!$this->container['plugins.manager']->isEnabled($name)) {
            $output->writeln(sprintf('Plugin named <comment>%s</comment> is already disabled, aborting', $name));

            return 0;
        }

        $this->container['plugins.manager']->disable($name);
        $output->writeln(sprintf('Plugin named <info>%s</info> is now disabled', $name));

        return 0;
    }
}
