<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\SearchEngine;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IndexFull extends Command
{
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $this->container['ES.indexer']->createIndex();
        $this->container['ES.indexer']->reindexAll();
    }
}
