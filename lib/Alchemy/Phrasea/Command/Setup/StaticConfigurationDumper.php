<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Setup;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StaticConfigurationDumper extends Command
{
    public function __construct($name = null)
    {
        parent::__construct('static-file:dump-configuration');

        $this->setDescription('Dump the virtual host configuration depending on Phraseanet configuration');
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');

        if (!$this->container['phraseanet.static-file-factory']->isStaticFileModeEnabled()) {
            $output->writeln('Static file support is <error>disabled</error>');
            $ret = 1;
        } else {
            $output->writeln('Static file support is <info>enabled</info>');
            $ret = 0;
        }

        try {
            $configuration = $this->container['phraseanet.static-file-factory']->getMode(true, true)->getVirtualHostConfiguration();
            $output->writeln('Static file configuration seems <info>OK</info>');
            $output->writeln($configuration);
        } catch (RuntimeException $e) {
            $output->writeln('Static file configuration seems <error>invalid</error>');
            $ret = 1;
        }

        $output->writeln('');

        return $ret;
    }
}
