<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Plugin;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class RemovePlugin extends AbstractPluginCommand
{
    public function __construct()
    {
        parent::__construct('plugins:remove');

        $this
            ->setDescription('Removes a plugin given its name')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the plugin');
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');

        $path = $this->container['plugins.directory'] . DIRECTORY_SEPARATOR . $name;

        $output->write("Removing <info>$name</info>...");
        $this->container['filesystem']->remove($path);
        $output->writeln(" <comment>OK</comment>");

        $this->updateConfigFiles($input, $output);

        return 0;
    }
}
