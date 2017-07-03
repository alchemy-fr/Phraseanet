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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class IndexCreateCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('searchengine:index:create')
            ->setDescription('Creates search index <fg=yellow;>(Deprecated use searchengine:index instead)</>')
            ->addOption('drop', 'd', InputOption::VALUE_NONE, 'Drops the index if it already exists.');
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        /** @var Indexer $indexer */
        $indexer = $this->container['elasticsearch.indexer'];

        $drop = $input->getOption('drop');
        $indexExists = $indexer->indexExists();

        if (! $drop && $indexExists) {
            $output->writeln('<error>The search index already exists.</error>');

            return 1;
        }

        if ($drop && $indexExists) {
            $output->writeln('<info>Dropping existing search index</info>');

            $indexer->deleteIndex();
        }

        $indexer->createIndex();
        $output->writeln('Search index was created');
    }
}
