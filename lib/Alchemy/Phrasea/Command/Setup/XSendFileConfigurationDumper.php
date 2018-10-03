<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Setup;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class XSendFileConfigurationDumper extends Command
{
    public function __construct($name = null)
    {
        parent::__construct('xsendfile:dump-configuration');

        $this->setDescription('Dump the virtual host configuration depending on Phraseanet configuration');
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');

        if (!$this->container['phraseanet.xsendfile-factory']->isXSendFileModeEnabled()) {
            $output->writeln('XSendFile support is <error>disabled</error>, enable it to use this feature.');
            $ret = 1;
        } else {
            $output->writeln('XSendFile support is <info>enabled</info>');
            $ret = 0;
        }

        try {
            $configuration = $this->container['phraseanet.xsendfile-factory']->getMode(true, true)->getVirtualHostConfiguration();
            $output->writeln('XSendFile configuration seems <info>OK</info>');
            $output->writeln($configuration);

        } catch (RuntimeException $e) {
            $output->writeln('XSendFile configuration seems <error>invalid</error>');

            $ret = 1;
        }

        $output->writeln('');

        return $ret;
    }
}
