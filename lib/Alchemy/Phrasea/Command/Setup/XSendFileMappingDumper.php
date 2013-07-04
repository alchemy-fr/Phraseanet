<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Setup;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class XSendFileMappingDumper extends Command
{
    public function __construct($name = null) {
        parent::__construct('xsendfile:configuration-dumper');

        $this->setDescription('Dump the virtual host configuration depending on Phraseanet configuration');
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');

        if (!$this->container['phraseanet.xsendfile-factory']->isXSendFileModeEnabled()) {
            $output->writeln('XSendFile support is <error>disabled</error>');

            return 1;
        }

        $output->writeln('XSendFile support is <info>enabled</info>');

        try {
            $configuration = $this->container['phraseanet.xsendfile-factory']->getMode(true)->getVirtualHostConfiguration();
            $output->writeln('XSendFile configuration seems <info>OK</info>');
            $output->writeln($configuration);

            return 0;
        } catch (RuntimeException $e) {
            $output->writeln('XSendFile configuration seems <error>invalid</error>');

            return 1;
        }

        $output->writeln('');

        return 0;
    }
}
