<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Setup;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Utilities\CrossDomainDumper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CrossDomainGenerator extends Command
{
    public function __construct($name = null)
    {
        parent::__construct('crossdomain:generate');

        $this->setDescription('Generate crossdomain.xml file according to configuration');
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $configuration = $this->container['phraseanet.configuration']['crossdomain'];

        $dumper = new CrossDomainDumper();

        $xml = $dumper->dump($configuration);

        $output->writeln($xml);

        $this->container['filesystem']->dumpFile($this->container['root.path'].'/www/crossdomain.xml', $xml);

        return ;
    }
}
