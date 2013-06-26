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

/**
 * This command dumps XsendFile Apache condifuration
 */
class XSendFileMappingApacheDumper extends Command
{
    public function __construct()
    {
        parent::__construct('xsendfile:dump-apache');

        $this->setDescription('Dump XSendFile mapping for Apache web server');
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $mapper = $this->container['phraseanet.xsendfile-mapping'];

        $output->writeln('<info>Apache XSendfile configuration</info>');
        $output->writeln('');
        $output->writeln('<IfModule mod_xsendfile.c>');
        $output->writeln('  <Files *>');
        $output->writeln('      XSendFile on');
        foreach ($this->container['phraseanet.xsendfile-mapping']->getMapping() as $entry) {
            $output->writeln('      XSendFilePath  ' .  $mapper->sanitizePath($entry['directory']));
        }
        $output->writeln('  </Files>');
        $output->writeln('</IfModule>');
        $output->writeln('');

        return 1;
    }
}
