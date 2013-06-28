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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractXSendFileMappingDumper extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $configuration = 0;

        $output->writeln('');
        if ($this->container['phraseanet.file-serve']->isXSendFileEnable()) {
            $output->writeln('XSendFile support is <info>enabled</info>');
        } else {
            $output->writeln('XSendFile support is <error>disabled</error>');
            $configuration++;
        }
        if (2 < count($this->container['phraseanet.xsendfile-mapping']->getMapping())) {
            $output->writeln('XSendFile configuration seems <info>OK</info>');
        } else {
            $output->writeln('XSendFile configuration seems <error>invalid</error>');
            $configuration++;
        }
        $output->writeln('');

        return $configuration + $this->doDump($input, $output);
    }

    abstract protected function doDump(InputInterface $input, OutputInterface $output);
}
